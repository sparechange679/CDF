<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('mp', '../auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Progress | MP Workspace</title>
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
                    <li><a class='nav-link active' href='progress.php'>Progress</a></li>
                    <li><a class='nav-link' href='reports.php'>Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="mb-4">
            <div class="mp-eyebrow text-dark">Delivery monitoring</div>
            <h1 class="h2">Project progress and status updates</h1>
            <p class="text-muted">Monitor implementation progress and inspect updates for accepted projects in your constituency.</p>
        </div>

        <div id="list" class="row g-4"><div class="col-12"><div class="mp-panel mp-empty">Loading progress updates…</div></div></div>
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
        MP.load(function (err, all) {
            if (err) {
                document.getElementById('list').innerHTML = '<div class="col-12"><div class="alert alert-danger">' + MP.esc(err.message) + '</div></div>';
                return;
            }

            var esc = MP.esc;
            var filtered = all.filter(function (p) {
                return ['accepted', 'progress', 'completed'].indexOf(p.status) > -1;
            });

            document.getElementById('list').innerHTML = filtered.length ? filtered.map(function (p) {
                return '<div class="col-lg-6">' +
                    '<div class="mp-panel">' +
                    '<div class="mp-panel-body">' +
                    '<div class="d-flex justify-content-between gap-2">' +
                    '<div>' +
                    '<h2 class="h5 mb-1">' + esc(p.title) + '</h2>' +
                    '<div class="mp-kicker">' + esc(p.area) + ' · ' + esc(p.sector) + '</div>' +
                    '</div>' +
                    MP.badge(p) +
                    '</div>' +
                    '<div class="d-flex justify-content-between small mt-4 mb-1">' +
                    '<span>Implementation progress</span>' +
                    '<strong>' + ((p.progress || 0)) + '%</strong>' +
                    '</div>' +
                    '<div class="mp-progress mb-3">' +
                    '<div class="progress-bar" style="width:' + (p.progress || 0) + '%"></div>' +
                    '</div>' +
                    '<p class="small text-muted mb-3">' +
                    (p.reason
                        ? 'MP decision note: ' + esc(p.reason)
                        : 'No additional progress note recorded.') +
                    '</p>' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                    '<span class="small text-muted">' + Number(p.votes || 0).toLocaleString() + ' citizen votes</span>' +
                    '<a class="btn btn-outline-gov btn-sm" href="review.php?id=' + encodeURIComponent(p.id) + '">Open review</a>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }).join('') : '<div class="col-12"><div class="mp-panel mp-empty">No accepted projects are currently in progress.</div></div>';
        });
    </script>
</body>
</html>
