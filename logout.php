<?php
// Link to this from any dashboard: <a href="../logout.php">Sign out</a>
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$wasCitizen = $user && $user['role'] === 'citizen';
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

if ($wasCitizen) {
    // Also forget the constituency kept in the browser, then go to the citizen login.
    ?>
    <!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><title>Signing out…</title></head>
    <body>
    <script>
        try { localStorage.removeItem('cdf_malawi_session'); } catch (e) {}
        window.location.replace('login.php');
    </script>
    <noscript><meta http-equiv="refresh" content="0;url=login.php"></noscript>
    </body></html>
    <?php
    exit;
}

header('Location: auth.php');
exit;