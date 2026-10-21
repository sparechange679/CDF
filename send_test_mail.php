<?php
/**
 * Sends one test email so you can check the settings in config/mail.php.
 *   Browser:  http://localhost/cdf/send_test_mail.php?to=you@example.com
 *   CLI:      php send_test_mail.php you@example.com
 * Only works from this computer. DELETE THIS FILE before going live.
 */
$isCli = (PHP_SAPI === 'cli');
if (!$isCli && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Test mail can only be sent from localhost.');
}

require_once __DIR__ . '/includes/notifications.php';

$to = $isCli ? ($argv[1] ?? '') : (string) ($_GET['to'] ?? '');
header('Content-Type: text/plain; charset=utf-8');

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    exit("Give an address: ?to=chisisibongile@gmail.com\n");
}

echo 'Host: ' . MAIL_HOST . ':' . MAIL_PORT . ' (' . (MAIL_ENCRYPTION ?: 'no encryption') . ")\n";
echo 'Sending to ' . $to . "...\n";

$ok = send_notification(
    null, $to, 'test', 'CDF Malawi test email',
    "If you can read this, email notifications are working.\n",
    email_layout('Test email', '<p>If you can read this, email notifications are working.</p>')
);

echo $ok ? "SENT\n" : 'FAILED: ' . mail_last_error() . "\n";
