<?php
include 'includes/head.php';
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
        <p class="mb-0">Open a project to assign delivery resources, confirm its timeline, and maintain its result
            record.</p>
    </div>
</header>

<main class="container py-5">
    <div class="ddo-panel">
        <div class="ddo-panel-header">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Search</label>
                    <input id="q" class="form-control" placeholder="Project, village, sector, or supplier">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Delivery status</label>
                    <select id="filter" class="form-select">
                        <option value="">All statuses</option>
                        <option value="planning">Assignment pending</option>
                        <option value="active">In progress</option>
                        <option value="completed">Completed</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                <tbody id="rows"></tbody>
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

<script src="ddo.js"></script>
<script>
    let all = DDO.priority(DDO.get());

    function render() {
        let q = document.getElementById('q').value.toLowerCase();
        let f = document.getElementById('filter').value;

        let a = all.filter(function (p) {
            return (!f || p.status === f) &&
                (!q || (p.title + ' ' + p.area + ' ' + p.sector + ' ' + p.supplier).toLowerCase().indexOf(q) > -1);
        });

        document.getElementById('rows').innerHTML = a.length
            ? a.map(DDO.row).join('')
            : '<tr><td colspan="5" class="ddo-empty">No approved projects match your filters.</td></tr>';

        document.getElementById('count').textContent = a.length + ' project' + (a.length === 1 ? '' : 's');
    }

    document.getElementById('q').addEventListener('input', render);
    document.getElementById('filter').addEventListener('change', render);
    render();
</script>
</body>
</html>
