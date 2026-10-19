<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DDO Workspace | CDF Malawi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="ddo.css" rel="stylesheet">
</head>
<body class="ddo-shell">

    <div class="gov-topbar">
        <div class="container d-flex justify-content-between">
            <span><i class="bi bi-building me-1"></i> Official Government of Malawi Portal</span>
            <span class="d-none d-md-inline">District Development Officer Workspace</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg sticky-top ddo-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
                <div>
                    <div>DDO Workspace</div>
                    <small class="text-muted">District delivery office</small>
                </div>
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#ddoNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="ddoNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li><a class="nav-link active" href="index.php">Overview</a></li>
                    <li><a class="nav-link" href="projects.php">Priority projects</a></li>
                    <li><a class="nav-link" href="reports.php">Reports &amp; results</a></li>
                    <li class="ms-lg-2">
                        <a class="btn btn-cdf btn-sm" href="../mp/index.php">
                            <i class="bi bi-arrow-left me-1"></i> MP workspace
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="ddo-hero">
        <div class="container">
            <div class="ddo-eyebrow mb-2">District delivery oversight · 2026</div>
            <h1>Move approved priorities from allocation to results.</h1>
            <p class="lead mb-4">
                Assign delivery teams, verify project funds, maintain timelines,
                and publish clear progress reports for every approved development.
            </p>
            <a class="btn btn-light fw-semibold" href="projects.php">
                <i class="bi bi-kanban me-2"></i>Open project delivery
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
                            <h2 class="h5 mb-1">Approved and prioritised developments</h2>
                            <p class="text-muted small mb-0">Priority is calculated from citizen votes and current readiness.</p>
                        </div>
                        <a href="projects.php" class="btn btn-outline-gov btn-sm">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table ddo-table mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Request</th>
                                    <th>Votes</th>
                                    <th>Delivery status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="rows"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="ddo-panel h-100">
                    <div class="ddo-panel-header">
                        <h2 class="h5 mb-1">Delivery controls</h2>
                        <p class="text-muted small mb-0">Keep every assigned detail current.</p>
                    </div>
                    <div class="ddo-panel-body d-grid gap-2">
                        <a href="projects.php" class="btn btn-cdf text-start">
                            <i class="bi bi-person-workspace me-2"></i>Assign supplier and supervisor
                        </a>
                        <a href="reports.php" class="btn btn-outline-gov text-start">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>View reports and results
                        </a>
                        <div class="ddo-callout mt-3 small">
                            <strong>Top calculated priority</strong><br>
                            <span id="leader"></span><br>
                            <span class="text-muted">The score is a planning aid, not a replacement for procurement or technical review.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">DDO Workspace · CDF Malawi · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="ddo.js"></script>
    <script>
        var a = DDO.get();

        var ranked = DDO.priority(a);

        var allocated = a.reduce(function (s, p) {
            return s + (p.allocation ? p.allocation.amount : 0);
        }, 0);

        var active = a.filter(function (p) {
            return p.status === 'active';
        }).length;

        var done = a.filter(function (p) {
            return p.status === 'completed';
        }).length;

        document.getElementById('stats').innerHTML = [
            ['bi-kanban', '' + a.length, 'Approved projects', 'blue'],
            ['bi-wallet2', DDO.money(allocated), 'Funds assigned', 'green'],
            ['bi-activity', '' + active, 'In delivery', 'gold'],
            ['bi-check-circle', '' + done, 'Completed results', 'red']
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
            '<tr><td colspan="5" class="ddo-empty">No approved projects are available from the MP workspace.</td></tr>';

        document.getElementById('leader').textContent = ranked[0]
            ? ranked[0].title + ' · ' + ranked[0].votes.toLocaleString() + ' votes'
            : '';
    </script>
</body>
</html>
