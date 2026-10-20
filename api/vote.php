<?php
// Citizen voting API.
//
// GET  /api/vote.php
//      Returns projects and live voting statistics.
//      - If signed in as citizen, returns projects for their constituency + whether they voted.
//      - Optional query param ?constituency=<id|code> to filter by constituency.
//
// POST /api/vote.php (requires X-CSRF-Token header)
//      Casts a vote for a project.
//      Payload: { project_id: <int> }

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();

function vote_status_label(string $status): string
{
    return match ($status) {
        'submitted' => 'Submitted',
        'review'    => 'Under review',
        'accepted'  => 'Accepted',
        'denied'    => 'Denied',
        'progress'  => 'In Progress',
        'completed' => 'Completed',
        default     => ucfirst($status),
    };
}

/* ------------------------------------------------------------------ */
/* GET: Return votable projects and live vote statistics              */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $me = current_user();
    $userId = $me ? (int) $me['id'] : null;
    $userRole = $me ? (string) $me['role'] : null;

    $constituencyFilter = $_GET['constituency'] ?? null;
    $filterConstId = null;

    if ($me && $userRole === 'citizen') {
        $cProfStmt = $pdo->prepare(
            'SELECT cp.constituency_id, c.name, c.code, c.district
               FROM citizen_profiles cp
               JOIN constituencies c ON c.id = cp.constituency_id
              WHERE cp.user_id = ?'
        );
        $cProfStmt->execute([$userId]);
        $cProfile = $cProfStmt->fetch();
        if ($cProfile) {
            $filterConstId = (int) $cProfile['constituency_id'];
        }
    }

    if ($filterConstId === null && $constituencyFilter !== null && $constituencyFilter !== '') {
        $cFind = $pdo->prepare('SELECT id, name, code, district FROM constituencies WHERE code = ? OR id = ? LIMIT 1');
        $cFind->execute([$constituencyFilter, is_numeric($constituencyFilter) ? (int) $constituencyFilter : 0]);
        $cFound = $cFind->fetch();
        if ($cFound) {
            $filterConstId = (int) $cFound['id'];
        }
    }

    $sql = "SELECT p.id,
                   p.reference_no AS reference,
                   p.title,
                   p.area,
                   p.sector,
                   p.budget,
                   p.beneficiaries,
                   p.description,
                   p.constituency_id,
                   c.name AS constituency,
                   c.code AS constituency_code,
                   p.status,
                   p.progress,
                   p.voting_open,
                   DATE_FORMAT(p.voting_opens_at, '%d %b %Y') AS voting_opens_at,
                   DATE_FORMAT(p.voting_closes_at, '%d %b %Y') AS voting_closes_at,
                   (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes"
         . ($userId ? ", (SELECT COUNT(*) FROM votes v2 WHERE v2.project_id = p.id AND v2.user_id = " . (int) $userId . ") AS user_voted" : ", 0 AS user_voted")
         . " FROM projects p
             JOIN constituencies c ON c.id = p.constituency_id";

    $params = [];
    if ($filterConstId !== null) {
        $sql .= " WHERE p.constituency_id = ?";
        $params[] = $filterConstId;
    }

    $sql .= " ORDER BY p.voting_open DESC, votes DESC, p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    $totalVotes = 0;
    $openCount = 0;
    $leader = null;

    foreach ($projects as &$p) {
        $p['id']            = (int) $p['id'];
        $p['budget']        = (float) $p['budget'];
        $p['beneficiaries'] = $p['beneficiaries'] !== null ? (int) $p['beneficiaries'] : null;
        $p['votes']         = (int) $p['votes'];
        $p['user_voted']    = !empty($p['user_voted']);
        $p['voting_open']   = (int) ($p['voting_open'] ?? 1);
        $p['progress']      = (int) ($p['progress'] ?? 0);
        $p['statusLabel']   = vote_status_label((string) $p['status']);

        if ($p['voting_open'] === 1 && $p['status'] !== 'completed') {
            $totalVotes += $p['votes'];
            $openCount++;
            if ($leader === null || $p['votes'] > $leader['votes']) {
                $leader = [
                    'id'    => $p['id'],
                    'title' => $p['title'],
                    'votes' => $p['votes'],
                ];
            }
        }
    }
    unset($p);

    json_out([
        'ok'             => true,
        'projects'       => $projects,
        'total_votes'    => $totalVotes,
        'open_count'     => $openCount,
        'leader'         => $leader ? $leader['title'] : '—',
        'leader_project' => $leader,
        'user'           => $me ? [
            'id'       => (int) $me['id'],
            'name'     => $me['full_name'],
            'role'     => $me['role'],
            'is_citizen' => ($me['role'] === 'citizen'),
        ] : null,
    ]);
}

