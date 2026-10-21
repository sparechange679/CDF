<?php
// Project submission, review, decision, and voting API.
//
// GET  /api/projects.php
//      Returns projects for the signed-in user or workspace.
//      - Council users: returns proposals submitted by their account.
//      - MP users: returns proposals belonging to their assigned constituency.
//      - Citizen users: returns proposals in their registered constituency.
//      - Any authenticated user with ?id=<id>: returns single project details + comments.
//
// POST /api/projects.php (requires X-CSRF-Token header)
//      - Council action: submit new project
//        Payload: { title, area, sector, constituency, budget, beneficiaries, description }
//      - Council action: start or stop voting for a project
//        Payload: { action: "voting"|"start_voting"|"stop_voting"|"toggle_voting", project_id: <int>, voting_open: 1|0 }
//      - MP action: decision or status update with reason and optional/required comment
//        Payload: { action: "decision", project_id: <int>, status: "accepted"|"denied"|"review"|"progress"|"completed", reason: <string>, comment: <string> }
//      - MP action: add comment
//        Payload: { action: "comment", project_id: <int>, text: <string> }

use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/notifications.php';

$pdo = db();

function status_label(string $status): string
{
    return match ($status) {
        'submitted' => 'Submitted',
        'review' => 'Under review',
        'accepted' => 'Accepted',
        'denied' => 'Denied',
        'progress' => 'Implementation',
        'completed' => 'Completed',
        default => ucfirst($status),
    };
}

