<?php
// Database and security settings.
// Keep this file out of version control and out of public downloads.
// Values can also be set with environment variables.

define('DB_HOST', getenv('CDF_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('CDF_DB_NAME') ?: 'cdf_malawi');
define('DB_USER', getenv('CDF_DB_USER') ?: 'bless');   // use a dedicated MySQL user in production
define('DB_PASS', getenv('CDF_DB_PASS') ?: 'asdf');       // XAMPP/WAMP default is empty

// true  = the roles listed in ROLES_REQUIRING_APPROVAL wait for approval (users.status = 'pending')
// false = every staff account is active straight away and goes to its dashboard
const STAFF_REQUIRE_APPROVAL = true;

// Staff roles that must be approved before they can sign in.
// Any role not listed here (mp, ddo) is active as soon as it registers.
// Keep this in step with the "approval" flags in js/auth.js (they only control the notice on the form).
const ROLES_REQUIRING_APPROVAL = ['council', 'contractor'];

const MAX_FAILED_LOGINS = 5;   // wrong passwords before the account is locked
const LOCK_MINUTES = 15;       // how long the lock lasts
const REMEMBER_DAYS = 30;      // "Keep me signed in" lifetime