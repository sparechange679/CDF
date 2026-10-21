<?php
// Stripe payment API (DDO only).
//
// GET  /api/payments.php?project_id=ID
//      -> { ok, payment: {status, paid_at, ...}|null, allocation: {...}|null,
//           preview: { amount_mk, currency, charge_amount } | null }
// POST { action:"checkout", project_id }   (X-CSRF-Token header)
//      -> { ok, url }   send the browser to this Stripe Checkout URL
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/stripe.php';

$pdo = db();
$me  = current_user();
if (!$me) {
    json_out(['ok' => false, 'error' => 'Sign in to continue.'], 401);
}
if ($me['role'] !== 'ddo') {
    json_out(['ok' => false, 'error' => 'Only a District Development Officer can pay for projects.'], 403);
}

function pay_quotation(PDO $pdo, int $pid): ?array
{
    $st = $pdo->prepare(
        'SELECT q.id, q.status, q.amount, p.title, p.reference_no
           FROM quotations q JOIN projects p ON p.id = q.project_id WHERE q.project_id = ?'
    );
    $st->execute([$pid]);
    return $st->fetch() ?: null;
}

function pay_paid(PDO $pdo, int $pid): ?array
{
    $st = $pdo->prepare("SELECT * FROM payments WHERE project_id = ? AND status = 'paid' ORDER BY id DESC LIMIT 1");
    $st->execute([$pid]);
    return $st->fetch() ?: null;
}

/* ------------------------------ GET ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pid = isset($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
    if ($pid <= 0) {
        json_out(['ok' => false, 'error' => 'A valid project is required.'], 422);
    }

    // If the DDO paid on Stripe but closed the tab before coming back, settle it now.
    if (!pay_paid($pdo, $pid)) {
        $pend = $pdo->prepare("SELECT stripe_session_id FROM payments WHERE project_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 3");
        $pend->execute([$pid]);
        foreach ($pend->fetchAll() as $row) {
            if (stripe_finalize_session($pdo, $row['stripe_session_id'])['ok'] ?? false) {
                break;
            }
        }
    }

    $paid = pay_paid($pdo, $pid);
    $alloc = null;
    if ($paid) {
        $a = $pdo->prepare("SELECT amount, source, reference, DATE_FORMAT(allocated_on, '%d %b %Y') AS date FROM allocations WHERE project_id = ?");
        $a->execute([$pid]);
        $alloc = $a->fetch() ?: null;
        if ($alloc) {
            $alloc['amount'] = (float) $alloc['amount'];
        }
    }

    $q = pay_quotation($pdo, $pid);
    $preview = null;
    if ($q && $q['status'] === 'accepted' && $q['amount'] !== null) {
        $mk = (float) $q['amount'];
        $preview = ['amount_mk' => $mk, 'currency' => strtoupper(STRIPE_CURRENCY), 'charge_amount' => stripe_charge_amount($mk)];
    }

    if ($paid) {
        $paid = [
            'status'        => 'paid',
            'paid_at'       => $paid['paid_at'],
            'amount_mk'     => (float) $paid['amount_mk'],
            'charge_amount' => (float) $paid['charge_amount'],
            'currency'      => strtoupper($paid['currency']),
        ];
    }
    json_out(['ok' => true, 'payment' => $paid, 'allocation' => $alloc, 'preview' => $preview]);
}

/* ------------------------------ POST ----------------------------- */
require_post_with_csrf();

$in  = read_json();
$pid = isset($in['project_id']) && is_numeric($in['project_id']) ? (int) $in['project_id'] : 0;
if (($in['action'] ?? '') !== 'checkout' || $pid <= 0) {
    json_out(['ok' => false, 'error' => 'Invalid request.'], 422);
}

$q = pay_quotation($pdo, $pid);
if (!$q || $q['status'] !== 'accepted' || $q['amount'] === null) {
    json_out(['ok' => false, 'error' => 'Accept the contractor\'s quotation before paying.'], 409);
}
if (pay_paid($pdo, $pid)) {
    json_out(['ok' => false, 'error' => 'This project has already been paid for.'], 409);
}

$mk     = (float) $q['amount'];            // always taken from the database, never from the browser
$charge = stripe_charge_amount($mk);
$minor  = (int) round($charge * 100);
if ($minor < 50) {
    json_out(['ok' => false, 'error' => 'The amount is below the minimum Stripe can charge.'], 422);
}

$base = APP_URL . '/ddo';
$r = stripe_request('POST', '/checkout/sessions', [
    'mode'               => 'payment',
    'client_reference_id' => 'project-' . $pid,
    'customer_email'     => $me['email'],
    'success_url'        => $base . '/payment_success.php?project=' . $pid . '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'         => $base . '/quotation.php?project=' . $pid . '&payment=cancelled',
    'line_items'         => [[
        'quantity'   => 1,
        'price_data' => [
            'currency'     => STRIPE_CURRENCY,
            'unit_amount'  => $minor,
            'product_data' => [
                'name'        => 'Project funding: ' . $q['title'],
                'description' => 'Reference ' . $q['reference_no'] . ' · MK ' . number_format($mk, 2),
            ],
        ],
    ]],
    'metadata' => ['project_id' => $pid, 'quotation_id' => (int) $q['id']],
]);
if (!$r['ok']) {
    json_out(['ok' => false, 'error' => $r['error']], 502);
}

$pdo->prepare(
    "INSERT INTO payments (project_id, quotation_id, user_id, stripe_session_id, amount_mk, charge_amount, currency, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
)->execute([$pid, (int) $q['id'], (int) $me['id'], $r['data']['id'], $mk, $charge, strtoupper(STRIPE_CURRENCY)]);

log_event((int) $me['id'], $me['email'], 'ddo_start_payment');
json_out(['ok' => true, 'url' => $r['data']['url']]);