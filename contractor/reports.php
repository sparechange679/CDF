<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('contractor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reports and Results | Contractor Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="ddo.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, nav, .gov-topbar, footer {
                display: none !important
            }

            .ddo-shell {
                background: #fff
            }

            .ddo-panel {
                box-shadow: none
            }
        }
    </style>
</head>
<body class="ddo-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>Contractor Workspace<small class="text-muted d-block">Contractor delivery</small></div>
        </a>
        <button class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#ddoNav'>
            <span class='navbar-toggler-icon'></span>
        </button>
        <div class='collapse navbar-collapse' id='ddoNav'>
            <ul class='navbar-nav ms-auto align-items-lg-center'>
                <li><a class='nav-link' href='index.php'>Overview</a></li>
                <li><a class='nav-link' href='projects.php'>Assigned projects</a></li>
                <li><a class='nav-link active' href='reports.php'>Reports &amp; results</a></li>
                <li><a class='nav-link' href='quotations.php'>Quotations</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="ddo-eyebrow text-primary">Contractor delivery reports</div>
            <h1 class="h2">Project delivery reports and results</h1>
            <p class="text-muted mb-0">Summary of all projects assigned to your contractor account and submitted progress records.</p>
        </div>
        <button class="btn btn-cdf btn-sm no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i> Generate / print</button>
    </div>
    <div class="row g-3 mb-4" id="stats"></div>
    <div class="ddo-panel">
        <div class="ddo-panel-header">
            <h2 class="h5 mb-1">Assigned project delivery results</h2>
            <p class="text-muted small mb-0">Latest progress updates and delivery status for each project.</p>
        </div>
        <div class="table-responsive">
            <table class="table ddo-table mb-0">
                <thead>
                <tr>
                    <th>Project</th>
                    <th>Supervisor</th>
                    <th>Timeline</th>
                    <th>Progress</th>
                    <th>Latest Report &amp; Scope</th>
                </tr>
                </thead>
                <tbody id="rows">
                    <tr><td colspan="5" class="ddo-empty">Loading reports…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-4">Generated: <?= date('d M Y') ?> · CDF Contractor Progress System.</p>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">Contractor Workspace · <a href="../index.php">Citizen portal</a></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src='ddo.js?v=20261020'></script>
<script>
    DDO.load(function (err, a) {
        if (err || !a) {
            document.getElementById('rows').innerHTML = '<tr><td colspan="5" class="ddo-empty">Unable to load reports.</td></tr>';
            return;
        }

        var funds = a.reduce(function (s, p) {
            return s + (p.allocation ? Number(p.allocation.amount || 0) : Number(p.budget || 0));
        }, 0);
        var done = a.filter(function (p) {
            return p.status === 'completed' || (p.assignment && p.assignment.status === 'completed');
        }).length;
        var inProg = a.filter(function (p) {
            return p.status === 'progress' || (p.assignment && p.assignment.status === 'active');
        }).length;

        document.getElementById('stats').innerHTML = [
            ['bi-kanban', a.length, 'Assigned projects', 'blue'],
            ['bi-wallet2', DDO.money(funds), 'Budget total', 'green'],
            ['bi-activity', inProg, 'In progress', 'gold'],
            ['bi-check-circle', done, 'Completed', 'red']
        ].map(function (x) {
            return '<div class="col-6 col-lg-3"><div class="ddo-panel ddo-stat ' + x[3] + '"><i class="bi ' + x[0] + ' text-muted"></i><div class="number">' + x[1] + '</div><div class="label">' + x[2] + '</div></div></div>';
        }).join('');

        document.getElementById('rows').innerHTML = a.map(function (p) {
            var prog = Number(p.progress || 0);
            var supervisor = p.supervisor || (p.assignment ? p.assignment.supervisor : 'Not assigned');
            var timeline = (p.start || (p.assignment ? p.assignment.start_date : 'Not set')) + '<br>' + (p.end || (p.assignment ? p.assignment.target_date : 'Not set'));
            var report = p.report || (p.assignment ? (p.assignment.completion_report || 'Pending') : 'Pending');

            return '<tr>' +
                '<td><strong>' + DDO.esc(p.title) + '</strong><div class="ddo-kicker">' + DDO.esc(p.area) + ' · ' + DDO.badge(p) + '</div></td>' +
                '<td class="small">' + DDO.esc(supervisor) + '</td>' +
                '<td class="small">' + timeline + '</td>' +
                '<td><strong>' + prog + '%</strong><div class="ddo-progress mt-1"><div class="progress-bar" style="width:' + prog + '%"></div></div></td>' +
                '<td class="small"><strong>Latest report:</strong> ' + DDO.esc(report) + '<br><strong>Scope:</strong> ' + DDO.esc(p.description || 'Pending') + '</td>' +
                '</tr>';
        }).join('') || '<tr><td colspan="5" class="ddo-empty">No assigned project reports are available.</td></tr>';
    });
</script>
</body>
</html>