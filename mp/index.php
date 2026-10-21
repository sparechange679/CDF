<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('mp', '../auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MP Workspace | CDF Malawi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="mp.css" rel="stylesheet">
</head>
<body class="mp-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top mp-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="mp-brand-mark"><i class="bi bi-bank"></i></span>
                <div>
                    <div>MP Workspace</div>
                    <small class="text-muted">Constituency oversight</small>
                </div>
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#mpNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mpNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li><a class="nav-link active" href="index.php">Overview</a></li>
                    <li><a class="nav-link" href="projects.php">All proposals</a></li>
                    <li><a class="nav-link" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link" href="progress.php">Progress</a></li>
                    <li><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="mp-hero">
        <div class="container">
            <div class="mp-eyebrow mb-2" id="hero-kicker">Parliamentary constituency oversight</div>
            <h1>Decide, fund, and account for local development.</h1>
            <p class="lead mb-4">
                Review constituency proposals, use citizen votes to guide priorities, record approval and rejection decisions with reasons, and keep implementation progress visible.
            </p>
            <a class="btn btn-light fw-semibold" href="projects.php">
                <i class="bi bi-clipboard-data me-2"></i>Review proposals
            </a>
        </div>
    </header>

    <main class="container py-5">

        <div class="row g-3 mb-4" id="stats">
            <div class="col-12 text-muted">Loading workspace metrics…</div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="mp-panel">
                    <div class="mp-panel-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-1">Action queue</h2>
                            <p class="text-muted small mb-0">Constituency proposals requiring review or already moving through delivery.</p>
                        </div>
                        <a href="projects.php" class="btn btn-outline-gov btn-sm">Open register</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mp-table mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Budget</th>
                                    <th>Votes</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="queue">
                                <tr><td colspan="5" class="mp-empty">Loading action queue…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="mp-panel h-100">
                    <div class="mp-panel-header">
                        <h2 class="h5 mb-1">Oversight actions</h2>
                        <p class="text-muted small mb-0">Keep decisions and delivery records current.</p>
                    </div>
                    <div class="mp-panel-body d-grid gap-2">
                        <a href="projects.php" class="btn btn-cdf text-start">
                            <i class="bi bi-check2-square me-2"></i>Record a decision &amp; reason
                        </a>
                        <a href="allocations.php" class="btn btn-outline-gov text-start">
                            <i class="bi bi-wallet2 me-2"></i>Verify funds and DDO details
                        </a>
                        <a href="progress.php" class="btn btn-outline-gov text-start">
                            <i class="bi bi-graph-up-arrow me-2"></i>Update implementation
                        </a>
                        <a href="reports.php" class="btn btn-outline-gov text-start">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate report
                        </a>
                        <div class="mp-callout mt-3 small">
                            <strong>Automated priority</strong><br>
                            <span id="leader">Calculating constituency leader…</span><br>
                            <span class="text-muted">Ranking combines citizen votes with project readiness.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">MP Workspace · CDF Malawi · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="mp.js?v=20261020"></script>
    <script>
        MP.load(function (err, list, constituency) {
            if (err) {
                document.getElementById('stats').innerHTML = '<div class="col-12"><div class="alert alert-danger">' + MP.esc(err.message) + '</div></div>';
                document.getElementById('queue').innerHTML = '<tr><td colspan="5" class="mp-empty">Unable to load queue.</td></tr>';
                return;
            }

            if (constituency) {
                document.getElementById('hero-kicker').textContent = constituency.name + ' Constituency oversight · ' + (constituency.district || 'Malawi');
            }

            var review = list.filter(function (p) {
                return p.status === 'review' || p.status === 'submitted';
            });

            var accepted = list.filter(function (p) {
                return ['accepted', 'progress', 'completed'].indexOf(p.status) > -1;
            });

            var funds = list.reduce(function (s, p) {
                return s + (p.allocation ? p.allocation.amount : 0);
            }, 0);

            document.getElementById('stats').innerHTML = [
                ['bi-inbox', review.length, 'Awaiting MP decision', 'red'],
                ['bi-hand-thumbs-up', list.reduce(function (s, p) {
                    return s + (p.votes || 0);
                }, 0).toLocaleString(), 'Citizen votes', 'blue'],
                ['bi-wallet2', MP.money(funds), 'Allocated funds', 'green'],
                ['bi-check-circle', accepted.length, 'Accepted or delivering', 'gold']
            ].map(function (x) {
                return '<div class="col-6 col-lg-3">' +
                    '<div class="mp-panel mp-stat ' + x[3] + '">' +
                    '<i class="bi ' + x[0] + ' text-muted"></i>' +
                    '<div class="number">' + x[1] + '</div>' +
                    '<div class="label">' + x[2] + '</div>' +
                    '</div>' +
                    '</div>';
            }).join('');

            document.getElementById('queue').innerHTML = list.length
                ? list.slice(0, 5).map(MP.row).join('')
                : '<tr><td colspan="5" class="mp-empty">No projects found in this constituency.</td></tr>';

            var ranked = list.slice().sort(function (a, b) {
                return ((b.votes || 0) + (b.progress || 0) * 2) - ((a.votes || 0) + (a.progress || 0) * 2);
            });

            document.getElementById('leader').textContent = ranked[0]
                ? ranked[0].title + ' leads with ' + (ranked[0].votes || 0).toLocaleString() + ' votes.'
                : 'No proposals currently recorded.';
        });
    </script>
</body>
</html>
