<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me  = current_user();
$tab = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Decisions | DC Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="vdc.css" rel="stylesheet">
</head>
<body class="vdc-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top vdc-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="vdc-brand-mark"><i class="bi bi-diagram-3"></i></span>
                <div>
                    DC Workspace
                    <small class="text-muted d-block">District Council</small>
                </div>
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li><a class="nav-link" href="index.php">Overview</a></li>
                    <li><a class="nav-link" href="submit.php">Submit project</a></li>
                    <li><a class="nav-link" href="projects.php">My projects</a></li>
                    <li><a class="nav-link active" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link" href="priorities.php">Priorities</a></li>
                    <li><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="vdc-hero">
        <div class="container">
            <div class="vdc-eyebrow">Decision register</div>
            <h1 class="h2">Accepted and denied projects</h1>
            <p class="mb-0">Review committee outcomes and the recorded reason behind each decision.</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="vdc-panel">
                    <div class="vdc-panel-header">
                        <h2 class="h5 mb-1">Accepted for development</h2>
                        <p class="text-muted small mb-0">Projects cleared for funding or implementation.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table vdc-table mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Budget</th>
                                    <th>Status</th>
                                    <th class="text-end">Votes</th>
                                </tr>
                            </thead>
                            <tbody id="accepted"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="vdc-panel">
                    <div class="vdc-panel-header">
                        <h2 class="h5 mb-1">Denied or returned</h2>
                        <p class="text-muted small mb-0">Reasons are kept visible for accountability and resubmission.</p>
                    </div>
                    <div class="vdc-panel-body" id="denied"></div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">DC Workspace · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="vdc.js"></script>
    <script>
        var list = DC.get();

        var accepted = list.filter(function (p) {
            return ['accepted', 'progress', 'completed'].indexOf(p.status) > -1;
        });

        document.getElementById('accepted').innerHTML = accepted.length
            ? accepted.map(function (p) {
                return '<tr>' +
                    '<td>' +
                    '<strong>' + p.title + '</strong>' +
                    '<div class="vdc-kicker">' + p.area + ' · ' + p.sector + '</div>' +
                    '</td>' +
                    '<td>' + DC.money(p.budget) + '</td>' +
                    '<td>' + DC.statusBadge(p) + '</td>' +
                    '<td class="text-end">' + p.votes.toLocaleString() + '</td>' +
                    '</tr>';
            }).join('')
            : '<tr><td colspan="4" class="vdc-empty">No accepted projects yet.</td></tr>';

        var denied = list.filter(function (p) {
            return p.status === 'denied';
        });

        document.getElementById('denied').innerHTML = denied.length
            ? denied.map(function (p) {
                return '<div class="border-bottom pb-3 mb-3">' +
                    '<div class="d-flex justify-content-between gap-2">' +
                    '<strong>' + p.title + '</strong>' +
                    DC.statusBadge(p) +
                    '</div>' +
                    '<div class="vdc-kicker mt-1">' + p.area + ' · ' + DC.money(p.budget) + '</div>' +
                    '<p class="small text-danger mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>' + p.reason + '</p>' +
                    '</div>';
            }).join('')
            : '<p class="vdc-empty mb-0">No denied projects.</p>';
    </script>
</body>
</html>
