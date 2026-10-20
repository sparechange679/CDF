<?php
include 'includes/head.php';
require_role('ddo');
?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>
                DDO Workspace
                <small class="text-muted d-block">District delivery office</small>
            </div>
        </a>
        <button class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#ddoNav'>
            <span class='navbar-toggler-icon'></span>
        </button>
        <div class='collapse navbar-collapse' id='ddoNav'>
            <ul class='navbar-nav ms-auto align-items-lg-center'>
                <li><a class='nav-link' href='index.php'>Overview</a></li>
                <li><a class='nav-link active' href='projects.php'>Priority projects</a></li>
                <li><a class='nav-link' href='users.php'>User accounts</a></li>
                <li><a class='nav-link' href='reports.php'>Reports &amp; results</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="ddo-hero">
    <div class="container">
        <div class="ddo-eyebrow">Delivery portfolio</div>
        <h1 class="h2">Approved and prioritized projects</h1>
        <p class="mb-0">Open a project to assign an available contractor, confirm its timeline, and monitor contractor progress updates.</p>
    </div>
</header>

<main class="container py-5">
    <div id="alert"></div>
    <div class="ddo-panel">
        <div class="ddo-panel-header">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="q">Search</label>
                    <input id="q" class="form-control" placeholder="Project, village, sector, or contractor">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="filter">Delivery status</label>
                    <select id="filter" class="form-select">
                        <option value="">All statuses</option>
                        <option value="planning">Assignment pending</option>
                        <option value="assigned">Contractor assigned</option>
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
                    <th>Funds</th>
                    <th>Priority votes</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="rows">
                    <tr><td colspan="5" class="ddo-empty">Loading projects…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">
            <p class="mb-0">DDO Workspace · <a href="../index.php">Citizen portal</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src='ddo.js?v=20261020'></script>
<script>
    let all = [];

    function render() {
        let q = (document.getElementById('q').value || '').toLowerCase().trim();
        let f = document.getElementById('filter').value;

        let a = all.filter(function (p) {
            let asgnSt = p.assignment ? p.assignment.status : (p.status === 'completed' ? 'completed' : (p.status === 'progress' ? 'active' : 'planning'));
            let matchesFilter = !f || asgnSt === f || p.status === f;
            let hay = [p.title, p.area, p.sector, p.supplier, p.reference].join(' ').toLowerCase();
            let matchesQuery = !q || hay.indexOf(q) > -1;
            return matchesFilter && matchesQuery;
        });

        document.getElementById('rows').innerHTML = a.length
            ? a.map(DDO.row).join('')
            : '<tr><td colspan="5" class="ddo-empty">' + (all.length ? 'No approved projects match your filters.' : 'No approved projects available.') + '</td></tr>';

        document.getElementById('count').textContent = a.length + ' project' + (a.length === 1 ? '' : 's');
    }

    DDO.load(function (err, projects) {
        if (err) {
            document.getElementById('alert').innerHTML = '<div class="alert alert-danger">' + DDO.esc(err.message) + '</div>';
            document.getElementById('rows').innerHTML = '<tr><td colspan="5" class="ddo-empty">Unable to load projects.</td></tr>';
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
