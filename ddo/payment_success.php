<?php
// Stripe sends the DDO back here after paying. The payment is verified with Stripe
// (the URL alone proves nothing) before the fund allocation is created.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/stripe.php';
require_role('ddo', '../auth.php');

$projectId = (int) ($_GET['project'] ?? 0);
$sessionId = (string) ($_GET['session_id'] ?? '');

$flag = 'payment=failed';
if ($projectId > 0 && preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
    $res = stripe_finalize_session(db(), $sessionId);
    if (!empty($res['ok']) && (int) ($res['project_id'] ?? 0) === $projectId) {
        $flag = 'paid=1';
    }
}
header('Location: quotation.php?project=' . $projectId . '&' . $flag);
exit;
