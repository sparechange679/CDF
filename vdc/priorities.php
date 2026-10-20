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
    <title>Priorities | VDC Workspace</title>
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
                    <li><a class="nav-link" href="projects.php">My projects</a></li>
                    <li><a class="nav-link" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link active" href="priorities.php">Priorities</a></li>
                    <li><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="vdc-hero">
        <div class="container">
            <div class="vdc-eyebrow">Citizen voice</div>
            <h1 class="h2">Community priorities</h1>
            <p class="mb-0">Use public votes to understand which proposed developments residents want addressed first.</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="vdc-panel">
                    <div class="vdc-panel-header d-flex justify-content-between">
                        <div>
                            <h2 class="h5 mb-1">Priority ranking</h2>
                            <p class="text-muted small mb-0">Ranked by votes received from citizens.</p>
                        </div>
                        <span class="badge text-bg-light" id="total"></span>
                    </div>
                    <div class="vdc-panel-body" id="ranking"></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="vdc-panel h-100">
                    <div class="vdc-panel-header">
                        <h2 class="h5 mb-1">How to use this</h2>
                    </div>
                    <div class="vdc-panel-body">
                        <p class="small text-muted">
                            Vote totals are one part of committee prioritisation. Consider urgency, equity,
                            readiness, cost, and whether the proposal serves people who have fewer alternatives.
                        </p>
                        <div class="vdc-callout small">
                            <strong>Next committee review</strong><br>
                            30 August 2026<br>
                            <span class="text-muted">Bring the top three proposals and their supporting evidence.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">VDC Workspace · <a href="../vote.php">Open citizen voting</a></p>
            </div>
        </div>
    </footer>

    <script src="vdc.js"></script>
    <script>
        var list = VDC.get().slice().sort(function (a, b) {
            return b.votes - a.votes;
        });

        var total = list.reduce(function (s, p) {
            return s + p.votes;
        }, 0);

        document.getElementById('total').textContent = total.toLocaleString() + ' total votes';

        document.getElementById('ranking').innerHTML = list.map(function (p, i) {
            var pct = total ? Math.round(p.votes / total * 100) : 0;

            return '<div class="d-flex align-items-center gap-3 mb-4">' +
                '<span class="vdc-rank ' + (i === 0 ? 'first' : '') + '">' + (i + 1) + '</span>' +
                '<div class="flex-grow-1">' +
                '<div class="d-flex justify-content-between gap-2 small mb-1">' +
                '<strong>' + p.title + '</strong>' +
                '<span class="vote-count">' + p.votes.toLocaleString() + '</span>' +
                '</div>' +
                '<div class="vdc-progress"><div class="progress-bar" style="width:' + pct + '%"></div></div>' +
                '<div class="vdc-kicker mt-1">' + p.area + ' · ' + p.sector + ' · ' + pct + '% of votes</div>' +
                '</div>' +
                '</div>';
        }).join('');
    </script>
</body>
</html>
