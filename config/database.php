<?php
// Database and security settings.
// Keep this file out of version control and out of public downloads.
// Values can also be set with environment variables.

define('DB_HOST', getenv('CDF_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('CDF_DB_NAME') ?: 'cdf_malawi');
define('DB_USER', getenv('CDF_DB_USER') ?: 'bless');   // use a dedicated MySQL user in production
define('DB_PASS', getenv('CDF_DB_PASS') ?: 'asdf');       // XAMPP/WAMP default is empty

// true = new staff accounts wait for approval (users.status = 'pending')
// false = staff accounts are active straight away and go to their dashboard
const STAFF_REQUIRE_APPROVAL = true;

const MAX_FAILED_LOGINS = 5;   // wrong passwords before the account is locked
const LOCK_MINUTES = 15;       // how long the lock lasts
const REMEMBER_DAYS = 30;      // "Keep me signed in" lifetime
