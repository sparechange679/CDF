<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('mp', '../auth.php');
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
            <button class='navbar-toggler' data-bs-toggle='collapse' data-bs-target='#nav'>
                <span class='navbar-toggler-icon'></span>
            </button>
            <div class='collapse navbar-collapse' id='nav'>
                <ul class='navbar-nav ms-auto'>
                    <li><a class='nav-link' href='index.php'>Overview</a></li>
                    <li><a class='nav-link active' href='projects.php'>All proposals</a></li>
                    <li><a class='nav-link' href='decisions.php'>Decisions</a></li>
                    <li><a class='nav-link' href='allocations.php'>Funds &amp; DDO</a></li>
                    <li><a class='nav-link' href='progress.php'>Progress</a></li>
                    <li><a class='nav-link' href='reports.php'>Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div id="alert"></div>
        <div id="content"><div class="mp-panel mp-empty">Loading project details…</div></div>
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
        var id = new URLSearchParams(location.search).get('id');
        var currentProject = null;
        var currentComments = [];

        function render() {
            var p = currentProject;
            var cs = currentComments || [];

            if (!p) {
                document.getElementById('content').innerHTML = '<div class="mp-panel mp-empty">No project selected. <a href="projects.php">Return to proposals</a></div>';
                return;
            }

            var esc = MP.esc;

            document.getElementById('content').innerHTML =
                '<div class="row g-4">' +
                '<div class="col-lg-8">' +
                '<div class="mp-panel">' +
                '<div class="mp-panel-header d-flex justify-content-between align-items-start gap-3">' +
                '<div>' +
                '<div class="mp-kicker">' + esc(p.proposer || 'Council') + ' · ' + esc(p.submitted || '') + (p.reference ? ' · ' + esc(p.reference) : '') + '</div>' +
                '<h1 class="h3 mb-1">' + esc(p.title) + '</h1>' +
                '<p class="text-muted mb-0">' + esc(p.area) + ' (' + esc(p.constituency) + ') · ' + esc(p.sector) + ' · ' + MP.money(p.budget) + '</p>' +
                '</div>' +
                MP.badge(p) +
                '</div>' +
                '<div class="mp-panel-body">' +
                (p.description ? '<div class="mb-4"><h2 class="h6 text-muted">Project scope &amp; description</h2><p class="mb-0">' + esc(p.description) + '</p></div><hr class="my-4">' : '') +
                '<div class="row g-3 mb-4">' +
                '<div class="col-6 col-md-4">' +
                '<div class="mp-callout">' +
                '<small class="mp-kicker">Citizen votes</small>' +
                '<div class="h4 mb-0">' + Number(p.votes || 0).toLocaleString() + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-6 col-md-4">' +
                '<div class="mp-callout">' +
                '<small class="mp-kicker">Current progress</small>' +
                '<div class="h4 mb-0">' + (p.progress || 0) + '%</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-12 col-md-4">' +
                '<div class="mp-callout">' +
                '<small class="mp-kicker">Beneficiaries</small>' +
                '<div class="h4 mb-0">' + (p.beneficiaries ? Number(p.beneficiaries).toLocaleString() : '—') + '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<h2 class="h5"><i class="bi bi-shield-check me-2"></i>Record MP Decision &amp; Status Change</h2>' +
                '<p class="small text-muted">Approve, reject, or update the status of this constituency proposal. Provide a required reason and an oversight comment for the public audit trail.</p>' +
                '<form id="decision-form" class="row g-3">' +
                '<div class="col-md-5">' +
                '<label class="form-label small fw-semibold" for="decision-status">Status decision</label>' +
                '<select id="decision-status" name="decision" class="form-select" required>' +
                '<option value="accepted"' + (p.status === 'accepted' ? ' selected' : '') + '>Approve proposal (Accepted)</option>' +
                '<option value="denied"' + (p.status === 'denied' ? ' selected' : '') + '>Reject proposal (Denied)</option>' +
                '<option value="review"' + (p.status === 'review' ? ' selected' : '') + '>Mark under review</option>' +
                '<option value="progress"' + (p.status === 'progress' ? ' selected' : '') + '>Move to implementation</option>' +
                '<option value="completed"' + (p.status === 'completed' ? ' selected' : '') + '>Mark as completed</option>' +
                '</select>' +
                '</div>' +
                '<div class="col-md-7">' +
                '<label class="form-label small fw-semibold" for="decision-reason">Reason for status change <span class="text-danger">*</span></label>' +
                '<input id="decision-reason" name="reason" class="form-control" required placeholder="Mandatory reason for this decision / status change" value="' + esc(p.reason || '') + '">' +
                '</div>' +
                '<div class="col-12">' +
                '<label class="form-label small fw-semibold" for="decision-comment">Decision comment (optional note to record in timeline)</label>' +
                '<textarea id="decision-comment" name="comment" rows="2" class="form-control" placeholder="Add additional remarks or instructions for the council / DDO"></textarea>' +
                '</div>' +
                '<div class="col-12 d-flex align-items-center">' +
                '<button class="btn btn-cdf" type="submit"><i class="bi bi-check2-square me-2"></i>Save decision &amp; reason</button>' +
                '<span id="saved" class="small text-success ms-3 fw-semibold"></span>' +
                '</div>' +
                '</form>' +
                '<hr class="my-4">' +
                '<h2 class="h5"><i class="bi bi-chat-left-text me-2"></i>MP office comments &amp; timeline</h2>' +
                '<div id="comments" class="mb-3">' +
                (cs.length
                    ? cs.map(function (c) {
                        return '<div class="mp-comment mb-3">' +
                            '<div>' + esc(c.text) + '</div>' +
                            '<small>' + esc(c.author || 'MP Office') + ' · ' + esc(c.date || '') + '</small>' +
                            '</div>';
                    }).join('')
                    : '<p class="small text-muted">No comments recorded yet.</p>') +
                '</div>' +
                '<form id="comment-form" class="d-flex gap-2">' +
                '<input name="text" class="form-control" required placeholder="Add a public oversight comment">' +
                '<button class="btn btn-outline-gov" type="submit">Post comment</button>' +
                '</form>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="col-lg-4">' +
                '<div class="mp-panel mb-4">' +
                '<div class="mp-panel-header">' +
                '<h2 class="h5 mb-0">Project details</h2>' +
                '</div>' +
                '<div class="mp-panel-body small">' +
                '<p><strong>Constituency</strong><br>' + esc(p.constituency) + '</p>' +
                '<p><strong>Submitted by</strong><br>' + esc(p.proposer || 'Council') + '</p>' +
                '<p><strong>Location</strong><br>' + esc(p.area) + '</p>' +
                '<p><strong>Funding request</strong><br>' + MP.money(p.budget) + '</p>' +
                '<p class="mb-0">' +
                '<strong>Decision status</strong><br>' +
                MP.badge(p) +
                (p.decision_date ? '<div class="small text-muted mt-1">Decided on ' + esc(p.decision_date) + '</div>' : '') +
                (p.reason ? '<div class="text-danger mt-2"><strong>Reason:</strong> ' + esc(p.reason) + '</div>' : '') +
                '</p>' +
                '</div>' +
                '</div>' +
                (p.allocation ?
                '<div class="mp-panel">' +
                '<div class="mp-panel-header"><h2 class="h6 mb-0">Allocation &amp; DDO</h2></div>' +
                '<div class="mp-panel-body small">' +
                '<p><strong>Amount:</strong> ' + MP.money(p.allocation.amount) + '</p>' +
                '<p><strong>Source:</strong> ' + esc(p.allocation.source) + '</p>' +
                '<p><strong>DDO:</strong> ' + esc(p.allocation.ddo) + '</p>' +
                '<p><strong>Reference:</strong> ' + esc(p.allocation.reference) + '</p>' +
                '<p class="mb-0"><strong>Scope:</strong> ' + esc(p.allocation.scope) + '</p>' +
                '</div>' +
                '</div>' : '') +
                '</div>' +
                '</div>';

            document.getElementById('decision-form').addEventListener('submit', function (e) {
                e.preventDefault();

                var d = this.decision.value;
                var r = this.reason.value.trim();
                var c = this.comment.value.trim();

                if (!r) {
                    this.reason.focus();
                    return;
                }

                var btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;

                MP.postDecision(p.id, d, r, c, function (err, res) {
                    btn.disabled = false;
                    if (err) {
                        alert(err.message || 'Error saving decision.');
                        return;
                    }
                    loadData(function () {
                        var saveMsg = document.getElementById('saved');
                        if (saveMsg) {
                            saveMsg.textContent = 'Decision and reason recorded successfully.';
                        }
                    });
                });
            });

            document.getElementById('comment-form').addEventListener('submit', function (e) {
                e.preventDefault();
                var text = this.text.value.trim();
                if (!text) return;

                var btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;

                MP.postComment(p.id, text, function (err, res) {
                    btn.disabled = false;
                    if (err) {
                        alert(err.message || 'Error posting comment.');
                        return;
                    }
                    loadData();
                });
            });
        }

        function loadData(cb) {
            if (!id) {
                // If no id in URL, load first project in constituency
                MP.load(function (err, projects, constituency) {
                    if (err || !projects.length) {
                        document.getElementById('content').innerHTML = '<div class="mp-panel mp-empty">No projects found in your constituency. <a href="projects.php">Back to proposals</a></div>';
                        return;
                    }
                    id = projects[0].id;
                    loadData(cb);
                });
                return;
            }

            MP.loadOne(id, function (err, project, comments) {
                if (err) {
                    document.getElementById('content').innerHTML = '<div class="alert alert-danger">' + MP.esc(err.message) + ' <a href="projects.php" class="ms-2">Back to proposals</a></div>';
                    return;
                }
                currentProject = project;
                currentComments = comments;
                render();
                if (cb) cb();
            });
        }

        loadData();
    </script>
</body>
</html>
