<?php
// Citizen feedback API.
//
// GET  /api/feedback.php?project_id=<id>
//      -> { ok, feedback: [ { id, name, rating, comment, date } ] }
//
// POST /api/feedback.php (needs X-CSRF-Token header)
//      { project_id, rating (1-5), comment }
//      Only a signed-in citizen can post, and only for an approved project
//      in their own constituency.
require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pid = isset($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    if ($pid <= 0) {
        json_out(['ok' => false, 'error' => 'Choose a project.'], 422);
    }
    $stmt = $pdo->prepare(
        "SELECT f.id, f.rating, f.comment,
                DATE_FORMAT(f.created_at, '%d %b %Y') AS date,
                COALESCE(u.full_name, 'Anonymous citizen') AS name
           FROM feedback f
      LEFT JOIN users u ON u.id = f.user_id
          WHERE f.project_id = ?
       ORDER BY f.created_at DESC, f.id DESC
          LIMIT 100"
    );
    $stmt->execute([$pid]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['rating'] = (int)$r['rating'];
    }
    unset($r);
    json_out(['ok' => true, 'feedback' => $rows]);
}

require_post_with_csrf();

$me = current_user();
if (!$me) {
    json_out(['ok' => false, 'error' => 'Please sign in to submit feedback.', 'login_required' => true], 401);
}
if ($me['role'] !== 'citizen') {
    json_out(['ok' => false, 'error' => 'Only citizen accounts can submit project feedback.'], 403);
}

$in = read_json();
$pid = isset($in['project_id']) && is_numeric($in['project_id']) ? (int)$in['project_id'] : 0;
$rating = isset($in['rating']) && is_numeric($in['rating']) ? (int)$in['rating'] : 0;
$comment = trim((string)($in['comment'] ?? ''));

if ($pid <= 0) {
    json_out(['ok' => false, 'error' => 'Choose a project.'], 422);
}
if ($rating < 1 || $rating > 5) {
    json_out(['ok' => false, 'error' => 'Choose a rating from 1 to 5.'], 422);
}
if ($comment === '' || text_len($comment) > 2000) {
    json_out(['ok' => false, 'error' => 'Write your feedback (up to 2000 characters).'], 422);
}

$st = $pdo->prepare('SELECT id, constituency_id, status FROM projects WHERE id = ?');
$st->execute([$pid]);
$project = $st->fetch();
if (!$project || !in_array($project['status'], ['accepted', 'progress', 'completed'], true)) {
    json_out(['ok' => false, 'error' => 'Project not found.'], 404);
}

$cp = $pdo->prepare('SELECT constituency_id FROM citizen_profiles WHERE user_id = ?');
$cp->execute([(int)$me['id']]);
$cid = $cp->fetchColumn();
if ($cid !== false && (int)$cid !== (int)$project['constituency_id']) {
    json_out(['ok' => false, 'error' => 'You can only give feedback on projects in your registered constituency.'], 403);
}

// Simple flood guard: one feedback per citizen per project per minute.
$recent = $pdo->prepare(
    'SELECT COUNT(*) FROM feedback WHERE user_id = ? AND project_id = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)'
);
$recent->execute([(int)$me['id'], $pid]);
if ((int)$recent->fetchColumn() > 0) {
    json_out(['ok' => false, 'error' => 'Please wait a minute before sending more feedback on this project.'], 429);
}

$pdo->prepare('INSERT INTO feedback (project_id, user_id, rating, comment) VALUES (?, ?, ?, ?)')
    ->execute([$pid, (int)$me['id'], $rating, $comment]);
$id = (int)$pdo->lastInsertId();

log_event((int)$me['id'], $me['email'], 'citizen_feedback');

json_out([
    'ok' => true,
    'message' => 'Thank you. Your feedback has been recorded.',
    'feedback' => [
        'id' => $id,
        'name' => $me['full_name'],
        'rating' => $rating,
        'comment' => $comment,
        'date' => date('d M Y'),
    ],
], 201);