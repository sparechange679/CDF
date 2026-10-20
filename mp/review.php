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
    <title>Review Proposal | MP Workspace</title>
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
                <a class="btn btn-outline-gov btn-sm" href="projects.php">Back to proposals</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div id="content"></div>
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
        var id = new URLSearchParams(location.search).get('id');
        var p = MP.get().filter(function (x) {
            return x.id === id;
        })[0] || MP.get()[0];

        function render() {
            var cs = MP.comments().filter(function (c) {
                return c.id === p.id;
            });

            document.getElementById('content').innerHTML =
                '<div class="row g-4">' +
                '<div class="col-lg-8">' +
                '<div class="mp-panel">' +
                '<div class="mp-panel-header d-flex justify-content-between gap-3">' +
                '<div>' +
                '<div class="mp-kicker">' + p.proposer + ' · ' + p.submitted + '</div>' +
                '<h1 class="h3 mb-1">' + p.title + '</h1>' +
                '<p class="text-muted mb-0">' + p.area + ' · ' + p.sector + ' · ' + MP.money(p.budget) + '</p>' +
                '</div>' +
                MP.badge(p) +
                '</div>' +
                '<div class="mp-panel-body">' +
                '<div class="row g-3 mb-4">' +
                '<div class="col-6">' +
                '<div class="mp-callout">' +
                '<small class="mp-kicker">Citizen votes</small>' +
                '<div class="h4 mb-0">' + p.votes.toLocaleString() + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-6">' +
                '<div class="mp-callout">' +
                '<small class="mp-kicker">Current progress</small>' +
                '<div class="h4 mb-0">' + (p.progress || 0) + '%</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<h2 class="h5">MP decision</h2>' +
                '<p class="small text-muted">Record an approval or rejection. A rejection reason is required for the public decision trail.</p>' +
                '<form id="decision" class="row g-3">' +
                '<div class="col-md-4">' +
                '<select name="decision" class="form-select" required>' +
                '<option value="accepted">Approve project</option>' +
                '<option value="denied">Reject project</option>' +
                '</select>' +
                '</div>' +
                '<div class="col-md-8">' +
                '<input name="reason" class="form-control" placeholder="Decision note or reason (required for rejection)">' +
                '</div>' +
                '<div class="col-12">' +
                '<button class="btn btn-cdf" type="submit"><i class="bi bi-check2-square me-2"></i>Save decision</button>' +
                '<span id="saved" class="small text-success ms-3"></span>' +
                '</div>' +
                '</form>' +
                '<hr class="my-4">' +
                '<h2 class="h5">MP office comments</h2>' +
                '<div id="comments">' +
                (cs.length
                    ? cs.map(function (c) {
                        return '<div class="mp-comment mb-3">' +
                            '<div>' + c.text + '</div>' +
                            '<small>' + c.author + ' · ' + c.date + '</small>' +
                            '</div>';
                    }).join('')
                    : '<p class="small text-muted">No comments recorded yet.</p>') +
                '</div>' +
                '<form id="comment" class="d-flex gap-2">' +
                '<input name="text" class="form-control" required placeholder="Add a public oversight comment">' +
                '<button class="btn btn-outline-gov" type="submit">Post</button>' +
                '</form>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-lg-4">' +
                '<div class="mp-panel">' +
                '<div class="mp-panel-header">' +
                '<h2 class="h5 mb-0">Project record</h2>' +
                '</div>' +
                '<div class="mp-panel-body small">' +
                '<p><strong>Submitted by</strong><br>' + p.proposer + '</p>' +
                '<p><strong>Location</strong><br>' + p.area + '</p>' +
                '<p><strong>Funding request</strong><br>' + MP.money(p.budget) + '</p>' +
                '<p class="mb-0">' +
                '<strong>Decision status</strong><br>' +
                MP.badge(p) +
                (p.reason ? '<br><span class="text-danger mt-2 d-block">' + p.reason + '</span>' : '') +
                '</p>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';

            document.getElementById('decision').addEventListener('submit', function (e) {
                e.preventDefault();

                var d = this.decision.value;
                var r = this.reason.value.trim();

                if (d === 'denied' && !r) {
                    this.reason.focus();
                    return;
                }

                MP.setDecision(p.id, d, r);
                p = MP.get().filter(function (x) {
                    return x.id === p.id;
                })[0];
                render();
                document.getElementById('saved').textContent = 'Decision saved';
            });

            document.getElementById('comment').addEventListener('submit', function (e) {
                e.preventDefault();
                MP.addComment(p.id, this.text.value.trim());
                this.reset();
                render();
            });
        }

        render();
    </script>
</body>
</html>
