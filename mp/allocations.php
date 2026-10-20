<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('mp', '../auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Funds and DDO | MP Workspace</title>
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
            <button class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#nav'>
                <span class='navbar-toggler-icon'></span>
            </button>
            <div class='collapse navbar-collapse' id='nav'>
                <ul class='navbar-nav ms-auto'>
                    <li><a class='nav-link' href='index.php'>Overview</a></li>
                    <li><a class='nav-link' href='projects.php'>All proposals</a></li>
                    <li><a class='nav-link' href='decisions.php'>Decisions</a></li>
                    <li><a class='nav-link active' href='allocations.php'>Funds &amp; DDO</a></li>
                    <li><a class='nav-link' href='progress.php'>Progress</a></li>
                    <li><a class='nav-link' href='reports.php'>Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="mb-4">
            <div class="mp-eyebrow text-dark">Fund control</div>
            <h1 class="h2">Allocated funds and DDO details</h1>
            <p class="text-muted">Verify the amount, source, reference, scope, and District Development Officer record attached to each project.</p>
        </div>

        <div class="mp-panel">
            <div class="table-responsive">
                <table class="table mp-table mb-0">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Allocation</th>
                            <th>DDO</th>
                            <th>Reference and date</th>
                            <th>Scope</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="rows">
                        <tr><td colspan="6" class="mp-empty">Loading allocation records…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mp-callout small mt-4">
            <strong>Control note:</strong>
            Projects without an allocation record should not be marked ready for implementation until the DDO
            confirms the funding details.
        </div>
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
        MP.load(function (err, a) {
            if (err) {
                document.getElementById('rows').innerHTML = '<tr><td colspan="6" class="mp-empty">Failed to load allocations.</td></tr>';
                return;
            }

            var esc = MP.esc;
            document.getElementById('rows').innerHTML = a.length ? a.map(function (p) {
                var x = p.allocation;

                return '<tr>' +
                    '<td>' +
                    '<strong>' + esc(p.title) + '</strong>' +
                    '<div class="mp-kicker">' + esc(p.area) + ' · ' + MP.badge(p) + '</div>' +
                    '</td>' +
                    '<td class="fw-semibold">' + (x ? MP.money(x.amount) : '<span class="text-muted">Pending</span>') + '</td>' +
                    '<td>' + (x ? esc(x.ddo) : '<span class="text-muted">Not assigned</span>') + '</td>' +
                    '<td class="small">' + (x ? esc(x.reference) + '<br>' + esc(x.date) : 'Awaiting DDO record') + '</td>' +
                    '<td class="small">' + (x ? esc(x.scope) : 'No approved scope recorded') + '</td>' +
                    '<td><a class="btn btn-outline-gov btn-sm" href="review.php?id=' + encodeURIComponent(p.id) + '">Open</a></td>' +
                    '</tr>';
            }).join('') : '<tr><td colspan="6" class="mp-empty">No projects found in this constituency.</td></tr>';
        });
    </script>
</body>
</html>
