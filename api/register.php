<?php
// POST /api/register.php
// { role, full_name, email, password, confirm, extra1, extra2, terms }
//
// extra1 / extra2 are the two role-specific fields on the form:
//   council    council_ward          position
//   mp         constituency (name)   parliamentary_office
//   ddo        district              staff_number
//   contractor company_name          business_reg_number
//   citizen    constituency (name or code)   (not used)
use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ . '/../includes/bootstrap.php';
require_post_with_csrf();

$in       = read_json();
$role     = (string) ($in['role'] ?? '');
$name     = trim((string) ($in['full_name'] ?? ''));
$email    = strtolower(trim((string) ($in['email'] ?? '')));
$password = (string) ($in['password'] ?? '');
$confirm  = (string) ($in['confirm'] ?? '');
$extra1   = trim((string) ($in['extra1'] ?? ''));
$extra2   = trim((string) ($in['extra2'] ?? ''));

#[NoReturn]
function fail(string $message, int $status = 422): void
{
    json_out(['ok' => false, 'error' => $message], $status);
}

/* ---- Common checks ---- */
if (!in_array($role, ROLE_SLUGS, true))              fail('Choose a valid role.');
if ($name === '' || text_len($name) > 120)          fail('Enter your full name.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) fail('Enter a valid email address.');
if (strlen($password) < 8)                           fail('Password must be at least 8 characters.');
if ($password !== $confirm)                          fail('The two passwords do not match.');
if (empty($in['terms']))                             fail('Confirm the details and accept the terms to continue.');

$pdo = db();

/* ---- Role-specific checks ---- */
function constituency_id(PDO $pdo, string $value): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM constituencies WHERE code = ? OR LOWER(name) = LOWER(?) LIMIT 1');
    $stmt->execute([$value, $value]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

$profileSql    = '';
$profileParams = [];   // user id is added in front once the user row exists

switch ($role) {
    case 'citizen':
        $cid = constituency_id($pdo, $extra1);
        if ($cid === null) fail('Choose your constituency from the list.');
        $profileSql    = 'INSERT INTO citizen_profiles (user_id, constituency_id) VALUES (?, ?)';
        $profileParams = [$cid];
        break;

    case 'council':
        if ($extra1 === '' || text_len($extra1) > 120) fail('Enter your council or ward.');
        if ($extra2 === '' || text_len($extra2) > 100) fail('Enter your position.');
        $profileSql    = 'INSERT INTO council_profiles (user_id, council_ward, position) VALUES (?, ?, ?)';
        $profileParams = [$extra1, $extra2];
        break;

    case 'mp':
        $cid = constituency_id($pdo, $extra1);
        if ($cid === null) fail('Choose your constituency from the list.');
        if ($extra2 === '' || text_len($extra2) > 120) fail('Enter your parliamentary office.');
        $profileSql    = 'INSERT INTO mp_profiles (user_id, constituency_id, parliamentary_office) VALUES (?, ?, ?)';
        $profileParams = [$cid, $extra2];
        break;

    case 'ddo':
        if ($extra1 === '' || text_len($extra1) > 60) fail('Enter your district.');
        if ($extra2 === '' || text_len($extra2) > 40) fail('Enter your staff number.');
        $profileSql    = 'INSERT INTO ddo_profiles (user_id, district, staff_number) VALUES (?, ?, ?)';
        $profileParams = [$extra1, $extra2];
        break;

    case 'contractor':
        if ($extra1 === '' || text_len($extra1) > 150) fail('Enter your company name.');
        if ($extra2 === '' || text_len($extra2) > 60)  fail('Enter your business registration number.');
        $profileSql    = 'INSERT INTO contractor_profiles (user_id, company_name, business_reg_number) VALUES (?, ?, ?)';
        $profileParams = [$extra1, $extra2];
        break;
}

/* ---- Create the account ---- */
$status = ($role === 'citizen' || !STAFF_REQUIRE_APPROVAL) ? 'active' : 'pending';

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        'INSERT INTO users (role_id, full_name, email, password_hash, status)
         VALUES ((SELECT id FROM roles WHERE slug = ?), ?, ?, ?, ?)'
    )->execute([$role, $name, $email, password_hash($password, PASSWORD_DEFAULT), $status]);
    $userId = (int) $pdo->lastInsertId();

    $pdo->prepare($profileSql)->execute(array_merge([$userId], $profileParams));

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() === '23000') {
        $msg = $e->getMessage();
        if (str_contains($msg, 'uq_users_email'))     fail('An account with this email already exists. Sign in instead.', 409);
        if (str_contains($msg, 'uq_ddo_staff_number')) fail('That staff number is already registered.', 409);
        if (str_contains($msg, 'uq_contractor_reg'))   fail('That business registration number is already registered.', 409);
    }
    error_log('register failed: ' . $e->getMessage());
    fail('We could not create your account. Try again.', 500);
}

log_event($userId, $email, 'register');

/* ---- Active accounts go straight to their dashboard ---- */
if ($status === 'active') {
    start_login($userId);
    $dash = $pdo->prepare('SELECT dashboard FROM roles WHERE slug = ?');
    $dash->execute([$role]);
    $response = ['ok' => true, 'pending' => false, 'redirect' => $dash->fetchColumn()];

    if ($role === 'citizen') {
        $c = $pdo->prepare('SELECT code, name FROM constituencies WHERE id = ?');
        $c->execute([$cid]);
        $response['constituency'] = $c->fetch() ?: null;
    }
    json_out($response, 201);
}

json_out([
    'ok'      => true,
    'pending' => true,
    'message' => 'Account created. You can sign in once the district office approves it.',
], 201);