/* ------------------------------------------------------------------ */
/* POST: Cast citizen vote                                            */
/* ------------------------------------------------------------------ */
require_post_with_csrf();

$me = current_user();
if (!$me) {
    json_out([
        'ok'             => false,
        'error'          => 'Please sign in to vote on CDF projects.',
        'login_required' => true,
    ], 401);
}

if ($me['role'] !== 'citizen') {
    json_out([
        'ok'    => false,
        'error' => 'Only registered citizen accounts can cast votes on projects.',
    ], 403);
}

$in = read_json();
$projectId = isset($in['project_id']) && is_numeric($in['project_id']) ? (int) $in['project_id'] : 0;

if ($projectId <= 0) {
    json_out(['ok' => false, 'error' => 'Please select a valid project to vote for.'], 422);
}

// Fetch project details
$pStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.status, p.voting_open, p.constituency_id, c.name AS constituency_name
       FROM projects p
       JOIN constituencies c ON c.id = p.constituency_id
      WHERE p.id = ?'
);
$pStmt->execute([$projectId]);
$project = $pStmt->fetch();

if (!$project) {
    json_out(['ok' => false, 'error' => 'Project not found.'], 404);
}

if (empty($project['voting_open'])) {
    json_out(['ok' => false, 'error' => 'Voting has not been opened or has ended for this project.'], 400);
}

if ($project['status'] === 'completed') {
    json_out(['ok' => false, 'error' => 'Voting is closed for completed projects.'], 400);
}

// Fetch citizen constituency
$cpStmt = $pdo->prepare('SELECT constituency_id FROM citizen_profiles WHERE user_id = ?');
$cpStmt->execute([(int) $me['id']]);
$citProfile = $cpStmt->fetch();

if ($citProfile && (int) $citProfile['constituency_id'] !== (int) $project['constituency_id']) {
    json_out([
        'ok'    => false,
        'error' => 'You can only vote for projects located in your registered constituency (' . $project['constituency_name'] . ').',
    ], 403);
}

// Check if already voted
$chkStmt = $pdo->prepare('SELECT id FROM votes WHERE user_id = ? AND project_id = ?');
$chkStmt->execute([(int) $me['id'], $projectId]);
if ($chkStmt->fetch()) {
    json_out([
        'ok'            => false,
        'error'         => 'You have already voted for this project.',
        'already_voted' => true,
    ], 409);
}

// Insert vote
try {
    $ins = $pdo->prepare('INSERT INTO votes (user_id, project_id) VALUES (?, ?)');
    $ins->execute([(int) $me['id'], $projectId]);
} catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1062) {
        json_out([
            'ok'            => false,
            'error'         => 'You have already voted for this project.',
            'already_voted' => true,
        ], 409);
    }
    json_out(['ok' => false, 'error' => 'Could not record vote. Please try again.'], 500);
}

// Count total votes for this project
$cntStmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE project_id = ?');
$cntStmt->execute([$projectId]);
$totalVotes = (int) $cntStmt->fetchColumn();

log_event((int) $me['id'], $me['email'], 'citizen_vote_cast');

json_out([
    'ok'         => true,
    'message'    => 'Your vote has been cast successfully!',
    'project_id' => $projectId,
    'votes'      => $totalVotes,
    'voted'      => true,
]);
