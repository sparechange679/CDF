<?php
// Drop-in replacement for the old sendEmail() snippet.
//
//   require_once __DIR__ . '/../includes/mail_helper.php';
//   sendEmail('someone@example.com', 'Subject', '<p>HTML body</p>', 'Plain text fallback');
//
// It uses the same PHPMailer (from Composer's vendor/), the same Gmail settings from
// config/mail.php, and the same notifications log as the rest of the system, so there is
// only one place to configure email.

require_once __DIR__ . '/notifications.php';

function sendEmail(string $to, string $subject, string $body, string $altBody = ''): bool
{
    if ($altBody === '') {
        $altBody = trim(html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8'));
    }
    return send_notification(null, $to, 'general', $subject, $altBody, $body);
}