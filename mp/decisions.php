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

    <div class="gov-topbar">
        <div class="container d-flex justify-content-between">
            <span><i class="bi bi-building me-1"></i> Official Government of Malawi Portal</span>
            <span>Member of Parliament Workspace</span>
        </div>
    </div>

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
                    <tbody id="rows"></tbody>
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

    <script src="mp.js"></script>
    <script>
        var a = MP.get().filter(function (p) {
            return ['accepted', 'denied', 'progress', 'completed'].indexOf(p.status) > -1;
        });

        document.getElementById('rows').innerHTML = a.map(function (p) {
            return '<tr>' +
                '<td>' +
                '<strong>' + p.title + '</strong>' +
                '<div class="mp-kicker">' + p.area + ' · ' + p.votes.toLocaleString() + ' citizen votes</div>' +
                '</td>' +
                '<td>' + (p.decisionDate || 'Inherited from VDC') + '</td>' +
                '<td>' + MP.badge(p) + '</td>' +
                '<td class="small">' + (p.reason || 'Approved for funding and delivery.') + '</td>' +
                '<td><a class="btn btn-outline-gov btn-sm" href="review.php?id=' + p.id + '">Open record</a></td>' +
                '</tr>';
        }).join('') || '<tr><td colspan="5" class="mp-empty">No decisions recorded yet.</td></tr>';
    </script>
</body>
</html>
