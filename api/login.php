<?php
// POST /api/login.php { role, email, password, remember }
require_once __DIR__ . '/../includes/bootstrap.php';
require_post_with_csrf();

$in       = read_json();
$email    = strtolower(trim((string) ($in['email'] ?? '')));
$password = (string) ($in['password'] ?? '');
$role     = (string) ($in['role'] ?? '');
$remember = !empty($in['remember']);

if ($email === '' || $password === '' || !in_array($role, ROLE_SLUGS, true)) {
    json_out(['ok' => false, 'error' => 'Enter your email and password.'], 422);
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT u.id, u.full_name, u.password_hash, u.status, u.failed_login_attempts, u.locked_until,
            (u.locked_until IS NOT NULL AND u.locked_until > NOW()) AS is_locked,
            r.slug AS role, r.name AS role_name, r.dashboard
       FROM users u JOIN roles r ON r.id = u.role_id
      WHERE u.email = ?'
);
$stmt->execute([$email]);
$user = $stmt->fetch() ?: null;

// Still locked: refuse without checking the password.
if ($user && $user['is_locked']) {
    log_event((int) $user['id'], $email, 'login_failed');
    json_out(['ok' => false, 'error' => 'Too many failed attempts. Try again in ' . LOCK_MINUTES . ' minutes.'], 429);
}

// Lock has run out: start counting from zero again.
if ($user && $user['locked_until'] !== null) {
    $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?')
        ->execute([$user['id']]);
}

// Always verify, even for unknown emails, so timing does not reveal which accounts exist.
$passwordOk = password_verify($password, $user['password_hash'] ?? DUMMY_HASH);

if (!$user || !$passwordOk) {
    if ($user) {
        // The attempt counter is already incremented when locked_until is evaluated (left-to-right).
        $pdo->prepare(
            'UPDATE users
                SET failed_login_attempts = failed_login_attempts + 1,
                    locked_until = IF(failed_login_attempts >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)
              WHERE id = ?'
        )->execute([MAX_FAILED_LOGINS, LOCK_MINUTES, $user['id']]);
    }
    log_event($user ? (int) $user['id'] : null, $email, 'login_failed');
    json_out(['ok' => false, 'error' => 'Incorrect email or password.'], 401);
}

// Correct password from here on, so it is safe to explain what is wrong.
if ($user['status'] === 'pending') {
    json_out(['ok' => false, 'error' => 'Your account is waiting for approval. You will be able to sign in once it is approved.'], 403);
}
if ($user['status'] === 'suspended') {
    json_out(['ok' => false, 'error' => 'This account has been suspended. Contact the district office.'], 403);
}
if ($user['role'] !== $role) {
    json_out(['ok' => false, 'error' => 'This account is registered as ' . $user['role_name'] . '. Choose that role and try again.'], 403);
}

start_login((int) $user['id']);
if ($remember) {
    issue_remember_cookie((int) $user['id']);
}
log_event((int) $user['id'], $email, 'login_success');

$response = [
    'ok'       => true,
    'role'     => $user['role'],
    'name'     => $user['full_name'],
    'redirect' => $user['dashboard'],
];

// Citizens browse by constituency, so send theirs back for the portal to use.
if ($user['role'] === 'citizen') {
    $c = $pdo->prepare(
        'SELECT c.code, c.name FROM citizen_profiles p JOIN constituencies c ON c.id = p.constituency_id WHERE p.user_id = ?'
    );
    $c->execute([$user['id']]);
    $response['constituency'] = $c->fetch() ?: null;
}

json_out($response);
