<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('council', '../auth.php');   // only District Council accounts may open this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Projects | DC Workspace</title>
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
    <div id="alert"></div>

    <div class="vdc-panel">
        <div class="vdc-panel-header">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold" for="q">Search projects</label>
                    <input id="q" class="form-control" placeholder="Title, reference, area, or sector">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="filter">Filter status</label>
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
                <div class="col-md-1 text-md-end">
                    <a href="submit.php" class="btn btn-cdf btn-sm" title="Submit project"><i class="bi bi-plus-lg"></i></a>
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
                <tbody id="rows">
                <tr><td colspan="5" class="vdc-empty">Loading projects…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">
            <p class="mb-0">DC Workspace · <a href="../index.php">Citizen portal</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";

        var all = [];

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function money(n) {
            return 'MK ' + Number(n).toLocaleString('en-GB', { maximumFractionDigits: 2 });
        }

        function badge(p) {
            return '<span class="vdc-status ' + esc(p.status) + '">' +
                '<i class="bi bi-circle-fill" style="font-size:.42rem"></i>' + esc(p.statusLabel) + '</span>';
        }

        function row(p) {
            return '<tr>' +
                '<td><strong>' + esc(p.title) + '</strong>' +
                '<div class="vdc-kicker">' + esc(p.reference) + ' · ' + esc(p.area) + ' · ' + esc(p.sector) + '</div></td>' +
                '<td>' + money(p.budget) + '</td>' +
                '<td>' + esc(p.submitted) + '</td>' +
                '<td>' + badge(p) +
                (p.reason ? '<div class="small text-danger mt-2"><i class="bi bi-info-circle me-1"></i>' + esc(p.reason) + '</div>' : '') +
                '</td>' +
                '<td class="text-end"><span class="fw-semibold">' + Number(p.votes).toLocaleString() + '</span></td>' +
                '</tr>';
        }

        function render() {
            var q = document.getElementById('q').value.toLowerCase().trim();
            var f = document.getElementById('filter').value;

            var list = all.filter(function (p) {
                var hay = [p.title, p.reference, p.area, p.sector].join(' ').toLowerCase();
                return (!f || p.status === f) && (!q || hay.indexOf(q) > -1);
            });

            document.getElementById('rows').innerHTML = list.length
                ? list.map(row).join('')
                : '<tr><td colspan="5" class="vdc-empty">' +
                (all.length ? 'No projects match your filters.' : 'You have not submitted any projects yet. <a href="submit.php">Submit your first proposal</a>.') +
                '</td></tr>';

            document.getElementById('count').textContent = list.length + ' project' + (list.length === 1 ? '' : 's');
        }

        fetch('../api/projects.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) {
                    document.getElementById('alert').innerHTML = '<div class="alert alert-danger" role="alert"></div>';
                    document.getElementById('alert').firstChild.textContent = d.error || 'Could not load projects.';
                    document.getElementById('rows').innerHTML = '<tr><td colspan="5" class="vdc-empty">Projects could not be loaded.</td></tr>';
                    return;
                }
                all = d.projects;
                render();
            })
            .catch(function () {
                document.getElementById('rows').innerHTML = '<tr><td colspan="5" class="vdc-empty">Could not reach the server. Try again.</td></tr>';
            });

        document.getElementById('q').addEventListener('input', render);
        document.getElementById('filter').addEventListener('change', render);
    })();
</script>
</body>
</html>