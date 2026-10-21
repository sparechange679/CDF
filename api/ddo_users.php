<?php
// DDO account management.
//
// GET /api/ddo_users.php
//      -> { ok, me, users: [ { id, full_name, email, status, role, role_name,
//                              detail1, detail2, created_at, last_login_at } ] }
//
// POST /api/ddo_users.php { id, action } (needs X-CSRF-Token header)
//      action: "activate" pending or suspended -> active
//              "suspend" active -> suspended
//      -> { ok, id, status }
//
// Only a signed-in District Development Officer can use this endpoint.
// Citizens are not listed or managed here.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/notifications.php';

$me = current_user();
if (!$me) {
    json_out(['ok' => false, 'error' => 'Sign in to continue.'], 401);
}
if ($me['role'] !== 'ddo') {
    json_out(['ok' => false, 'error' => 'Only a District Development Officer can manage accounts.'], 403);
}

$pdo = db();

/* ------------------------------------------------------------------ */
/* List                                                                */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query(
        "SELECT u.id, u.full_name, u.email, u.status, u.created_at, u.last_login_at,
                r.slug AS role, r.name AS role_name,
                CASE r.slug
                    WHEN 'council'    THEN cp.council_ward
                    WHEN 'mp'         THEN mc.name
                    WHEN 'ddo'        THEN dp.district
                    WHEN 'contractor' THEN kp.company_name
                END AS detail1,
                CASE r.slug
                    WHEN 'council'    THEN cp.position
                    WHEN 'mp'         THEN mp.parliamentary_office
                    WHEN 'ddo'        THEN dp.staff_number
                    WHEN 'contractor' THEN kp.business_reg_number
                END AS detail2
           FROM users u
           JOIN roles r ON r.id = u.role_id
      LEFT JOIN council_profiles    cp ON cp.user_id = u.id
      LEFT JOIN mp_profiles         mp ON mp.user_id = u.id
      LEFT JOIN constituencies      mc ON mc.id = mp.constituency_id
      LEFT JOIN ddo_profiles        dp ON dp.user_id = u.id
      LEFT JOIN contractor_profiles kp ON kp.user_id = u.id
          WHERE r.slug <> 'citizen'
       ORDER BY FIELD(u.status, 'pending', 'active', 'suspended'), u.created_at DESC"
    )->fetchAll();

    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
    }
    unset($row);

    json_out(['ok' => true, 'me' => (int) $me['id'], 'users' => $rows]);
}

/* ------------------------------------------------------------------ */
/* Change status                                                       */
/* ------------------------------------------------------------------ */
require_post_with_csrf();

$in     = read_json();
$id     = (int) ($in['id'] ?? 0);
$action = (string) ($in['action'] ?? '');

if ($id <= 0 || !in_array($action, ['activate', 'suspend'], true)) {
    json_out(['ok' => false, 'error' => 'Invalid request.'], 422);
}
if ($id === (int) $me['id']) {
    json_out(['ok' => false, 'error' => 'You cannot change the status of your own account.'], 403);
}

$stmt = $pdo->prepare(
    'SELECT u.id, u.email, u.status, r.slug AS role
       FROM users u JOIN roles r ON r.id = u.role_id
      WHERE u.id = ?'
);
$stmt->execute([$id]);
$target = $stmt->fetch();

if (!$target || $target['role'] === 'citizen') {
    json_out(['ok' => false, 'error' => 'Account not found.'], 404);
}

if ($action === 'activate') {
    if ($target['status'] === 'active') {
        json_out(['ok' => false, 'error' => 'This account is already active.'], 409);
    }
    $new = 'active';
    // Fresh start: clear any lockout left over from failed sign-ins.
    $pdo->prepare('UPDATE users SET status = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?')
        ->execute([$new, $id]);
    log_event((int) $me['id'], $target['email'], $target['status'] === 'pending' ? 'account_approved' : 'account_reactivated');
} else {
    if ($target['status'] !== 'active') {
        json_out(['ok' => false, 'error' => 'Only active accounts can be suspended.'], 409);
    }
    $new = 'suspended';
    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$new, $id]);
    // End "keep me signed in" sessions so the suspension takes effect straight away.
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$id]);
    log_event((int) $me['id'], $target['email'], 'account_suspended');
}

notify_account_status(
    $pdo, $id,
    $new === 'suspended' ? 'suspended' : ($target['status'] === 'pending' ? 'approved' : 'reactivated')
);

json_out(['ok' => true, 'id' => $id, 'status' => $new]);
