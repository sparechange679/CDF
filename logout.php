<?php
// Link to this from any dashboard: <a href="../logout.php">Sign out</a>
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
if ($user) {
    log_event((int) $user['id'], $user['email'], 'logout');
}

clear_remember_cookie();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax']);
}
session_destroy();

header('Location: auth.php');
exit;
