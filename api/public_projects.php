<?php
// Approved projects for the citizen portal (projects.php, feedback.php).
//
// GET /api/public_projects.php
//      Returns projects that are accepted, in progress or completed.
//      - Signed-in citizen: only projects in their registered constituency.
//      - Otherwise: all constituencies, or ?constituency=<code|id> to filter.
//      - ?id=<id>: a single project (also returns its progress updates).
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$pdo = db();
$me  = current_user();

function pp_label(string $s): string
{
    return match ($s) {
        'accepted'  => 'Approved',
        'progress'  => 'In Progress',
        'completed' => 'Completed',
        default     => ucfirst($s),
    };
}

$filterId = null;
if ($me && $me['role'] === 'citizen') {
    $st = $pdo->prepare('SELECT constituency_id FROM citizen_profiles WHERE user_id = ?');
    $st->execute([(int) $me['id']]);
    $cid = $st->fetchColumn();
    if ($cid !== false) {
        $filterId = (int) $cid;
    }
}
if ($filterId === null && !empty($_GET['constituency'])) {
    $st = $pdo->prepare('SELECT id FROM constituencies WHERE code = ? OR id = ? LIMIT 1');
    $v  = (string) $_GET['constituency'];
    $st->execute([$v, is_numeric($v) ? (int) $v : 0]);
    $cid = $st->fetchColumn();
    if ($cid !== false) {
        $filterId = (int) $cid;
    }
}

$sql = "SELECT p.id, p.reference_no AS reference, p.title, p.area, p.sector, p.budget,
               p.beneficiaries, p.description, p.status, p.progress, p.image_url,
               p.constituency_id, c.name AS constituency,
               (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes,
               (SELECT COUNT(*) FROM feedback f WHERE f.project_id = p.id) AS feedback_count,
               (SELECT ROUND(AVG(f2.rating), 1) FROM feedback f2 WHERE f2.project_id = p.id) AS avg_rating
          FROM projects p
          JOIN constituencies c ON c.id = p.constituency_id
         WHERE p.status IN ('accepted', 'progress', 'completed')";
$params = [];

if ($filterId !== null) {
    $sql .= ' AND p.constituency_id = ?';
    $params[] = $filterId;
}
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $sql .= ' AND p.id = ?';
    $params[] = (int) $_GET['id'];
}
$sql .= ' ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$updStmt = $pdo->prepare(
    "SELECT DATE_FORMAT(u.update_date, '%d %b %Y') AS date, u.note AS text, u.percentage
       FROM progress_updates u
       JOIN assignments a ON a.id = u.assignment_id
      WHERE a.project_id = ?
   ORDER BY u.update_date DESC, u.id DESC"
);

foreach ($projects as &$p) {
    $p['id']             = (int) $p['id'];
    $p['budget']         = (float) $p['budget'];
    $p['beneficiaries']  = $p['beneficiaries'] !== null ? (int) $p['beneficiaries'] : null;
    $p['progress']       = (int) $p['progress'];
    $p['votes']          = (int) $p['votes'];
    $p['feedback_count'] = (int) $p['feedback_count'];
    $p['avg_rating']     = $p['avg_rating'] !== null ? (float) $p['avg_rating'] : null;
    $p['complete']       = $p['status'] === 'completed';
    $p['statusLabel']    = pp_label((string) $p['status']);

    $updStmt->execute([$p['id']]);
    $p['updates'] = $updStmt->fetchAll();
}
unset($p);

json_out([
    'ok'       => true,
    'projects' => $projects,
    'user'     => $me ? [
        'id'         => (int) $me['id'],
        'name'       => $me['full_name'],
        'role'       => $me['role'],
        'is_citizen' => $me['role'] === 'citizen',
    ] : null,
]);