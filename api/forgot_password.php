<?php
// POST { action:"request", email }              -> always the same reply (no account enumeration)
// POST { action:"reset", token, password, confirm }
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/notifications.php';
require_post_with_csrf();

$pdo = db();
$in  = read_json();

if (($in['action'] ?? '') === 'request') {
    $email = strtolower(trim((string) ($in['email'] ?? '')));
    $generic = ['ok' => true, 'message' => 'If that email has an account, a reset link has been sent.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { json_out($generic); }

    $st = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND status = 'active'");
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u) {
        // one request per minute per account
        $recent = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)');
        $recent->execute([$u['id']]);
        if ((int) $recent->fetchColumn() === 0) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([$u['id'], hash('sha256', $token)]);
            log_event((int) $u['id'], $email, 'password_reset_requested');
            notify_password_reset((int) $u['id'], $email, $u['full_name'], APP_URL . '/reset_password.php?token=' . $token);
        }
    }
    json_out($generic);
}

if (($in['action'] ?? '') === 'reset') {
    $token = (string) ($in['token'] ?? '');
    $pw    = (string) ($in['password'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) { json_out(['ok' => false, 'error' => 'This reset link is not valid.'], 422); }
    if (strlen($pw) < 8)                         { json_out(['ok' => false, 'error' => 'Password must be at least 8 characters.'], 422); }
    if ($pw !== (string) ($in['confirm'] ?? ''))  { json_out(['ok' => false, 'error' => 'The two passwords do not match.'], 422); }

    $st = $pdo->prepare('SELECT r.id, r.user_id, u.email FROM password_resets r JOIN users u ON u.id = r.user_id
                          WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()');
    $st->execute([hash('sha256', $token)]);
    $r = $st->fetch();
    if (!$r) { json_out(['ok' => false, 'error' => 'This reset link has expired or was already used. Request a new one.'], 410); }

    $pdo->beginTransaction();
    $claim = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ? AND used_at IS NULL');
    $claim->execute([$r['id']]);
    if ($claim->rowCount() !== 1) { $pdo->rollBack(); json_out(['ok' => false, 'error' => 'This reset link was already used.'], 410); }

    $pdo->prepare('UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?')
        ->execute([password_hash($pw, PASSWORD_DEFAULT), $r['user_id']]);
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$r['user_id']]);          // sign out other devices
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$r['user_id']]);
    $pdo->commit();

    log_event((int) $r['user_id'], $r['email'], 'password_reset_completed');
    json_out(['ok' => true, 'message' => 'Password changed. You can sign in now.']);
}

json_out(['ok' => false, 'error' => 'Invalid request.'], 422);