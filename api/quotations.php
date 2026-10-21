<?php
// Quotation API (DDO <-> contractor).
//
// GET  /api/quotation.php                 DDO: all quotations. Contractor: their own.
// GET  /api/quotation.php?project_id=ID   DDO: project + contractor + quotation (or null).
//                                          Contractor: their quotation for that project.
// POST (X-CSRF-Token header)
//   DDO        { action:"request", project_id, note, due_date }
//   Contractor { action:"submit",  project_id, amount, duration_days, breakdown }
//   DDO        { action:"decide",  project_id, decision:"accepted"|"rejected", note }
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/notifications.php';

$pdo = db();
$me = current_user();
if (!$me) {
    json_out(['ok' => false, 'error' => 'Sign in to continue.'], 401);
}
if (!in_array($me['role'], ['ddo', 'contractor'], true)) {
    json_out(['ok' => false, 'error' => 'Only DDO and contractor accounts can use quotations.'], 403);
}

function q_select(): string
{
    return "SELECT q.id, q.project_id, q.contractor_id, q.status, q.request_note,
                   DATE_FORMAT(q.due_date, '%Y-%m-%d') AS due_date,
                   DATE_FORMAT(q.requested_at, '%d %b %Y') AS requested_at,
                   q.amount, q.duration_days, q.breakdown,
                   DATE_FORMAT(q.submitted_at, '%d %b %Y') AS submitted_at,
                   q.decision_note, DATE_FORMAT(q.decided_at, '%d %b %Y') AS decided_at,
                   p.title, p.reference_no AS reference, p.area, p.budget,
                   k.name AS constituency,
                   u.full_name AS contractor_name, kp.company_name
              FROM quotations q
              JOIN projects p ON p.id = q.project_id
              JOIN constituencies k ON k.id = p.constituency_id
              JOIN users u ON u.id = q.contractor_id
         LEFT JOIN contractor_profiles kp ON kp.user_id = u.id";
}

function q_clean(array $r): array
{
    $r['id'] = (int)$r['id'];
    $r['project_id'] = (int)$r['project_id'];
    $r['contractor_id'] = (int)$r['contractor_id'];
    $r['budget'] = (float)$r['budget'];
    $r['amount'] = $r['amount'] !== null ? (float)$r['amount'] : null;
    $r['duration_days'] = $r['duration_days'] !== null ? (int)$r['duration_days'] : null;
    return $r;
}

/* ------------------------------ GET ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pid = isset($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

    if ($me['role'] === 'ddo' && $pid > 0) {
        $st = $pdo->prepare(
            'SELECT p.id, p.title, p.reference_no AS reference, p.area, p.sector, p.budget,
                    k.name AS constituency,
                    a.contractor_id, a.status AS assignment_status,
                    u.full_name AS contractor_name, u.email AS contractor_email, kp.company_name
               FROM projects p
               JOIN constituencies k ON k.id = p.constituency_id
          LEFT JOIN assignments a ON a.project_id = p.id
          LEFT JOIN users u ON u.id = a.contractor_id
          LEFT JOIN contractor_profiles kp ON kp.user_id = u.id
              WHERE p.id = ?'
        );
        $st->execute([$pid]);
        $project = $st->fetch();
        if (!$project) {
            json_out(['ok' => false, 'error' => 'Project not found.'], 404);
        }
        $project['id'] = (int)$project['id'];
        $project['budget'] = (float)$project['budget'];

        $qs = $pdo->prepare(q_select() . ' WHERE q.project_id = ?');
        $qs->execute([$pid]);
        $q = $qs->fetch();
        json_out(['ok' => true, 'project' => $project, 'quotation' => $q ? q_clean($q) : null]);
    }

    $sql = q_select();
    $where = [];
    $params = [];
    if ($me['role'] === 'contractor') {
        $where[] = 'q.contractor_id = ?';
        $params[] = (int)$me['id'];
    }
    if ($pid > 0) {
        $where[] = 'q.project_id = ?';
        $params[] = $pid;
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $stmt = $pdo->prepare($sql . ' ORDER BY q.updated_at DESC');
    $stmt->execute($params);
    json_out(['ok' => true, 'quotations' => array_map('q_clean', $stmt->fetchAll())]);
}

/* ------------------------------ POST ----------------------------- */
require_post_with_csrf();

$in = read_json();
$action = (string)($in['action'] ?? '');
$pid = isset($in['project_id']) && is_numeric($in['project_id']) ? (int)$in['project_id'] : 0;
if ($pid <= 0) {
    json_out(['ok' => false, 'error' => 'A valid project is required.'], 422);
}

$cur = $pdo->prepare('SELECT * FROM quotations WHERE project_id = ?');
$cur->execute([$pid]);
$existing = $cur->fetch() ?: null;

