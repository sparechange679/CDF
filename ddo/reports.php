<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = current_user();
$tab = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reports and Results | DDO Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="ddo.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, nav, .gov-topbar, footer {
                display: none !important
            }

            .ddo-shell {
                background: #fff
            }

            .ddo-panel {
                box-shadow: none
            }
        }
    </style>
</head>
<body class="ddo-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php"><span class="ddo-brand-mark"><i
                        class="bi bi-clipboard2-pulse"></i></span>
            <div>DDO Workspace<small class="text-muted d-block">District delivery office</small></div>
        </a>
        <button class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#ddoNav'>
            <span class='navbar-toggler-icon'></span>
        </button>
        <div class='collapse navbar-collapse' id='ddoNav'>
            <ul class='navbar-nav ms-auto align-items-lg-center'>
                <li><a class='nav-link' href='index.php'>Overview</a></li>
                <li><a class='nav-link' href='projects.php'>Priority projects</a></li>
                <li><a class='nav-link active' href='reports.php'>Reports &amp; results</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="ddo-eyebrow text-primary">District delivery report</div>
            <h1 class="h2">Project reports and results</h1>
            <p class="text-muted mb-0">A generated view of assigned resources, delivery status, and recorded
                outcomes.</p></div>
        <button class="btn btn-cdf btn-sm no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i> Generate
            / print
        </button>
    </div>
    <div class="row g-3 mb-4" id="stats"></div>
    <div class="ddo-panel">
        <div class="ddo-panel-header"><h2 class="h5 mb-1">Delivery results</h2>
            <p class="text-muted small mb-0">Latest report and expected result for each approved project.</p></div>
        <div class="table-responsive">
            <table class="table ddo-table mb-0">
                <thead>
                <tr>
                    <th>Project</th>
                    <th>Supplier / supervisor</th>
                    <th>Timeline</th>
                    <th>Progress</th>
                    <th>Report and result</th>
                </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-4">Generated: 28 Aug 2026 · Reconcile with signed site reports, procurement records,
        and district finance returns.</p>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">DDO Workspace · <a href="../index.php">Citizen portal</a></div>
    </div>
</footer>

<script src="ddo.js"></script>
<script>
    let a = DDO.get(), funds = a.reduce(function (s, p) {
        return s + (p.allocation ? p.allocation.amount : 0)
    }, 0), done = a.filter(function (p) {
        return p.status === 'completed'
    }).length, assigned = a.filter(function (p) {
        return p.supplier
    }).length;
    document.getElementById('stats').innerHTML = [['bi-kanban', a.length, 'Approved projects', 'blue'], ['bi-wallet2', DDO.money(funds), 'Funds assigned', 'green'], ['bi-person-check', assigned, 'Delivery teams assigned', 'gold'], ['bi-check-circle', done, 'Completed results', 'red']].map(function (x) {
        return '<div class="col-6 col-lg-3"><div class="ddo-panel ddo-stat ' + x[3] + '"><i class="bi ' + x[0] + ' text-muted"></i><div class="number">' + x[1] + '</div><div class="label">' + x[2] + '</div></div></div>'
    }).join('');
    document.getElementById('rows').innerHTML = a.map(function (p) {
        return '<tr><td><strong>' + p.title + '</strong><div class="ddo-kicker">' + p.area + ' · ' + DDO.badge(p) + '</div></td><td class="small">' + (p.supplier || 'Pending assignment') + '<br><span class="ddo-kicker">' + (p.supervisor || 'No supervisor') + '</span></td><td class="small">' + (p.start || 'Not set') + '<br>' + (p.end || 'Not set') + '</td><td><strong>' + p.progress + '%</strong><div class="ddo-progress mt-1"><div class="progress-bar" style="width:' + p.progress + '%"></div></div></td><td class="small"><strong>Report:</strong> ' + (p.report || 'Pending') + '<br><strong>Result:</strong> ' + (p.result || 'Pending') + '</td></tr>'
    }).join('') || '<tr><td colspan="5" class="ddo-empty">No approved project reports are available.</td></tr>';
</script>
</body>
</html>