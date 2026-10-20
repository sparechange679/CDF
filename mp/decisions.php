<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('mp', '../auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Decisions | MP Workspace</title>
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
                    <li><a class='nav-link active' href='decisions.php'>Decisions</a></li>
                    <li><a class='nav-link' href='allocations.php'>Funds &amp; DDO</a></li>
                    <li><a class='nav-link' href='progress.php'>Progress</a></li>
                    <li><a class='nav-link' href='reports.php'>Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="mb-4">
            <div class="mp-eyebrow text-dark">Decision register</div>
            <h1 class="h2">Approval and rejection decisions</h1>
            <p class="text-muted">Every MP outcome stays visible with its reason and supporting vote count.</p>
        </div>

        <div class="mp-panel">
            <div class="table-responsive">
                <table class="table mp-table mb-0">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Decision date</th>
                            <th>Outcome</th>
                            <th>Reason</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="rows">
                        <tr><td colspan="5" class="mp-empty">Loading decisions…</td></tr>
                    </tbody>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="mp.js?v=20261020"></script>
    <script>
        MP.load(function (err, projects, constituency) {
            if (err) {
                document.getElementById('rows').innerHTML = '<tr><td colspan="5" class="mp-empty">Failed to load decisions.</td></tr>';
                return;
            }

            var esc = MP.esc;
            var a = projects.filter(function (p) {
                return ['accepted', 'denied', 'progress', 'completed'].indexOf(p.status) > -1 || !!p.reason;
            });

            document.getElementById('rows').innerHTML = a.length ? a.map(function (p) {
                return '<tr>' +
                    '<td>' +
                    '<strong>' + esc(p.title) + '</strong>' +
                    '<div class="mp-kicker">' + esc(p.area) + ' · ' + Number(p.votes || 0).toLocaleString() + ' citizen votes</div>' +
                    '</td>' +
                    '<td>' + esc(p.decision_date || p.submitted || '—') + '</td>' +
                    '<td>' + MP.badge(p) + '</td>' +
                    '<td class="small">' + (p.reason ? esc(p.reason) : '<span class="text-muted">Approved for funding and delivery.</span>') + '</td>' +
                    '<td><a class="btn btn-outline-gov btn-sm" href="review.php?id=' + encodeURIComponent(p.id) + '">Open record</a></td>' +
                    '</tr>';
            }).join('') : '<tr><td colspan="5" class="mp-empty">No decisions recorded yet.</td></tr>';
        });
    </script>
</body>
</html>
