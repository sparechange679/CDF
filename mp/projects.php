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
    <title>All Proposals | MP Workspace</title>
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
                    MP Workspace
                    <small class="text-muted d-block">Constituency oversight</small>
                </div>
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li><a class="nav-link" href="index.php">Overview</a></li>
                    <li><a class="nav-link active" href="projects.php">All proposals</a></li>
                    <li><a class="nav-link" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link" href="allocations.php">Funds &amp; DDO</a></li>
                    <li><a class="nav-link" href="progress.php">Progress</a></li>
                    <li><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="mp-hero">
        <div class="container">
            <div class="mp-eyebrow">Proposal register</div>
            <h1 class="h2">All VDC proposals</h1>
            <p class="mb-0">Search every local project, inspect citizen support, and open a full review record.</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="mp-panel">
            <div class="mp-panel-header">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Search</label>
                        <input id="q" class="form-control" placeholder="Project, area, sector, or VDC">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select id="filter" class="form-select">
                            <option value="">All statuses</option>
                            <option value="review">Under review</option>
                            <option value="accepted">Accepted</option>
                            <option value="denied">Denied</option>
                            <option value="progress">Implementation</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <span id="count" class="small text-muted"></span>
                    </div>
                </div>
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
                    <tbody id="rows"></tbody>
                </table>
            </div>
        </div>
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
        var all = MP.get();

        function render() {
            var q = document.getElementById('q').value.toLowerCase();
            var f = document.getElementById('filter').value;

            var a = all.filter(function (p) {
                return (!f || p.status === f) &&
                    (!q || (p.title + ' ' + p.area + ' ' + p.sector + ' ' + p.proposer).toLowerCase().indexOf(q) > -1);
            });

            document.getElementById('rows').innerHTML = a.length
                ? a.map(MP.row).join('')
                : '<tr><td colspan="5" class="mp-empty">No proposals match your filters.</td></tr>';

            document.getElementById('count').textContent = a.length + ' project' + (a.length === 1 ? '' : 's');
        }

        document.getElementById('q').addEventListener('input', render);
        document.getElementById('filter').addEventListener('change', render);
        render();
    </script>
</body>
</html>
