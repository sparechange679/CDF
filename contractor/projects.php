<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('contractor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assigned Projects | Contractor Workspace</title>
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
                Contractor Workspace
                <small class="text-muted d-block">Contractor delivery</small>
            </div>
        </a>
        <button class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#ddoNav'>
            <span class='navbar-toggler-icon'></span>
        </button>
        <div class='collapse navbar-collapse' id='ddoNav'>
            <ul class='navbar-nav ms-auto align-items-lg-center'>
                <li><a class='nav-link' href='index.php'>Overview</a></li>
                <li><a class='nav-link active' href='projects.php'>Assigned projects</a></li>
                <li><a class='nav-link' href='reports.php'>Reports &amp; results</a></li>
                <li><a class='nav-link' href='quotations.php'>Quotations</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="ddo-hero">
    <div class="container">
        <div class="ddo-eyebrow">Contractor Delivery Portfolio</div>
        <h1 class="h2">Assigned projects</h1>
        <p class="mb-0">Select an assigned project to record delivery updates, adjust progress completion percentage, and submit work reports.</p>
    </div>
</header>

<main class="container py-5">
    <div id="alert"></div>
    <div class="ddo-panel">
        <div class="ddo-panel-header">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="q">Search</label>
                    <input id="q" class="form-control" placeholder="Project title, village, or sector">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="filter">Delivery status</label>
                    <select id="filter" class="form-select">
                        <option value="">All statuses</option>
                        <option value="assigned">Assigned</option>
                        <option value="active">In progress</option>
                        <option value="completed">Completed</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <span id="count" class="small text-muted"></span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table ddo-table mb-0">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Budget</th>
                        <th>Priority votes</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="rows">
                    <tr><td colspan="5" class="ddo-empty">Loading assigned projects…</td></tr>
                </tbody>
            </table>
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
    var all = [];

    function render() {
        var q = (document.getElementById('q').value || '').toLowerCase().trim();
        var f = document.getElementById('filter').value;

        var a = all.filter(function (p) {
            var asgnSt = p.assignment ? p.assignment.status : (p.status === 'completed' ? 'completed' : (p.status === 'progress' ? 'active' : 'assigned'));
            var matchesFilter = !f || asgnSt === f || p.status === f;
            var hay = [p.title, p.area, p.sector, p.reference].join(' ').toLowerCase();
            var matchesQuery = !q || hay.indexOf(q) > -1;
            return matchesFilter && matchesQuery;
        });

        document.getElementById('rows').innerHTML = a.length
            ? a.map(DDO.row).join('')
            : '<tr><td colspan="5" class="ddo-empty">' + (all.length ? 'No assigned projects match your filters.' : 'No projects are assigned to your contractor account yet.') + '</td></tr>';

        document.getElementById('count').textContent = a.length + ' project' + (a.length === 1 ? '' : 's');
    }

    DDO.load(function (err, projects) {
        if (err) {
            document.getElementById('alert').innerHTML = '<div class="alert alert-danger">' + DDO.esc(err.message) + '</div>';
            document.getElementById('rows').innerHTML = '<tr><td colspan="5" class="ddo-empty">Unable to load assigned projects.</td></tr>';
            return;
        }
        all = DDO.priority(projects);
        render();
    });

    document.getElementById('q').addEventListener('input', render);
    document.getElementById('filter').addEventListener('change', render);
</script>
</body>
</html>