/* DDO: request a quotation from the assigned contractor */
if ($me['role'] === 'ddo' && $action === 'request') {
    $note = trim((string)($in['note'] ?? ''));
    $due = trim((string)($in['due_date'] ?? ''));
    if (text_len($note) > 2000) {
        json_out(['ok' => false, 'error' => 'The note must be 2000 characters or fewer.'], 422);
    }
    if ($due !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
        json_out(['ok' => false, 'error' => 'Enter a valid due date.'], 422);
    }

    $a = $pdo->prepare('SELECT contractor_id FROM assignments WHERE project_id = ?');
    $a->execute([$pid]);
    $contractorId = (int)$a->fetchColumn();
    if ($contractorId <= 0) {
        json_out(['ok' => false, 'error' => 'Assign a contractor to this project first.'], 422);
    }
    if ($existing && $existing['status'] === 'submitted') {
        json_out(['ok' => false, 'error' => 'A quotation has already been received. Accept or reject it first.'], 409);
    }
    if ($existing && $existing['status'] === 'accepted') {
        json_out(['ok' => false, 'error' => 'The quotation for this project has already been accepted.'], 409);
    }

    $pdo->prepare(
        "INSERT INTO quotations (project_id, contractor_id, requested_by, request_note, due_date, status, requested_at)
         VALUES (?, ?, ?, ?, ?, 'requested', NOW())
         ON DUPLICATE KEY UPDATE
            contractor_id = VALUES(contractor_id), requested_by = VALUES(requested_by),
            request_note = VALUES(request_note), due_date = VALUES(due_date),
            status = 'requested', requested_at = NOW(),
            amount = NULL, duration_days = NULL, breakdown = NULL, submitted_at = NULL,
            decision_note = NULL, decided_at = NULL"
    )->execute([$pid, $contractorId, (int)$me['id'], $note ?: null, $due ?: null]);

    log_event((int)$me['id'], $me['email'], 'ddo_request_quotation');
    notify_quotation_event($pdo, $pid, 'requested');
    json_out(['ok' => true, 'message' => 'Quotation requested. The contractor has been notified.'], 201);
}

/* Contractor: give the price */
if ($me['role'] === 'contractor' && $action === 'submit') {
    if (!$existing || (int)$existing['contractor_id'] !== (int)$me['id']) {
        json_out(['ok' => false, 'error' => 'No quotation has been requested from you for this project.'], 403);
    }
    if (!in_array($existing['status'], ['requested', 'submitted'], true)) {
        json_out(['ok' => false, 'error' => 'This quotation can no longer be changed.'], 409);
    }
    $amountRaw = $in['amount'] ?? null;
    $days = $in['duration_days'] ?? null;
    $breakdown = trim((string)($in['breakdown'] ?? ''));
    if (!is_numeric($amountRaw) || (float)$amountRaw <= 0) {
        json_out(['ok' => false, 'error' => 'Enter a valid price in MK.'], 422);
    }
    if (!is_numeric($days) || (int)$days < 1 || (int)$days > 3650) {
        json_out(['ok' => false, 'error' => 'Enter the number of days needed to complete the work.'], 422);
    }
    if (text_len($breakdown) > 3000) {
        json_out(['ok' => false, 'error' => 'The breakdown must be 3000 characters or fewer.'], 422);
    }

    $pdo->prepare(
        "UPDATE quotations
            SET amount = ?, duration_days = ?, breakdown = ?, status = 'submitted', submitted_at = NOW()
          WHERE project_id = ?"
    )->execute([round((float)$amountRaw, 2), (int)$days, $breakdown ?: null, $pid]);

    log_event((int)$me['id'], $me['email'], 'contractor_submit_quotation');
    notify_quotation_event($pdo, $pid, 'submitted');
    json_out(['ok' => true, 'message' => 'Quotation sent to the District Development Officer.']);
}

/* DDO: accept or reject the quotation received */
if ($me['role'] === 'ddo' && $action === 'decide') {
    $decision = (string)($in['decision'] ?? '');
    $note = trim((string)($in['note'] ?? ''));
    if (!in_array($decision, ['accepted', 'rejected'], true)) {
        json_out(['ok' => false, 'error' => 'Choose accept or reject.'], 422);
    }
    if ($decision === 'rejected' && $note === '') {
        json_out(['ok' => false, 'error' => 'Give a reason for rejecting the quotation.'], 422);
    }
    if (text_len($note) > 2000) {
        json_out(['ok' => false, 'error' => 'The note must be 2000 characters or fewer.'], 422);
    }
    if (!$existing || $existing['status'] !== 'submitted') {
        json_out(['ok' => false, 'error' => 'There is no submitted quotation to decide on.'], 409);
    }

    $pdo->prepare('UPDATE quotations SET status = ?, decision_note = ?, decided_at = NOW() WHERE project_id = ?')
        ->execute([$decision, $note ?: null, $pid]);

    log_event((int)$me['id'], $me['email'], 'ddo_quotation_' . $decision);
    notify_quotation_event($pdo, $pid, $decision);
    json_out(['ok' => true, 'message' => 'Quotation ' . $decision . '.', 'status' => $decision]);
}

json_out(['ok' => false, 'error' => 'That action is not available for your account.'], 403);