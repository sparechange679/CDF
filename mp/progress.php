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
            <div class="mp-eyebrow text-dark">Delivery monitoring</div>
            <h1 class="h2">Project progress and status updates</h1>
            <p class="text-muted">Record the latest verified progress note for projects in implementation.</p>
        </div>

        <div id="list" class="row g-4"></div>
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
        var all = MP.get();

        function render() {
            document.getElementById('list').innerHTML = all.filter(function (p) {
                return ['accepted', 'progress', 'completed'].indexOf(p.status) > -1;
            }).map(function (p) {
                return '<div class="col-lg-6">' +
                    '<div class="mp-panel">' +
                    '<div class="mp-panel-body">' +
                    '<div class="d-flex justify-content-between gap-2">' +
                    '<div>' +
                    '<h2 class="h5 mb-1">' + p.title + '</h2>' +
                    '<div class="mp-kicker">' + p.area + ' · ' + p.sector + '</div>' +
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
                    '<p class="small text-muted">' +
                    (p.lastUpdate
                        ? 'Last update: ' + p.lastUpdate + ' · ' + p.updateNote
                        : 'No MP progress update recorded yet.') +
                    '</p>' +
                    '<form class="update row g-2" data-id="' + p.id + '">' +
                    '<div class="col-4">' +
                    '<input name="progress" type="number" min="0" max="100" value="' + (p.progress || 0) + '" class="form-control" aria-label="Progress percentage">' +
                    '</div>' +
                    '<div class="col-8">' +
                    '<input name="date" class="form-control" required placeholder="Update date">' +
                    '</div>' +
                    '<div class="col-12">' +
                    '<input name="note" class="form-control" required placeholder="Verified progress note">' +
                    '</div>' +
                    '<div class="col-12">' +
                    '<button class="btn btn-cdf btn-sm" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Save update</button>' +
                    '</div>' +
                    '</form>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }).join('') || '<div class="col-12"><div class="mp-panel mp-empty">No accepted projects are ready for progress tracking.</div></div>';

            document.querySelectorAll('.update').forEach(function (f) {
                f.addEventListener('submit', function (e) {
                    e.preventDefault();
                    MP.updateProgress(this.dataset.id, this);
                    all = MP.get();
                    render();
                });
            });
        }

        render();
    </script>
</body>
</html>
