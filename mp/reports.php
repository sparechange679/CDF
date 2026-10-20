<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('mp', '../auth.php');
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
            <button class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#nav'>
                <span class='navbar-toggler-icon'></span>
            </button>
            <div class='collapse navbar-collapse' id='nav'>
                <ul class='navbar-nav ms-auto'>
                    <li><a class='nav-link' href='index.php'>Overview</a></li>
                    <li><a class='nav-link' href='projects.php'>All proposals</a></li>
                    <li><a class='nav-link' href='decisions.php'>Decisions</a></li>
                    <li><a class='nav-link' href='allocations.php'>Funds &amp; DDO</a></li>
                    <li><a class='nav-link' href='progress.php'>Progress</a></li>
                    <li><a class='nav-link active' href='reports.php'>Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <div class="mp-eyebrow text-dark" id="report-eyebrow">Accountability report</div>
                <h1 class="h2">Constituency project report</h1>
                <p class="text-muted mb-0">Generated from the constituency proposals, decisions, allocations, and citizen votes.</p>
            </div>
            <button class="btn btn-cdf btn-sm no-print" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Generate / print
            </button>
        </div>

        <div class="row g-3 mb-4" id="stats">
            <div class="col-12 text-muted">Loading report stats…</div>
        </div>

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
                            <tbody id="funds">
                                <tr><td colspan="4" class="mp-empty">Loading funding records…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="mp-panel">
                    <div class="mp-panel-header">
                        <h2 class="h5 mb-0">Status breakdown</h2>
                    </div>
                    <div class="mp-panel-body" id="status">
                        <div class="text-muted">Loading status breakdown…</div>
                    </div>
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
            <div class="mp-panel-body" id="priority">
                <div class="text-muted">Loading priority calculation…</div>
            </div>
        </div>

        <p class="small text-muted mt-4">
            Generated: <?php echo date('d M Y'); ?> · Reconcile this report with official district finance records before publication.
        </p>

    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">MP Workspace · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="mp.js?v=20261020"></script>
    <script>
        MP.load(function (err, a, constituency) {
            if (err) {
                document.getElementById('stats').innerHTML = '<div class="col-12"><div class="alert alert-danger">' + MP.esc(err.message) + '</div></div>';
                return;
            }

            if (constituency) {
                document.getElementById('report-eyebrow').textContent = constituency.name + ' Constituency · Accountability Report';
            }

            var esc = MP.esc;

            var allocated = a.reduce(function (s, p) {
                return s + (p.allocation ? p.allocation.amount : 0);
            }, 0);

            var votes = a.reduce(function (s, p) {
                return s + (p.votes || 0);
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

            document.getElementById('funds').innerHTML = a.length ? a.map(function (p) {
                return '<tr>' +
                    '<td>' +
                    '<strong>' + esc(p.title) + '</strong>' +
                    '<div class="mp-kicker">' + esc(p.area) + '</div>' +
                    '</td>' +
                    '<td>' + MP.money(p.budget) + '</td>' +
                    '<td>' + (p.allocation ? MP.money(p.allocation.amount) : '<span class="text-muted">Pending</span>') + '</td>' +
                    '<td>' + (p.progress || 0) + '%</td>' +
                    '</tr>';
            }).join('') : '<tr><td colspan="4" class="mp-empty">No projects recorded yet.</td></tr>';

            var states = {};
            a.forEach(function (p) {
                states[p.statusLabel] = (states[p.statusLabel] || 0) + 1;
            });

            var stateKeys = Object.keys(states);
            document.getElementById('status').innerHTML = stateKeys.length ? stateKeys.map(function (k) {
                return '<div class="d-flex justify-content-between border-bottom py-2">' +
                    '<span>' + esc(k) + '</span>' +
                    '<strong>' + states[k] + '</strong>' +
                    '</div>';
            }).join('') : '<p class="text-muted small mb-0">No project statuses to display.</p>';

            var ranked = a.slice().sort(function (x, y) {
                return ((y.votes || 0) + (y.progress || 0) * 2) - ((x.votes || 0) + (x.progress || 0) * 2);
            });

            var topVotes = ranked.length && ranked[0].votes ? ranked[0].votes : 1;

            document.getElementById('priority').innerHTML = ranked.length ? ranked.map(function (p, i) {
                return '<div class="d-flex align-items-center gap-3 mb-3">' +
                    '<span class="badge text-bg-dark">' + (i + 1) + '</span>' +
                    '<div class="flex-grow-1">' +
                    '<div class="d-flex justify-content-between small">' +
                    '<strong>' + esc(p.title) + '</strong>' +
                    '<span>' + Number(p.votes || 0).toLocaleString() + ' votes · ' + (p.progress || 0) + '% ready</span>' +
                    '</div>' +
                    '<div class="mp-progress mt-1">' +
                    '<div class="progress-bar" style="width:' + Math.min(100, Math.round(((p.votes || 0) / Math.max(1, topVotes)) * 70 + (p.progress || 0) * .3)) + '%"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }).join('') : '<p class="text-muted small mb-0">No ranked proposals to display.</p>';
        });
    </script>
</body>
</html>
