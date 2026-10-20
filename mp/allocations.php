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
            <div>
                <a class="btn btn-outline-gov btn-sm" href="index.php">Overview</a>
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
                    <tbody id="rows"></tbody>
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

    <script src="mp.js"></script>
    <script>
        var a = MP.get();

        document.getElementById('rows').innerHTML = a.map(function (p) {
            var x = p.allocation;

            return '<tr>' +
                '<td>' +
                '<strong>' + p.title + '</strong>' +
                '<div class="mp-kicker">' + p.area + ' · ' + MP.badge(p) + '</div>' +
                '</td>' +
                '<td class="fw-semibold">' + (x ? MP.money(x.amount) : '<span class="text-muted">Pending</span>') + '</td>' +
                '<td>' + (x ? x.ddo : '<span class="text-muted">Not assigned</span>') + '</td>' +
                '<td class="small">' + (x ? x.reference + '<br>' + x.date : 'Awaiting DDO record') + '</td>' +
                '<td class="small">' + (x ? x.scope : 'No approved scope recorded') + '</td>' +
                '<td><a class="btn btn-outline-gov btn-sm" href="review.php?id=' + p.id + '">Open</a></td>' +
                '</tr>';
        }).join('');
    </script>
</body>
</html>