/* ------------------------------------------------------------------ */
/* GET: List or view projects                                         */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $me = current_user();
    if (!$me) {
        json_out(['ok' => false, 'error' => 'Sign in to view projects.'], 401);
    }

    $singleId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    if ($singleId !== null) {
        // Fetch single project
        $stmt = $pdo->prepare(
            "SELECT p.id,
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
                    COALESCE(u.full_name, 'Council Office') AS proposer,
                    DATE_FORMAT(p.created_at, '%d %b %Y') AS submitted,
                    p.status,
                    p.decision_reason AS reason,
                    DATE_FORMAT(p.decided_at, '%d %b %Y') AS decision_date,
                    p.progress,
                    p.voting_open,
                    DATE_FORMAT(p.voting_opens_at, '%d %b %Y') AS voting_opens_at,
                    DATE_FORMAT(p.voting_closes_at, '%d %b %Y') AS voting_closes_at,
                    (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes,
                    a.amount AS alloc_amount,
                    a.source AS alloc_source,
                    a.reference AS alloc_reference,
                    DATE_FORMAT(a.allocated_on, '%d %b %Y') AS alloc_date,
                    a.scope AS alloc_scope,
                    du.full_name AS alloc_ddo
               FROM projects p
               JOIN constituencies c ON c.id = p.constituency_id
          LEFT JOIN users u ON u.id = p.submitted_by
          LEFT JOIN allocations a ON a.project_id = p.id
          LEFT JOIN users du ON du.id = a.ddo_user_id
              WHERE p.id = ?"
        );
        $stmt->execute([$singleId]);
        $project = $stmt->fetch();

        if (!$project) {
            json_out(['ok' => false, 'error' => 'Project not found.'], 404);
        }

        // Authorization check
        if ($me['role'] === 'council' && (int)($project['submitted_by'] ?? 0) !== (int)$me['id']) {
            // Check if submitted by this council user
            $subCheck = $pdo->prepare('SELECT submitted_by FROM projects WHERE id = ?');
            $subCheck->execute([$singleId]);
            $subRow = $subCheck->fetch();
            if (!$subRow || (int)$subRow['submitted_by'] !== (int)$me['id']) {
                json_out(['ok' => false, 'error' => 'Access denied.'], 403);
            }
        } elseif ($me['role'] === 'mp') {
            $mpStmt = $pdo->prepare('SELECT constituency_id FROM mp_profiles WHERE user_id = ?');
            $mpStmt->execute([(int)$me['id']]);
            $mpProfile = $mpStmt->fetch();
            if (!$mpProfile || (int)$mpProfile['constituency_id'] !== (int)$project['constituency_id']) {
                json_out(['ok' => false, 'error' => 'This project is outside your parliamentary constituency.'], 403);
            }
        }

        // Fetch comments
        $cStmt = $pdo->prepare(
            "SELECT pc.id,
                    pc.comment AS text,
                    DATE_FORMAT(pc.created_at, '%d %b %Y') AS date,
                    COALESCE(u.full_name, 'MP Office') AS author,
                    r.slug AS author_role
               FROM project_comments pc
          LEFT JOIN users u ON u.id = pc.user_id
          LEFT JOIN roles r ON r.id = u.role_id
              WHERE pc.project_id = ?
           ORDER BY pc.created_at DESC"
        );
        $cStmt->execute([$singleId]);
        $comments = $cStmt->fetchAll();

        // Fetch assignment
        $asgnStmt = $pdo->prepare(
            "SELECT asgn.id,
                    asgn.contractor_id,
                    asgn.assigned_by,
                    asgn.supervisor,
                    asgn.scope,
                    asgn.start_date,
                    asgn.target_date,
                    asgn.status AS assignment_status,
                    asgn.completion_report,
                    DATE_FORMAT(asgn.completion_submitted_at, '%d %b %Y') AS completion_submitted_at,
                    asgn.completion_decision,
                    DATE_FORMAT(asgn.completion_decided_at, '%d %b %Y') AS completion_decided_at,
                    DATE_FORMAT(asgn.created_at, '%d %b %Y') AS assigned_date,
                    u.full_name AS contractor_name,
                    u.email AS contractor_email,
                    kp.company_name AS contractor_company,
                    kp.business_reg_number AS contractor_reg,
                    du.full_name AS assigner_name
               FROM assignments asgn
               JOIN users u ON u.id = asgn.contractor_id
          LEFT JOIN contractor_profiles kp ON kp.user_id = u.id
          LEFT JOIN users du ON du.id = asgn.assigned_by
              WHERE asgn.project_id = ?"
        );
        $asgnStmt->execute([$singleId]);
        $assignment = $asgnStmt->fetch() ?: null;

        // Fetch progress updates
        $updates = [];
        if ($assignment) {
            $updStmt = $pdo->prepare(
                "SELECT pu.id,
                        pu.assignment_id,
                        pu.percentage,
                        pu.note,
                        pu.photo_path,
                        DATE_FORMAT(pu.update_date, '%d %b %Y') AS update_date,
                        pu.update_date AS update_date_raw,
                        DATE_FORMAT(pu.created_at, '%d %b %Y %H:%i') AS created_at,
                        u.full_name AS created_by_name
                   FROM progress_updates pu
              LEFT JOIN users u ON u.id = pu.created_by
                  WHERE pu.assignment_id = ?
               ORDER BY pu.update_date DESC, pu.id DESC"
            );
            $updStmt->execute([(int)$assignment['id']]);
            $updates = $updStmt->fetchAll();
            foreach ($updates as &$u) {
                $u['id'] = (int)$u['id'];
                $u['percentage'] = (int)$u['percentage'];
            }
            unset($u);
        }

        $project['id'] = (int)$project['id'];
        $project['budget'] = (float)$project['budget'];
        $project['beneficiaries'] = $project['beneficiaries'] !== null ? (int)$project['beneficiaries'] : null;
        $project['votes'] = (int)$project['votes'];
        $project['voting_open'] = (int)($project['voting_open'] ?? 1);
        $project['progress'] = (int)($project['progress'] ?? 0);
        $project['statusLabel'] = status_label((string)$project['status']);

        if (!empty($project['alloc_amount'])) {
            $project['allocation'] = [
                'amount' => (float)$project['alloc_amount'],
                'source' => $project['alloc_source'] ?? '',
                'ddo' => $project['alloc_ddo'] ?? 'District Development Officer',
                'reference' => $project['alloc_reference'] ?? '',
                'date' => $project['alloc_date'] ?? '',
                'scope' => $project['alloc_scope'] ?? '',
            ];
        } else {
            $project['allocation'] = null;
        }

        $project['assignment'] = $assignment;
        $project['progress_updates'] = $updates;

        json_out(['ok' => true, 'project' => $project, 'comments' => $comments, 'assignment' => $assignment, 'progress_updates' => $updates]);
    }

    if ($me['role'] === 'mp') {
        // Find MP's constituency
        $mpStmt = $pdo->prepare(
            'SELECT mp.constituency_id, c.name AS constituency_name, c.district, mp.parliamentary_office
               FROM mp_profiles mp
               JOIN constituencies c ON c.id = mp.constituency_id
              WHERE mp.user_id = ?'
        );
        $mpStmt->execute([(int)$me['id']]);
        $mpProfile = $mpStmt->fetch();

        if (!$mpProfile) {
            json_out(['ok' => false, 'error' => 'No constituency assigned to your MP account.'], 403);
        }

        $constituencyId = (int)$mpProfile['constituency_id'];

        $stmt = $pdo->prepare(
            "SELECT p.id,
                    p.reference_no AS reference,
                    p.title,
                    p.area,
                    p.sector,
                    p.budget,
                    p.beneficiaries,
                    p.description,
                    c.name AS constituency,
                    c.code AS constituency_code,
                    COALESCE(u.full_name, 'Council Office') AS proposer,
                    DATE_FORMAT(p.created_at, '%d %b %Y') AS submitted,
                    p.status,
                    p.decision_reason AS reason,
                    DATE_FORMAT(p.decided_at, '%d %b %Y') AS decision_date,
                    p.progress,
                    p.voting_open,
                    DATE_FORMAT(p.voting_opens_at, '%d %b %Y') AS voting_opens_at,
                    DATE_FORMAT(p.voting_closes_at, '%d %b %Y') AS voting_closes_at,
                    (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes,
                    (SELECT COUNT(*) FROM project_comments pc WHERE pc.project_id = p.id) AS comments_count,
                    a.amount AS alloc_amount,
                    a.source AS alloc_source,
                    a.reference AS alloc_reference,
                    DATE_FORMAT(a.allocated_on, '%d %b %Y') AS alloc_date,
                    a.scope AS alloc_scope,
                    du.full_name AS alloc_ddo
               FROM projects p
               JOIN constituencies c ON c.id = p.constituency_id
          LEFT JOIN users u ON u.id = p.submitted_by
          LEFT JOIN allocations a ON a.project_id = p.id
          LEFT JOIN users du ON du.id = a.ddo_user_id
              WHERE p.constituency_id = ?
           ORDER BY p.created_at DESC"
        );
        $stmt->execute([$constituencyId]);
        $projects = $stmt->fetchAll();

        foreach ($projects as &$p) {
            $p['id'] = (int)$p['id'];
            $p['budget'] = (float)$p['budget'];
            $p['beneficiaries'] = $p['beneficiaries'] !== null ? (int)$p['beneficiaries'] : null;
            $p['votes'] = (int)$p['votes'];
            $p['voting_open'] = (int)($p['voting_open'] ?? 1);
            $p['progress'] = (int)($p['progress'] ?? 0);
            $p['statusLabel'] = status_label((string)$p['status']);

            if (!empty($p['alloc_amount'])) {
                $p['allocation'] = [
                    'amount' => (float)$p['alloc_amount'],
                    'source' => $p['alloc_source'] ?? '',
                    'ddo' => $p['alloc_ddo'] ?? 'District Development Officer',
                    'reference' => $p['alloc_reference'] ?? '',
                    'date' => $p['alloc_date'] ?? '',
                    'scope' => $p['alloc_scope'] ?? '',
                ];
            } else {
                $p['allocation'] = null;
            }
        }
        unset($p);

        json_out([
            'ok' => true,
            'constituency' => [
                'id' => $constituencyId,
                'name' => $mpProfile['constituency_name'],
                'district' => $mpProfile['district'],
                'office' => $mpProfile['parliamentary_office'],
            ],
            'projects' => $projects,
        ]);
    }

    if ($me['role'] === 'council') {
        $stmt = $pdo->prepare(
            "SELECT p.id,
                    p.reference_no AS reference,
                    p.title,
                    p.area,
                    p.sector,
                    p.budget,
                    p.beneficiaries,
                    p.description,
                    c.name AS constituency,
                    c.code AS constituency_code,
                    COALESCE(u.full_name, 'Council Office') AS proposer,
                    DATE_FORMAT(p.created_at, '%d %b %Y') AS submitted,
                    p.status,
                    p.decision_reason AS reason,
                    DATE_FORMAT(p.decided_at, '%d %b %Y') AS decision_date,
                    p.progress,
                    p.voting_open,
                    DATE_FORMAT(p.voting_opens_at, '%d %b %Y') AS voting_opens_at,
                    DATE_FORMAT(p.voting_closes_at, '%d %b %Y') AS voting_closes_at,
                    (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes
               FROM projects p
               JOIN constituencies c ON c.id = p.constituency_id
          LEFT JOIN users u ON u.id = p.submitted_by
              WHERE p.submitted_by = ? OR p.submitted_by IS NULL
           ORDER BY p.created_at DESC"
        );
        $stmt->execute([(int)$me['id']]);
        $projects = $stmt->fetchAll();

        foreach ($projects as &$p) {
            $p['id'] = (int)$p['id'];
            $p['budget'] = (float)$p['budget'];
            $p['beneficiaries'] = $p['beneficiaries'] !== null ? (int)$p['beneficiaries'] : null;
            $p['votes'] = (int)$p['votes'];
            $p['voting_open'] = (int)($p['voting_open'] ?? 1);
            $p['progress'] = (int)($p['progress'] ?? 0);
            $p['statusLabel'] = status_label((string)$p['status']);
        }
        unset($p);

        json_out(['ok' => true, 'projects' => $projects]);
    }

    if ($me['role'] === 'ddo') {
        // Fetch active contractors list if requested or always provide contractors list
        $contractors = $pdo->query(
            "SELECT u.id, u.full_name, u.email, kp.company_name, kp.business_reg_number
               FROM users u
               JOIN roles r ON r.id = u.role_id
          LEFT JOIN contractor_profiles kp ON kp.user_id = u.id
              WHERE r.slug = 'contractor' AND u.status = 'active'
           ORDER BY kp.company_name ASC, u.full_name ASC"
        )->fetchAll();

        foreach ($contractors as &$c) {
            $c['id'] = (int)$c['id'];
        }
        unset($c);

        if (isset($_GET['contractors'])) {
            json_out(['ok' => true, 'contractors' => $contractors]);
        }

        $stmt = $pdo->query(
            "SELECT p.id,
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
                    c.district,
                    COALESCE(u.full_name, 'Council Office') AS proposer,
                    DATE_FORMAT(p.created_at, '%d %b %Y') AS submitted,
                    p.status,
                    p.decision_reason AS reason,
                    DATE_FORMAT(p.decided_at, '%d %b %Y') AS decision_date,
                    p.progress,
                    p.voting_open,
                    (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes,
                    a.amount AS alloc_amount,
                    a.source AS alloc_source,
                    a.reference AS alloc_reference,
                    DATE_FORMAT(a.allocated_on, '%d %b %Y') AS alloc_date,
                    a.scope AS alloc_scope,
                    du.full_name AS alloc_ddo,
                    asgn.id AS assignment_id,
                    asgn.contractor_id,
                    asgn.supervisor,
                    asgn.scope AS assignment_scope,
                    asgn.start_date,
                    asgn.target_date,
                    asgn.status AS assignment_status,
                    asgn.completion_report,
                    DATE_FORMAT(asgn.completion_submitted_at, '%d %b %Y') AS completion_submitted_at,
                    asgn.completion_decision,
                    DATE_FORMAT(asgn.completion_decided_at, '%d %b %Y') AS completion_decided_at,
                    DATE_FORMAT(asgn.created_at, '%d %b %Y') AS assigned_date,
                    cu.full_name AS contractor_name,
                    cu.email AS contractor_email,
                    kp.company_name AS contractor_company,
                    (SELECT note FROM progress_updates pu WHERE pu.assignment_id = asgn.id ORDER BY pu.update_date DESC, pu.id DESC LIMIT 1) AS latest_progress_note,
                    (SELECT DATE_FORMAT(pu.update_date, '%d %b %Y') FROM progress_updates pu WHERE pu.assignment_id = asgn.id ORDER BY pu.update_date DESC, pu.id DESC LIMIT 1) AS latest_progress_date
               FROM projects p
               JOIN constituencies c ON c.id = p.constituency_id
          LEFT JOIN users u ON u.id = p.submitted_by
          LEFT JOIN allocations a ON a.project_id = p.id
          LEFT JOIN users du ON du.id = a.ddo_user_id
          LEFT JOIN assignments asgn ON asgn.project_id = p.id
          LEFT JOIN users cu ON cu.id = asgn.contractor_id
          LEFT JOIN contractor_profiles kp ON kp.user_id = cu.id
              WHERE p.status IN ('accepted', 'progress', 'completed')
           ORDER BY p.created_at DESC"
        );
        $projects = $stmt->fetchAll();

        foreach ($projects as &$p) {
            $p['id'] = (int)$p['id'];
            $p['budget'] = (float)$p['budget'];
            $p['beneficiaries'] = $p['beneficiaries'] !== null ? (int)$p['beneficiaries'] : null;
            $p['votes'] = (int)$p['votes'];
            $p['progress'] = (int)($p['progress'] ?? 0);
            $p['statusLabel'] = status_label((string)$p['status']);
            $p['supplier'] = $p['contractor_company'] ?: ($p['contractor_name'] ?: '');
            $p['supervisor'] = $p['supervisor'] ?: '';
            $p['start'] = $p['start_date'] ?: '';
            $p['end'] = $p['target_date'] ?: '';
            $p['report'] = $p['latest_progress_note'] ?: ($p['completion_report'] ?: '');
            $p['result'] = $p['description'] ?: '';

            if (!empty($p['alloc_amount'])) {
                $p['allocation'] = [
                    'amount' => (float)$p['alloc_amount'],
                    'source' => $p['alloc_source'] ?? '',
                    'ddo' => $p['alloc_ddo'] ?? 'District Development Officer',
                    'reference' => $p['alloc_reference'] ?? '',
                    'date' => $p['alloc_date'] ?? '',
                    'scope' => $p['alloc_scope'] ?? '',
                ];
            } else {
                $p['allocation'] = null;
            }

            if (!empty($p['assignment_id'])) {
                $p['assignment'] = [
                    'id' => (int)$p['assignment_id'],
                    'contractor_id' => (int)$p['contractor_id'],
                    'contractor_name' => $p['contractor_name'],
                    'contractor_company' => $p['contractor_company'],
                    'supervisor' => $p['supervisor'],
                    'scope' => $p['assignment_scope'],
                    'start_date' => $p['start_date'],
                    'target_date' => $p['target_date'],
                    'status' => $p['assignment_status'],
                    'completion_report' => $p['completion_report'],
                    'completion_submitted_at' => $p['completion_submitted_at'],
                    'completion_decision' => $p['completion_decision'],
                    'completion_decided_at' => $p['completion_decided_at'],
                    'assigned_date' => $p['assigned_date'],
                ];
            } else {
                $p['assignment'] = null;
            }
        }
        unset($p);

        json_out(['ok' => true, 'projects' => $projects, 'contractors' => $contractors]);
    }

    if ($me['role'] === 'contractor') {
        $stmt = $pdo->prepare(
            "SELECT p.id,
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
                    c.district,
                    COALESCE(u.full_name, 'Council Office') AS proposer,
                    DATE_FORMAT(p.created_at, '%d %b %Y') AS submitted,
                    p.status,
                    p.decision_reason AS reason,
                    DATE_FORMAT(p.decided_at, '%d %b %Y') AS decision_date,
                    p.progress,
                    (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes,
                    a.amount AS alloc_amount,
                    a.source AS alloc_source,
                    a.reference AS alloc_reference,
                    DATE_FORMAT(a.allocated_on, '%d %b %Y') AS alloc_date,
                    a.scope AS alloc_scope,
                    du.full_name AS alloc_ddo,
                    asgn.id AS assignment_id,
                    asgn.contractor_id,
                    asgn.supervisor,
                    asgn.scope AS assignment_scope,
                    asgn.start_date,
                    asgn.target_date,
                    asgn.status AS assignment_status,
                    asgn.completion_report,
                    DATE_FORMAT(asgn.completion_submitted_at, '%d %b %Y') AS completion_submitted_at,
                    asgn.completion_decision,
                    DATE_FORMAT(asgn.completion_decided_at, '%d %b %Y') AS completion_decided_at,
                    DATE_FORMAT(asgn.created_at, '%d %b %Y') AS assigned_date,
                    (SELECT note FROM progress_updates pu WHERE pu.assignment_id = asgn.id ORDER BY pu.update_date DESC, pu.id DESC LIMIT 1) AS latest_progress_note,
                    (SELECT DATE_FORMAT(pu.update_date, '%d %b %Y') FROM progress_updates pu WHERE pu.assignment_id = asgn.id ORDER BY pu.update_date DESC, pu.id DESC LIMIT 1) AS latest_progress_date
               FROM assignments asgn
               JOIN projects p ON p.id = asgn.project_id
               JOIN constituencies c ON c.id = p.constituency_id
          LEFT JOIN users u ON u.id = p.submitted_by
          LEFT JOIN allocations a ON a.project_id = p.id
          LEFT JOIN users du ON du.id = a.ddo_user_id
              WHERE asgn.contractor_id = ?
           ORDER BY asgn.created_at DESC"
        );
        $stmt->execute([(int)$me['id']]);
        $projects = $stmt->fetchAll();

        foreach ($projects as &$p) {
            $p['id'] = (int)$p['id'];
            $p['budget'] = (float)$p['budget'];
            $p['beneficiaries'] = $p['beneficiaries'] !== null ? (int)$p['beneficiaries'] : null;
            $p['votes'] = (int)$p['votes'];
            $p['progress'] = (int)($p['progress'] ?? 0);
            $p['statusLabel'] = status_label((string)$p['status']);
            $p['start'] = $p['start_date'] ?: '';
            $p['end'] = $p['target_date'] ?: '';
            $p['report'] = $p['latest_progress_note'] ?: ($p['completion_report'] ?: '');
            $p['result'] = $p['description'] ?: '';

            if (!empty($p['alloc_amount'])) {
                $p['allocation'] = [
                    'amount' => (float)$p['alloc_amount'],
                    'source' => $p['alloc_source'] ?? '',
                    'ddo' => $p['alloc_ddo'] ?? 'District Development Officer',
                    'reference' => $p['alloc_reference'] ?? '',
                    'date' => $p['alloc_date'] ?? '',
                    'scope' => $p['alloc_scope'] ?? '',
                ];
            } else {
                $p['allocation'] = null;
            }

            $p['assignment'] = [
                'id' => (int)$p['assignment_id'],
                'contractor_id' => (int)$p['contractor_id'],
                'supervisor' => $p['supervisor'],
                'scope' => $p['assignment_scope'],
                'start_date' => $p['start_date'],
                'target_date' => $p['target_date'],
                'status' => $p['assignment_status'],
                'completion_report' => $p['completion_report'],
                'completion_submitted_at' => $p['completion_submitted_at'],
                'completion_decision' => $p['completion_decision'],
                'completion_decided_at' => $p['completion_decided_at'],
                'assigned_date' => $p['assigned_date'],
            ];
        }
        unset($p);

        json_out(['ok' => true, 'projects' => $projects]);
    }

    // Default for citizen users and general listing
    $citizenConstId = null;
    if ($me['role'] === 'citizen') {
        $cpStmt = $pdo->prepare('SELECT constituency_id FROM citizen_profiles WHERE user_id = ?');
        $cpStmt->execute([(int)$me['id']]);
        $cpRow = $cpStmt->fetch();
        if ($cpRow) {
            $citizenConstId = (int)$cpRow['constituency_id'];
        }
    }

    $q = "SELECT p.id,
                 p.reference_no AS reference,
                 p.title,
                 p.area,
                 p.sector,
                 p.budget,
                 p.beneficiaries,
                 p.description,
                 c.name AS constituency,
                 c.code AS constituency_code,
                 COALESCE(u.full_name, 'Council Office') AS proposer,
                 DATE_FORMAT(p.created_at, '%d %b %Y') AS submitted,
                 p.status,
                 p.decision_reason AS reason,
                 DATE_FORMAT(p.decided_at, '%d %b %Y') AS decision_date,
                 p.progress,
                 p.voting_open,
                 DATE_FORMAT(p.voting_opens_at, '%d %b %Y') AS voting_opens_at,
                 DATE_FORMAT(p.voting_closes_at, '%d %b %Y') AS voting_closes_at,
                 (SELECT COUNT(*) FROM votes v WHERE v.project_id = p.id) AS votes,
                 (SELECT COUNT(*) FROM votes v2 WHERE v2.project_id = p.id AND v2.user_id = ?) AS user_voted
            FROM projects p
            JOIN constituencies c ON c.id = p.constituency_id
       LEFT JOIN users u ON u.id = p.submitted_by";

    if ($citizenConstId !== null) {
        $q .= ' WHERE p.constituency_id = ?';
        $stmt = $pdo->prepare($q . ' ORDER BY p.created_at DESC');
        $stmt->execute([(int)$me['id'], $citizenConstId]);
    } else {
        $stmt = $pdo->prepare($q . ' ORDER BY p.created_at DESC');
        $stmt->execute([(int)$me['id']]);
    }
    $projects = $stmt->fetchAll();

    foreach ($projects as &$p) {
        $p['id'] = (int)$p['id'];
        $p['budget'] = (float)$p['budget'];
        $p['beneficiaries'] = $p['beneficiaries'] !== null ? (int)$p['beneficiaries'] : null;
        $p['votes'] = (int)$p['votes'];
        $p['user_voted'] = !empty($p['user_voted']);
        $p['voting_open'] = (int)($p['voting_open'] ?? 1);
        $p['progress'] = (int)($p['progress'] ?? 0);
        $p['statusLabel'] = status_label((string)$p['status']);
    }
    unset($p);

    json_out(['ok' => true, 'projects' => $projects]);
}

/* ------------------------------------------------------------------ */
/* POST: Submit project, update decision, or post comment             */
/* ------------------------------------------------------------------ */
require_post_with_csrf();

$me = current_user();
if (!$me) {
    json_out(['ok' => false, 'error' => 'Sign in to perform this action.'], 401);
}

$in = read_json();
$action = trim((string)($in['action'] ?? ''));

/* ------------------------------------------------------------------ */
/* Council Action: Start / Stop Voting for a Project                  */
/* ------------------------------------------------------------------ */
if ($me['role'] === 'council' && in_array($action, ['voting', 'start_voting', 'stop_voting', 'toggle_voting'], true)) {
    $projectId = isset($in['project_id']) && is_numeric($in['project_id']) ? (int)$in['project_id'] : 0;
    if ($projectId <= 0) {
        json_out(['ok' => false, 'error' => 'Valid project ID is required.'], 422);
    }

    $pStmt = $pdo->prepare('SELECT id, submitted_by, voting_open, title FROM projects WHERE id = ?');
    $pStmt->execute([$projectId]);
    $proj = $pStmt->fetch();

    if (!$proj) {
        json_out(['ok' => false, 'error' => 'Project not found.'], 404);
    }

    if ($action === 'start_voting') {
        $votingOpen = 1;
    } elseif ($action === 'stop_voting') {
        $votingOpen = 0;
    } elseif (isset($in['voting_open'])) {
        $votingOpen = !empty($in['voting_open']) ? 1 : 0;
    } elseif (isset($in['open'])) {
        $votingOpen = !empty($in['open']) ? 1 : 0;
    } else {
        $votingOpen = empty($proj['voting_open']) ? 1 : 0;
    }

    if ($votingOpen === 1) {
        $upd = $pdo->prepare('UPDATE projects SET voting_open = 1, voting_opens_at = COALESCE(voting_opens_at, CURDATE()), voting_closes_at = NULL WHERE id = ?');
        $upd->execute([$projectId]);
        log_event((int)$me['id'], $me['email'], 'council_start_voting');
        $message = 'Voting has been started for this project.';
    } else {
        $upd = $pdo->prepare('UPDATE projects SET voting_open = 0, voting_closes_at = CURDATE() WHERE id = ?');
        $upd->execute([$projectId]);
        log_event((int)$me['id'], $me['email'], 'council_stop_voting');
        $message = 'Voting has been stopped for this project.';
    }

    $vStmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE project_id = ?');
    $vStmt->execute([$projectId]);
    $votes = (int)$vStmt->fetchColumn();

    json_out([
        'ok' => true,
        'message' => $message,
        'project_id' => $projectId,
        'voting_open' => $votingOpen,
        'votes' => $votes,
    ]);
}

/* ------------------------------------------------------------------ */
/* MP Actions: Decision (approve/reject/status update) & Comments     */
/* ------------------------------------------------------------------ */
if ($me['role'] === 'mp' || $action === 'decision' || $action === 'comment') {
    if ($me['role'] !== 'mp') {
        json_out(['ok' => false, 'error' => 'Only Members of Parliament can record decisions or MP comments.'], 403);
    }

    // Verify MP profile and constituency
    $mpStmt = $pdo->prepare('SELECT constituency_id FROM mp_profiles WHERE user_id = ?');
    $mpStmt->execute([(int)$me['id']]);
    $mpProfile = $mpStmt->fetch();
    if (!$mpProfile) {
        json_out(['ok' => false, 'error' => 'No parliamentary constituency linked to your account.'], 403);
    }
    $constituencyId = (int)$mpProfile['constituency_id'];

    $projectId = isset($in['project_id']) && is_numeric($in['project_id']) ? (int)$in['project_id'] : 0;
    if ($projectId <= 0) {
        json_out(['ok' => false, 'error' => 'Valid project ID is required.'], 422);
    }

    // Check project belongs to MP's constituency
    $pStmt = $pdo->prepare('SELECT id, status, constituency_id, title FROM projects WHERE id = ?');
    $pStmt->execute([$projectId]);
    $proj = $pStmt->fetch();

    if (!$proj) {
        json_out(['ok' => false, 'error' => 'Project not found.'], 404);
    }
    if ((int)$proj['constituency_id'] !== $constituencyId) {
        json_out(['ok' => false, 'error' => 'You can only review projects within your constituency.'], 403);
    }

    if ($action === 'comment') {
        $commentText = trim((string)($in['text'] ?? ''));
        if ($commentText === '' || text_len($commentText) > 2000) {
            json_out(['ok' => false, 'error' => 'Comment cannot be empty (up to 2000 characters).'], 422);
        }

        $insComment = $pdo->prepare('INSERT INTO project_comments (project_id, user_id, comment) VALUES (?, ?, ?)');
        $insComment->execute([$projectId, (int)$me['id'], $commentText]);

        log_event((int)$me['id'], $me['email'], 'mp_project_comment');

        json_out([
            'ok' => true,
            'message' => 'Comment added successfully.',
            'comment' => [
                'id' => (int)$pdo->lastInsertId(),
                'text' => $commentText,
                'author' => $me['full_name'],
                'date' => date('d M Y'),
            ],
        ]);
    }

    if ($action === 'decision' || $action === 'status_change') {
        $status = trim((string)($in['status'] ?? ($in['decision'] ?? '')));
        $reason = trim((string)($in['reason'] ?? ''));
        $comment = trim((string)($in['comment'] ?? ''));

        $validStatuses = ['submitted', 'review', 'accepted', 'denied', 'progress', 'completed'];
        if (!in_array($status, $validStatuses, true)) {
            json_out(['ok' => false, 'error' => 'Invalid status selected.'], 422);
        }

        if ($reason === '' || text_len($reason) > 2000) {
            json_out(['ok' => false, 'error' => 'Please provide a reason for this decision or status change (up to 2000 characters).'], 422);
        }

        // Update project status, decision reason, decided_by, decided_at
        $upd = $pdo->prepare(
            'UPDATE projects
                SET status = ?,
                    decision_reason = ?,
                    decided_by = ?,
                    decided_at = NOW()
              WHERE id = ?'
        );
        $upd->execute([$status, $reason, (int)$me['id'], $projectId]);

        // If a comment was supplied, insert into project_comments
        if ($comment !== '') {
            if (text_len($comment) > 2000) {
                json_out(['ok' => false, 'error' => 'Comment must be 2000 characters or fewer.'], 422);
            }
            $insComment = $pdo->prepare('INSERT INTO project_comments (project_id, user_id, comment) VALUES (?, ?, ?)');
            $insComment->execute([$projectId, (int)$me['id'], $comment]);
        }

        log_event((int)$me['id'], $me['email'], 'mp_project_decision_' . $status);

        json_out([
            'ok' => true,
            'message' => 'Decision and reason recorded successfully.',
            'status' => $status,
            'statusLabel' => status_label($status),
            'reason' => $reason,
        ]);
    }

    json_out(['ok' => false, 'error' => 'Unknown action.'], 422);
}

/* ------------------------------------------------------------------ */
/* DDO Actions: Assign Contractor, Update Allocation                  */
/* ------------------------------------------------------------------ */
if ($me['role'] === 'ddo' && in_array($action, ['assign', 'assign_contractor', 'allocate'], true)) {
    $projectId = isset($in['project_id']) && is_numeric($in['project_id']) ? (int)$in['project_id'] : 0;
    if ($projectId <= 0) {
        json_out(['ok' => false, 'error' => 'Valid project ID is required.'], 422);
    }

    $pStmt = $pdo->prepare('SELECT id, status, title FROM projects WHERE id = ?');
    $pStmt->execute([$projectId]);
    $proj = $pStmt->fetch();
    if (!$proj) {
        json_out(['ok' => false, 'error' => 'Project not found.'], 404);
    }

    if ($action === 'assign' || $action === 'assign_contractor') {
        $contractorId = isset($in['contractor_id']) && is_numeric($in['contractor_id']) ? (int)$in['contractor_id'] : 0;
        $supervisor = trim((string)($in['supervisor'] ?? ''));
        $scope = trim((string)($in['scope'] ?? ''));
        $startDate = trim((string)($in['start_date'] ?? ($in['start'] ?? '')));
        $targetDate = trim((string)($in['target_date'] ?? ($in['end'] ?? '')));
        $asgnStatus = trim((string)($in['status'] ?? 'assigned'));

        if ($contractorId <= 0) {
            json_out(['ok' => false, 'error' => 'Please select an available contractor.'], 422);
        }

        // Verify contractor user exists, has role 'contractor', and status 'active'
        $cCheck = $pdo->prepare(
            'SELECT u.id, u.full_name, u.status, r.slug AS role
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.id = ?'
        );
        $cCheck->execute([$contractorId]);
        $cUser = $cCheck->fetch();

        if (!$cUser || $cUser['role'] !== 'contractor') {
            json_out(['ok' => false, 'error' => 'Selected user is not a contractor.'], 422);
        }
        if ($cUser['status'] !== 'active') {
            json_out(['ok' => false, 'error' => 'Selected contractor account is not active.'], 422);
        }

        if (!in_array($asgnStatus, ['assigned', 'active', 'completed', 'blocked'], true)) {
            $asgnStatus = 'assigned';
        }

        $startDateVal = $startDate !== '' ? $startDate : null;
        $targetDateVal = $targetDate !== '' ? $targetDate : null;

        // Only email when this is a new assignment or the contractor changed,
        // not when the DDO just edits dates or status.
        $prev = $pdo->prepare('SELECT contractor_id FROM assignments WHERE project_id = ?');
        $prev->execute([$projectId]);
        $prevContractorId = $prev->fetchColumn();   // false = no assignment yet
        $isNewAssignment = ($prevContractorId === false || (int)$prevContractorId !== $contractorId);

        $upsert = $pdo->prepare(
            'INSERT INTO assignments (
                project_id, contractor_id, assigned_by, supervisor, scope, start_date, target_date, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                contractor_id = VALUES(contractor_id),
                assigned_by   = VALUES(assigned_by),
                supervisor    = VALUES(supervisor),
                scope         = VALUES(scope),
                start_date    = VALUES(start_date),
                target_date   = VALUES(target_date),
                status        = VALUES(status)'
        );
        $upsert->execute([
            $projectId,
            $contractorId,
            (int)$me['id'],
            $supervisor ?: null,
            $scope ?: null,
            $startDateVal,
            $targetDateVal,
            $asgnStatus,
        ]);

        if ($proj['status'] === 'accepted') {
            $pdo->prepare("UPDATE projects SET status = 'progress' WHERE id = ?")->execute([$projectId]);
        }

        log_event((int)$me['id'], $me['email'], 'ddo_assign_contractor');

        if ($isNewAssignment) {
            notify_contractor_assigned($pdo, $projectId, $contractorId, [
                'supervisor' => $supervisor,
                'scope' => $scope,
                'start_date' => $startDateVal ?? '',
                'target_date' => $targetDateVal ?? '',
                'assigned_by' => $me['full_name'],
            ]);
        }

        json_out([
            'ok' => true,
            'message' => 'Project successfully assigned to contractor.',
            'project_id' => $projectId,
            'contractor_id' => $contractorId,
        ]);
    }
}

/* ------------------------------------------------------------------ */
/* Contractor Actions: Submit Progress Update                         */
/* ------------------------------------------------------------------ */
if ($me['role'] === 'contractor' && in_array($action, ['progress', 'progress_update', 'update_progress'], true)) {
    $projectId = isset($in['project_id']) && is_numeric($in['project_id']) ? (int)$in['project_id'] : 0;
    if ($projectId <= 0) {
        json_out(['ok' => false, 'error' => 'Valid project ID is required.'], 422);
    }

    // Verify assignment belongs to this contractor
    $asgnStmt = $pdo->prepare('SELECT id, status FROM assignments WHERE project_id = ? AND contractor_id = ?');
    $asgnStmt->execute([$projectId, (int)$me['id']]);
    $asgn = $asgnStmt->fetch();

    if (!$asgn) {
        json_out(['ok' => false, 'error' => 'You are not assigned to this project.'], 403);
    }

    $assignmentId = (int)$asgn['id'];
    $percentage = isset($in['percentage']) && is_numeric($in['percentage']) ? (int)$in['percentage'] : (isset($in['progress']) && is_numeric($in['progress']) ? (int)$in['progress'] : null);
    $note = trim((string)($in['note'] ?? ($in['report'] ?? '')));
    $updateDate = trim((string)($in['update_date'] ?? ($in['date'] ?? '')));

    if ($percentage === null || $percentage < 0 || $percentage > 100) {
        json_out(['ok' => false, 'error' => 'Please provide a valid progress percentage between 0 and 100.'], 422);
    }
    if ($note === '' || text_len($note) > 3000) {
        json_out(['ok' => false, 'error' => 'Progress update note/report is required (up to 3000 characters).'], 422);
    }

    $updateDateVal = ($updateDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $updateDate)) ? $updateDate : date('Y-m-d');

    // Insert progress update record
    $insProg = $pdo->prepare(
        'INSERT INTO progress_updates (assignment_id, percentage, note, update_date, created_by)
         VALUES (?, ?, ?, ?, ?)'
    );
    $insProg->execute([$assignmentId, $percentage, $note, $updateDateVal, (int)$me['id']]);

    // Update project progress and status
    $newStatus = ($percentage >= 100) ? 'completed' : 'progress';
    $asgnStatus = ($percentage >= 100) ? 'completed' : 'active';

    $pdo->prepare('UPDATE projects SET progress = ?, status = ? WHERE id = ?')
        ->execute([$percentage, $newStatus, $projectId]);

    $pdo->prepare('UPDATE assignments SET status = ?, completion_report = COALESCE(?, completion_report) WHERE id = ?')
        ->execute([$asgnStatus, $percentage >= 100 ? $note : null, $assignmentId]);

    log_event((int)$me['id'], $me['email'], 'contractor_progress_update');

    json_out([
        'ok' => true,
        'message' => 'Progress update saved successfully.',
        'percentage' => $percentage,
        'status' => $newStatus,
        'statusLabel' => status_label($newStatus),
    ]);
}

/* ------------------------------------------------------------------ */
/* Council Action: Submit a project proposal                          */
/* ------------------------------------------------------------------ */
if ($me['role'] !== 'council') {
    json_out(['ok' => false, 'error' => 'Only District Council accounts may submit projects.'], 403);
}

$title = trim((string)($in['title'] ?? ''));
$area = trim((string)($in['area'] ?? ''));
$sector = trim((string)($in['sector'] ?? ''));
$constituency = trim((string)($in['constituency'] ?? ''));
$budgetRaw = $in['budget'] ?? null;
$beneficiaries = $in['beneficiaries'] ?? null;
$description = trim((string)($in['description'] ?? ''));

$validSectors = ['Water', 'Education', 'Health', 'Roads', 'Agriculture', 'Markets', 'Energy', 'Community'];

if ($title === '' || text_len($title) > 180) {
    json_out(['ok' => false, 'error' => 'Project title is required (up to 180 characters).'], 422);
}
if ($area === '' || text_len($area) > 150) {
    json_out(['ok' => false, 'error' => 'Village or area is required (up to 150 characters).'], 422);
}
if (!in_array($sector, $validSectors, true)) {
    json_out(['ok' => false, 'error' => 'Please select a valid sector.'], 422);
}
if ($constituency === '') {
    json_out(['ok' => false, 'error' => 'Please select a constituency.'], 422);
}

// Find constituency by code or id
$cStmt = $pdo->prepare('SELECT id, code, district FROM constituencies WHERE code = ? OR id = ? LIMIT 1');
$cStmt->execute([$constituency, is_numeric($constituency) ? (int)$constituency : 0]);
$cRow = $cStmt->fetch();

if (!$cRow) {
    json_out(['ok' => false, 'error' => 'Unknown constituency selected.'], 422);
}
$constituencyId = (int)$cRow['id'];

if (!is_numeric($budgetRaw) || (float)$budgetRaw <= 0) {
    json_out(['ok' => false, 'error' => 'Please enter a valid estimated budget.'], 422);
}
$budget = round((float)$budgetRaw, 2);

$beneficiariesVal = null;
if ($beneficiaries !== null && $beneficiaries !== '') {
    if (!is_numeric($beneficiaries) || (int)$beneficiaries < 1) {
        json_out(['ok' => false, 'error' => 'Beneficiaries must be a positive whole number.'], 422);
    }
    $beneficiariesVal = (int)$beneficiaries;
}

if ($description === '' || text_len($description) > 5000) {
    json_out(['ok' => false, 'error' => 'Please provide a description (up to 5000 characters).'], 422);
}

// Generate unique reference number: CDF-<DISTRICT/PREFIX>-<YEAR>-<RANDOM>
$districtCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $cRow['district'] ?: 'MW'), 0, 2));
$year = date('Y');

$referenceNo = '';
for ($attempts = 0; $attempts < 10; $attempts++) {
    $candidate = sprintf('CDF-%s-%s-%04d', $districtCode, $year, random_int(1000, 9999));
    $check = $pdo->prepare('SELECT id FROM projects WHERE reference_no = ?');
    $check->execute([$candidate]);
    if (!$check->fetch()) {
        $referenceNo = $candidate;
        break;
    }
}

if ($referenceNo === '') {
    $referenceNo = 'CDF-' . $districtCode . '-' . $year . '-' . bin2hex(random_bytes(3));
}

$insert = $pdo->prepare(
    'INSERT INTO projects (
        reference_no, title, area, sector, budget, beneficiaries, description,
        constituency_id, submitted_by, status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, \'submitted\'
    )'
);

$insert->execute([
    $referenceNo,
    $title,
    $area,
    $sector,
    $budget,
    $beneficiariesVal,
    $description,
    $constituencyId,
    (int)$me['id']
]);

$newId = (int)$pdo->lastInsertId();

log_event((int)$me['id'], $me['email'], 'project_submitted');

json_out([
    'ok' => true,
    'id' => $newId,
    'reference' => $referenceNo,
], 201);