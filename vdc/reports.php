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
    <title>Reports | DC Workspace</title>
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
                    <li><a class="nav-link" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link" href="priorities.php">Priorities</a></li>
                    <li><a class="nav-link active" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="vdc-hero">
        <div class="container">
            <div class="vdc-eyebrow">Transparency and planning</div>
            <h1 class="h2">Statistics and reports</h1>
            <p class="mb-0">A concise view of the committee portfolio, funding position, and delivery progress.</p>
        </div>
    </header>

    <main class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h5 mb-1">2026 portfolio report</h2>
                <p class="small text-muted mb-0">Generated from the current DC project register.</p>
            </div>
            <button class="btn btn-outline-gov btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print report
            </button>
        </div>

        <div class="row g-3 mb-4" id="stats"></div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="vdc-panel">
                    <div class="vdc-panel-header">
                        <h2 class="h5 mb-0">Funding by sector</h2>
                    </div>
                    <div class="vdc-panel-body" id="sectors"></div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="vdc-panel">
                    <div class="vdc-panel-header">
                        <h2 class="h5 mb-0">Delivery status</h2>
                    </div>
                    <div class="vdc-panel-body" id="status"></div>
                </div>
            </div>
        </div>

        <div class="vdc-panel mt-4">
            <div class="vdc-panel-header">
                <h2 class="h5 mb-1">Report notes</h2>
                <p class="text-muted small mb-0">Use this summary alongside committee minutes and supporting documents.</p>
            </div>
            <div class="vdc-panel-body small text-muted">
                <p>
                    Accepted projects represent proposals cleared for funding or already in implementation.
                    Denied projects remain visible so committees can address gaps and retain an auditable decision trail.
                </p>
                <p class="mb-0">
                    Values are based on the current browser session and should be reconciled with the official
                    district register before publication.
                </p>
            </div>
        </div>

    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">DC Workspace · CDF Malawi · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="vdc.js"></script>
    <script>
        var list = DC.get();

        var budget = list.reduce(function (s, p) {
            return s + p.budget;
        }, 0);

        var accepted = list.filter(function (p) {
            return ['accepted', 'progress', 'completed'].indexOf(p.status) > -1;
        }).length;

        document.getElementById('stats').innerHTML = [
            ['bi-folder2-open', list.length, 'Projects recorded', 'green'],
            ['bi-cash-stack', DC.money(budget), 'Total proposed value', 'blue'],
            ['bi-check-circle', accepted, 'Accepted or delivering', 'gold'],
            ['bi-hand-thumbs-up', list.reduce(function (s, p) {
                return s + p.votes;
            }, 0).toLocaleString(), 'Citizen votes', 'red']
        ].map(function (x) {
            return '<div class="col-6 col-lg-3">' +
                '<div class="vdc-panel vdc-stat ' + x[3] + '">' +
                '<i class="bi ' + x[0] + ' text-muted"></i>' +
                '<div class="number">' + x[1] + '</div>' +
                '<div class="label">' + x[2] + '</div>' +
                '</div>' +
                '</div>';
        }).join('');

        var by = {};
        list.forEach(function (p) {
            by[p.sector] = (by[p.sector] || 0) + p.budget;
        });

        var max = Math.max.apply(null, Object.keys(by).map(function (k) {
            return by[k];
        }));

        document.getElementById('sectors').innerHTML = Object.keys(by).sort(function (a, b) {
            return by[b] - by[a];
        }).map(function (k) {
            return '<div class="mb-3">' +
                '<div class="d-flex justify-content-between small mb-1">' +
                '<strong>' + k + '</strong>' +
                '<span>' + DC.money(by[k]) + '</span>' +
                '</div>' +
                '<div class="vdc-progress"><div class="progress-bar" style="width:' + Math.round(by[k] / max * 100) + '%"></div></div>' +
                '</div>';
        }).join('');

        var states = {};
        list.forEach(function (p) {
            states[p.statusLabel] = (states[p.statusLabel] || 0) + 1;
        });

        document.getElementById('status').innerHTML = Object.keys(states).map(function (k) {
            return '<div class="d-flex justify-content-between border-bottom py-2">' +
                '<span>' + k + '</span>' +
                '<strong>' + states[k] + '</strong>' +
                '</div>';
        }).join('');
    </script>
</body>
</html>
