<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('contractor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Project &amp; Update Progress | Contractor Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="ddo.css" rel="stylesheet">
</head>
<body class="ddo-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>Contractor Workspace<small class="text-muted d-block">Contractor delivery</small></div>
        </a>
        <div><a class="btn btn-outline-gov btn-sm" href="projects.php"><i class="bi bi-arrow-left me-1"></i>Back to projects</a></div>
    </div>
</nav>

<main class="container py-5">
    <div id="alert"></div>
    <div id="content">
        <div class="ddo-panel text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Loading assigned project…</p>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">Contractor Workspace · <a href="../index.php">Citizen portal</a></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="ddo.js"></script>
<script>
    var id = new URLSearchParams(location.search).get('id');
    var currentProject = null;
    var currentAssignment = null;
    var currentUpdates = [];

    function render() {
        if (!currentProject) {
            document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">Project details could not be found.</div>';
            return;
        }

        var p = currentProject;
        var asgn = currentAssignment || {};
        var updates = currentUpdates || [];

        var updatesHtml = '';
        if (updates.length > 0) {
            updatesHtml = updates.map(function (u) {
                return '<div class="border-bottom pb-3 mb-3">' +
                    '<div class="d-flex justify-content-between align-items-center mb-1">' +
                        '<span class="badge bg-primary fs-6">' + u.percentage + '% Completed</span>' +
                        '<span class="small text-muted"><i class="bi bi-calendar3 me-1"></i>' + DDO.esc(u.update_date) + '</span>' +
                    '</div>' +
                    '<p class="mb-1 text-dark">' + DDO.esc(u.note) + '</p>' +
                    '<small class="text-muted">Recorded by: ' + DDO.esc(u.created_by_name || 'You') + ' · ' + DDO.esc(u.created_at) + '</small>' +
                '</div>';
            }).join('');
        } else {
            updatesHtml = '<p class="text-muted small mb-0">No progress updates submitted yet.</p>';
        }

        var today = new Date().toISOString().split('T')[0];

        var html = '<div class="row g-4">' +
            '<div class="col-lg-7">' +
                '<div class="ddo-panel mb-4">' +
                    '<div class="ddo-panel-header">' +
                        '<div class="ddo-eyebrow text-primary mb-1">' + DDO.esc(p.constituency || 'Constituency') + ' · ' + DDO.esc(p.sector || 'Sector') + '</div>' +
                        '<h1 class="h3 mb-2">' + DDO.esc(p.title) + '</h1>' +
                        '<div class="d-flex flex-wrap gap-2 align-items-center">' +
                            DDO.badge(p) +
                            '<span class="badge bg-light text-dark border"><i class="bi bi-geo-alt me-1"></i>' + DDO.esc(p.area) + '</span>' +
                            '<span class="badge bg-light text-dark border"><i class="bi bi-wallet2 me-1"></i>' + DDO.money(p.budget) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ddo-panel-body">' +
                        '<div class="mb-3">' +
                            '<label class="text-muted small fw-semibold d-block">Project Scope &amp; Details</label>' +
                            '<p class="mb-0">' + DDO.esc(p.description || 'No description.') + '</p>' +
                        '</div>' +
                        '<div class="row g-3 pt-3 border-top">' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted small d-block">Technical Supervisor</span>' +
                                '<strong>' + DDO.esc(asgn.supervisor || 'Not assigned') + '</strong>' +
                            '</div>' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted small d-block">Timeline</span>' +
                                '<strong>' + DDO.esc(asgn.start_date || 'Not set') + ' to ' + DDO.esc(asgn.target_date || 'Not set') + '</strong>' +
                            '</div>' +
                        '</div>' +
                        (asgn.scope ? '<div class="pt-3 border-top mt-3"><span class="text-muted small d-block">Contract Instructions / Scope</span><p class="mb-0 text-dark">' + DDO.esc(asgn.scope) + '</p></div>' : '') +
                    '</div>' +
                '</div>' +

                '<!-- Progress History -->' +
                '<div class="ddo-panel">' +
                    '<div class="ddo-panel-header d-flex justify-content-between align-items-center">' +
                        '<h2 class="h5 mb-0">Progress Update History</h2>' +
                        '<span class="badge bg-info text-dark fs-6">' + (p.progress || 0) + '% Current Progress</span>' +
                    '</div>' +
                    '<div class="ddo-panel-body">' +
                        '<div class="mb-4">' +
                            '<div class="d-flex justify-content-between small text-muted mb-1">' +
                                '<span>Current Status</span>' +
                                '<span class="fw-bold">' + (p.progress || 0) + '%</span>' +
                            '</div>' +
                            '<div class="progress" style="height: 12px;">' +
                                '<div class="progress-bar bg-success" role="progressbar" style="width: ' + (p.progress || 0) + '%;" aria-valuenow="' + (p.progress || 0) + '" aria-valuemin="0" aria-valuemax="100"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="progress-history-box p-3 bg-light rounded border">' +
                            updatesHtml +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="col-lg-5">' +
                '<!-- Progress Update Form -->' +
                '<div class="ddo-panel">' +
                    '<div class="ddo-panel-header">' +
                        '<h2 class="h5 mb-1">Submit Progress Update</h2>' +
                        '<p class="text-muted small mb-0">Update the delivery completion percentage and report</p>' +
                    '</div>' +
                    '<div class="ddo-panel-body">' +
                        '<form id="progressForm">' +
                            '<div class="mb-3">' +
                                '<label class="form-label small fw-bold" for="progressSlider">Completion Percentage (%): <span id="progressVal" class="text-primary fs-5">' + (p.progress || 0) + '%</span></label>' +
                                '<input type="range" class="form-range" id="progressSlider" name="percentage" min="0" max="100" step="1" value="' + (p.progress || 0) + '" oninput="document.getElementById(\'progressVal\').textContent = this.value + \'%\'">' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label small fw-bold" for="update_date">Date of Progress Update <span class="text-danger">*</span></label>' +
                                '<input type="date" class="form-control" id="update_date" name="update_date" value="' + today + '" required>' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label small fw-bold" for="note">Progress Details &amp; Work Completed <span class="text-danger">*</span></label>' +
                                '<textarea class="form-control" id="note" name="note" rows="5" placeholder="Describe specific works completed, milestones reached, or current status of delivery…" required></textarea>' +
                            '</div>' +
                            '<button type="submit" id="saveProgBtn" class="btn btn-cdf w-100 py-2 fw-semibold">' +
                                '<i class="bi bi-send-check me-2"></i>Submit Progress Update' +
                            '</button>' +
                        '</form>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

        document.getElementById('content').innerHTML = html;

        document.getElementById('progressForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var form = this;
            var percentage = form.percentage.value;
            var updateDate = form.update_date.value;
            var note = form.note.value.trim();

            if (!note) {
                document.getElementById('alert').innerHTML = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Please provide details about the progress completed.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                return;
            }

            var btn = document.getElementById('saveProgBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving progress…';

            DDO.updateProgress(p.id, percentage, note, updateDate, function (err, res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-check me-2"></i>Submit Progress Update';
                if (err) {
                    document.getElementById('alert').innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + DDO.esc(err.message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

                document.getElementById('alert').innerHTML = '<div class="alert alert-success alert-dismissible fade show" role="alert">Project progress update saved and visible to DDO!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                window.scrollTo({ top: 0, behavior: 'smooth' });

                loadData();
            });
        });
    }

    function loadData() {
        if (!id) {
            document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">No project ID specified. <a href="projects.php">Return to projects list</a></div>';
            return;
        }

        DDO.loadOne(id, function (err, proj, asgn, updates) {
            if (err) {
                document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">' + DDO.esc(err.message) + '</div>';
                return;
            }
            currentProject = proj;
            currentAssignment = asgn;
            currentUpdates = updates || [];
            render();
        });
    }

    loadData();
</script>
</body>
</html>