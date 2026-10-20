<?php
// Shared helpers for every authentication endpoint and protected page.

use JetBrains\PhpStorm\NoReturn;
use Random\RandomException;

require_once __DIR__ . '/../config/database.php';

const ROLE_SLUGS = ['citizen', 'council', 'mp', 'ddo', 'contractor'];

// A real bcrypt hash of a random string. Checked when an email is unknown so
// that "no such account" and "wrong password" take the same time to answer.
const DUMMY_HASH = '$2y$10$yWZsLRM1RdIq1ZUqpdINx.WJVFAVGqBfi.Io6bj6c9qriE3DzoAcO';

if (session_status() === PHP_SESSION_NONE) {
    session_name('cdf_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ------------------------------------------------------------------ */
/* Database                                                            */
/* ------------------------------------------------------------------ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

/** Character count that works with or without the string extension. */
function text_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}

/* ------------------------------------------------------------------ */
/* JSON + CSRF                                                         */
/* ------------------------------------------------------------------ */

#[NoReturn]
function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function read_json(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($data) ? $data : [];
}

/**
 * @throws RandomException
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function require_post_with_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
    }
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$sent || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        json_out(['ok' => false, 'error' => 'Your session expired. Refresh the page and try again.'], 403);
    }
}

/* ------------------------------------------------------------------ */
/* Audit log                                                           */
/* ------------------------------------------------------------------ */

function log_event(?int $userId, string $email, string $event): void
{
    try {
        db()->prepare(
            'INSERT INTO auth_log (user_id, email, event, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $userId,
            substr($email, 0, 190),
            $event,
            substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        // Logging must never block a sign-in.
    }
}

/* ------------------------------------------------------------------ */
/* Sessions and "keep me signed in"                                    */
/* ------------------------------------------------------------------ */

function start_login(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $userId;
    db()->prepare(
        'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?'
    )->execute([$userId]);
}

/**
 * @throws RandomException
 */
function issue_remember_cookie(int $userId): void
{
    $selector  = bin2hex(random_bytes(12));   // 24 chars
    $validator = bin2hex(random_bytes(32));
    db()->prepare(
        'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))'
    )->execute([$userId, $selector, hash('sha256', $validator), REMEMBER_DAYS]);

    setcookie('cdf_remember', $selector . ':' . $validator, [
        'expires'  => time() + REMEMBER_DAYS * 86400,
        'path'     => '/',
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
    ]);
}

function clear_remember_cookie(): void
{
    if (!empty($_COOKIE['cdf_remember'])) {
        $selector = explode(':', $_COOKIE['cdf_remember'])[0];
        db()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
    }
    setcookie('cdf_remember', '', ['expires' => time() - 3600, 'path' => '/']);
}

function login_from_remember_cookie(): void
{
    if (empty($_COOKIE['cdf_remember']) || !str_contains($_COOKIE['cdf_remember'], ':')) {
        return;
    }
    [$selector, $validator] = explode(':', $_COOKIE['cdf_remember'], 2);

    $stmt = db()->prepare(
        'SELECT t.user_id, t.validator_hash
           FROM remember_tokens t
           JOIN users u ON u.id = t.user_id
          WHERE t.selector = ? AND t.expires_at > NOW() AND u.status = \'active\''
    );
    $stmt->execute([$selector]);
    $row = $stmt->fetch();

    if ($row && hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $row['user_id'];
    } else {
        clear_remember_cookie();
    }
}

/* ------------------------------------------------------------------ */
/* Current user and page guards                                        */
/* ------------------------------------------------------------------ */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    if (empty($_SESSION['uid'])) {
        login_from_remember_cookie();
    }
    $user = null;
    if (!empty($_SESSION['uid'])) {
        $stmt = db()->prepare(
            'SELECT u.id, u.full_name, u.email, u.status, r.slug AS role, r.name AS role_name, r.dashboard
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = ?'
        );
        $stmt->execute([$_SESSION['uid']]);
        $found = $stmt->fetch();
        if ($found && $found['status'] === 'active') {
            $user = $found;
        } else {
            unset($_SESSION['uid']);   // suspended or deleted since login
        }
    }
    return $user;
}

/**
 * Put this at the very top of a protected page, before any HTML:
 *   <?php require __DIR__. '/../includes/bootstrap.php'; require_role('mp'); ?>
 */
function require_role($roles, string $loginUrl = '../auth.php'): array
{
    $roles = (array) $roles;
    $user  = current_user();

    if (!$user) {
        header('Location: ' . $loginUrl);
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><meta charset="UTF-8"><title>Access denied</title>'
           . '<body style="font-family:system-ui,sans-serif;max-width:480px;margin:15vh auto;padding:0 1rem">'
           . '<h1>Access denied</h1>'
           . '<p>You are signed in as ' . htmlspecialchars($user['role_name']) . ', which cannot open this page.</p>'
           . '<p><a href="' . htmlspecialchars($loginUrl) . '">Go to the sign-in page</a></p></body></html>';
        exit;
    }
    return $user;
}
