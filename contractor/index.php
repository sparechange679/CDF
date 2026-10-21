<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('contractor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contractor Workspace | CDF Malawi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="ddo.css" rel="stylesheet">
</head>
<body class="ddo-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>
                <div>Contractor Workspace</div>
                <small class="text-muted">Contractor delivery</small>
            </div>
        </a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#ddoNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="ddoNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li><a class="nav-link active" href="index.php">Overview</a></li>
                <li><a class="nav-link" href="projects.php">Assigned projects</a></li>
                <li><a class="nav-link" href="reports.php">Reports &amp; results</a></li>
                <li><a class='nav-link' href='quotations.php'>Quotations</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="ddo-hero">
    <div class="container">
        <div class="ddo-eyebrow mb-2">Contractor Delivery Oversight · 2026</div>
        <h1>Deliver assigned CDF projects and report live progress.</h1>
        <p class="lead mb-4">
            Manage your assigned projects, log stage completions, submit regular progress updates and notes, and report project outcomes to the DDO.
        </p>
        <a class="btn btn-light fw-semibold" href="projects.php">
            <i class="bi bi-kanban me-2"></i>Open assigned projects
        </a>
    </div>
</header>

<main class="container py-5">

    <div class="row g-3 mb-4" id="stats"></div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="ddo-panel">
                <div class="ddo-panel-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 mb-1">Your assigned projects</h2>
                        <p class="text-muted small mb-0">Projects assigned to your contractor account by the District Delivery Office.</p>
                    </div>
                    <a href="projects.php" class="btn btn-outline-gov btn-sm">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table ddo-table mb-0">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Budget</th>
                                <th>Votes</th>
                                <th>Delivery status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="rows">
                            <tr><td colspan="5" class="ddo-empty">Loading projects…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="ddo-panel h-100">
                <div class="ddo-panel-header">
                    <h2 class="h5 mb-1">Contractor controls</h2>
                    <p class="text-muted small mb-0">Keep every assigned project updated.</p>
                </div>
                <div class="ddo-panel-body d-grid gap-2">
                    <a href="projects.php" class="btn btn-cdf text-start">
                        <i class="bi bi-send-check me-2"></i>Update project progress
                    </a>
                    <a href="reports.php" class="btn btn-outline-gov text-start">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i>View reports &amp; timeline
                    </a>
                    <div class="ddo-callout mt-3 small">
                        <strong>Logged in as contractor</strong><br>
                        <span><?= htmlspecialchars($me['full_name']) ?></span><br>
                        <span class="text-muted">Progress updates submitted here are instantly reflected on the DDO and public tracking portals.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">
            <p class="mb-0">Contractor Workspace · <a href="../index.php">Citizen portal</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src='ddo.js?v=20261020'></script>
<script>
    DDO.load(function (err, a) {
        if (err || !a) {
            document.getElementById('rows').innerHTML = '<tr><td colspan="5" class="ddo-empty">Unable to load assigned projects.</td></tr>';
            return;
        }

        var ranked = DDO.priority(a);

        var allocated = a.reduce(function (s, p) {
            return s + (p.allocation ? Number(p.allocation.amount || 0) : Number(p.budget || 0));
        }, 0);

        var active = a.filter(function (p) {
            return p.status === 'progress' || (p.assignment && (p.assignment.status === 'active' || p.assignment.status === 'assigned'));
        }).length;

        var done = a.filter(function (p) {
            return p.status === 'completed' || (p.assignment && p.assignment.status === 'completed');
        }).length;

        document.getElementById('stats').innerHTML = [
            ['bi-kanban', '' + a.length, 'Assigned projects', 'blue'],
            ['bi-wallet2', DDO.money(allocated), 'Total budget', 'green'],
            ['bi-activity', '' + active, 'In progress', 'gold'],
            ['bi-check-circle', '' + done, 'Completed', 'red']
        ].map(function (x) {
            return '<div class="col-6 col-lg-3">' +
                '<div class="ddo-panel ddo-stat ' + x[3] + '">' +
                '<i class="bi ' + x[0] + ' text-muted"></i>' +
                '<div class="number">' + x[1] + '</div>' +
                '<div class="label">' + x[2] + '</div>' +
                '</div>' +
                '</div>';
        }).join('');

        document.getElementById('rows').innerHTML = ranked.slice(0, 5).map(DDO.row).join('') ||
            '<tr><td colspan="5" class="ddo-empty">No projects currently assigned to your account.</td></tr>';
    });
</script>
</body>
</html>
