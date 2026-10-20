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
    <title>My Projects | VDC Workspace</title>
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
                    VDC Workspace
                    <small class="text-muted d-block">Village Development Committee</small>
                </div>
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li><a class="nav-link" href="index.php">Overview</a></li>
                    <li><a class="nav-link" href="submit.php">Submit project</a></li>
                    <li><a class="nav-link active" href="projects.php">My projects</a></li>
                    <li><a class="nav-link" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link" href="priorities.php">Priorities</a></li>
                    <li><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="vdc-hero">
        <div class="container">
            <div class="vdc-eyebrow">Project register</div>
            <h1 class="h2">Submitted development projects</h1>
            <p class="mb-0">Track proposals from submission through review, approval, implementation, and completion.</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="vdc-panel">
            <div class="vdc-panel-header">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Search projects</label>
                        <input id="q" class="form-control" placeholder="Search by title, area, or sector">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Filter status</label>
                        <select id="filter" class="form-select">
                            <option value="">All statuses</option>
                            <option value="submitted">Submitted</option>
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
                <table class="table vdc-table mb-0">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Budget</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th class="text-end">Votes</th>
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
                <p class="mb-0">VDC Workspace · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="vdc.js"></script>
    <script>
        var all = VDC.get();

        function render() {
            var q = document.getElementById('q').value.toLowerCase();
            var f = document.getElementById('filter').value;

            var list = all.filter(function (p) {
                return (!f || p.status === f) &&
                    (!q || (p.title + ' ' + p.area + ' ' + p.sector).toLowerCase().indexOf(q) > -1);
            });

            document.getElementById('rows').innerHTML = list.length
                ? list.map(function (p) {
                    return VDC.row(p, true);
                }).join('')
                : '<tr><td colspan="5" class="vdc-empty">No projects match your filters.</td></tr>';

            document.getElementById('count').textContent = list.length + ' project' + (list.length === 1 ? '' : 's');
        }

        document.getElementById('q').addEventListener('input', render);
        document.getElementById('filter').addEventListener('change', render);
        render();
    </script>
</body>
</html>
