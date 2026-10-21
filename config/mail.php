<?php
// Email settings for includes/notifications.php (PHPMailer via Composer + Gmail SMTP).
// Keep this file out of version control: it holds the SMTP password.
// Every value can also be set with an environment variable.
//
// Gmail setup:
//   1. Turn on 2-Step Verification for the Gmail account.
//   2. Create an App Password (Google Account > Security > App passwords).
//   3. Paste the 16-character App Password below (spaces are fine) or set CDF_MAIL_PASS.

// Master switch. false = emails are only recorded in the notifications table (status "queued").
define('MAIL_ENABLED', filter_var(getenv('CDF_MAIL_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN));

define('MAIL_HOST',     getenv('CDF_MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT',     (int) (getenv('CDF_MAIL_PORT') ?: 587));          // Gmail: 587 (STARTTLS) or 465 (SSL). 2525 does NOT work with Gmail.
define('MAIL_USERNAME', getenv('CDF_MAIL_USER') ?: 'blessingskishindosabuni@gmail.com');
define('MAIL_PASSWORD', getenv('CDF_MAIL_PASS') ?: 'nulm qsvl ebko olfu');

// 'tls' = STARTTLS (port 587), 'ssl' = implicit SSL (port 465), '' = none.
// (The old value 'smtp' matched none of these, so encryption was silently switched off.)
define('MAIL_ENCRYPTION', getenv('CDF_MAIL_ENC') ?: 'tls');

// Gmail rewrites the From address to the authenticated account, so keep them the same.
define('MAIL_FROM_ADDRESS', getenv('CDF_MAIL_FROM') ?: 'blessingskishindosabuni@gmail.com');
const MAIL_FROM_NAME = 'Constituency Development Fund';

// Address of the site without a trailing slash. Used for links inside emails.
define('APP_URL', rtrim(getenv('CDF_APP_URL') ?: 'http://localhost/cdf', '/'));
