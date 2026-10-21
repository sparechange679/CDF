<?php
// Email settings. Keep this file out of version control (it holds the SMTP password).
// Values can also be set with environment variables.
//
// Quick start for testing: create a free Mailtrap inbox (mailtrap.io) and paste its
// SMTP username and password below. Emails then land in that inbox instead of real ones.

// Master switch. false = emails are only recorded in the notification table (status "queued").
define('MAIL_ENABLED', filter_var(getenv('CDF_MAIL_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN));

define('MAIL_HOST',       getenv('CDF_MAIL_HOST') ?: '');
define('MAIL_PORT',  (int)(getenv('CDF_MAIL_PORT') ?: 2525));
define('MAIL_USERNAME',   getenv('CDF_MAIL_USER') ?: 'blessingskishindosabuni@gmail.com');
define('MAIL_PASSWORD',   getenv('CDF_MAIL_PASS') ?: 'nulm qsvl ebko olfu');

// 'tls' = STARTTLS (ports 587 and 2525), 'ssl' = implicit SSL (port 465), '' = none
define('MAIL_ENCRYPTION', getenv('CDF_MAIL_ENC') ?: 'smtp');

define('MAIL_FROM_ADDRESS', getenv('CDF_MAIL_FROM') ?: 'blessingskishindosabuni@gmail.com');
const MAIL_FROM_NAME = 'CDF Malawi Portal';

// Address of the site without a trailing slash. Used for links inside emails.
define('APP_URL', rtrim(getenv('CDF_APP_URL') ?: 'http://localhost/cdf', '/'));

