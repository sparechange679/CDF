<?php
// Gives the page a CSRF token to send with every sign-in or registration request.
require_once __DIR__ . '/../includes/bootstrap.php';

json_out(['ok' => true, 'token' => csrf_token()]);
