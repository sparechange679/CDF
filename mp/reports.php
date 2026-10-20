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
    <title>Reports | MP Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="mp.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, nav, .gov-topbar, footer { display: none !important; }
            .mp-shell { background: white; }
            .mp-panel { box-shadow: none; }
        }
    </style>
</head>
<body class="mp-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top mp-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="mp-brand-mark"><i class="bi bi-bank"></i></span>
                <div>
                    MP Workspace
                    <small class="text-muted d-block">Constituency oversight</small>
                </div>
            </a>
            <div>
                <a class="btn btn-outline-gov btn-sm" href="index.php">Overview</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <div class="mp-eyebrow text-dark">Accountability report</div>
                <h1 class="h2">Constituency project report</h1>
                <p class="text-muted mb-0">Generated from the VDC proposal, decision, allocation, vote, and progress records.</p>
            </div>
            <button class="btn btn-cdf btn-sm no-print" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Generate / print
            </button>
        </div>

        <div class="row g-3 mb-4" id="stats"></div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="mp-panel">
                    <div class="mp-panel-header">
                        <h2 class="h5 mb-0">Funding by project</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table mp-table mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Requested</th>
                                    <th>Allocated</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody id="funds"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="mp-panel">
                    <div class="mp-panel-header">
                        <h2 class="h5 mb-0">Status breakdown</h2>
                    </div>
                    <div class="mp-panel-body" id="status"></div>
                </div>
            </div>
        </div>

        <div class="mp-panel mt-4">
            <div class="mp-panel-header">
                <h2 class="h5 mb-1">Priority calculation</h2>
                <p class="text-muted small mb-0">
                    Automated ordering uses citizen votes plus readiness, where readiness is represented by recorded progress.
                </p>
            </div>
            <div class="mp-panel-body" id="priority"></div>
        </div>

        <p class="small text-muted mt-4">
            Generated: 27 Aug 2026 · Reconcile this browser report with official district finance records before publication.
        </p>

    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">MP Workspace · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="mp.js"></script>
    <script>
        var a = MP.get();

        var allocated = a.reduce(function (s, p) {
            return s + (p.allocation ? p.allocation.amount : 0);
        }, 0);

        var votes = a.reduce(function (s, p) {
            return s + p.votes;
        }, 0);

        var delivering = a.filter(function (p) {
            return ['progress', 'completed'].indexOf(p.status) > -1;
        }).length;

        document.getElementById('stats').innerHTML = [
            ['bi-folder2-open', a.length, 'Projects in register', 'blue'],
            ['bi-wallet2', MP.money(allocated), 'Funds allocated', 'green'],
            ['bi-hand-thumbs-up', votes.toLocaleString(), 'Citizen votes', 'red'],
            ['bi-graph-up-arrow', delivering, 'Projects delivering', 'gold']
        ].map(function (x) {
            return '<div class="col-6 col-lg-3">' +
                '<div class="mp-panel mp-stat ' + x[3] + '">' +
                '<i class="bi ' + x[0] + ' text-muted"></i>' +
                '<div class="number">' + x[1] + '</div>' +
                '<div class="label">' + x[2] + '</div>' +
                '</div>' +
                '</div>';
        }).join('');

        document.getElementById('funds').innerHTML = a.map(function (p) {
            return '<tr>' +
                '<td>' +
                '<strong>' + p.title + '</strong>' +
                '<div class="mp-kicker">' + p.area + '</div>' +
                '</td>' +
                '<td>' + MP.money(p.budget) + '</td>' +
                '<td>' + (p.allocation ? MP.money(p.allocation.amount) : '<span class="text-muted">Pending</span>') + '</td>' +
                '<td>' + (p.progress || 0) + '%</td>' +
                '</tr>';
        }).join('');

        var states = {};
        a.forEach(function (p) {
            states[p.statusLabel] = (states[p.statusLabel] || 0) + 1;
        });

        document.getElementById('status').innerHTML = Object.keys(states).map(function (k) {
            return '<div class="d-flex justify-content-between border-bottom py-2">' +
                '<span>' + k + '</span>' +
                '<strong>' + states[k] + '</strong>' +
                '</div>';
        }).join('');

        var ranked = a.slice().sort(function (x, y) {
            return (y.votes + (y.progress || 0) * 2) - (x.votes + (x.progress || 0) * 2);
        });

        document.getElementById('priority').innerHTML = ranked.map(function (p, i) {
            return '<div class="d-flex align-items-center gap-3 mb-3">' +
                '<span class="badge text-bg-dark">' + (i + 1) + '</span>' +
                '<div class="flex-grow-1">' +
                '<div class="d-flex justify-content-between small">' +
                '<strong>' + p.title + '</strong>' +
                '<span>' + p.votes.toLocaleString() + ' votes · ' + (p.progress || 0) + '% ready</span>' +
                '</div>' +
                '<div class="mp-progress mt-1">' +
                '<div class="progress-bar" style="width:' + Math.min(100, Math.round((p.votes / Math.max(1, ranked[0].votes)) * 70 + (p.progress || 0) * .3)) + '%"></div>' +
                '</div>' +
                '</div>' +
                '</div>';
        }).join('');
    </script>
</body>
</html>
