<?php
include 'includes/head.php';
require_role('ddo');
?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>DDO Workspace<small class="text-muted d-block">District delivery office</small></div>
        </a>
        <div><a class="btn btn-outline-gov btn-sm" href="projects.php"><i class="bi bi-arrow-left me-1"></i>Back to projects</a></div>
    </div>
</nav>

<main class="container py-5">
    <div id="alert"></div>
    <div id="content">
        <div class="ddo-panel text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Loading project details…</p>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">DDO Workspace · <a href="../index.php">Citizen portal</a></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src='ddo.js?v=20261020'></script>
<script>
    let id = new URLSearchParams(location.search).get('id');
    let currentProject = null;
    let allContractors = [];
    let currentAssignment = null;
    let currentUpdates = [];

    function render() {
        if (!currentProject) {
            document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">Project details could not be found.</div>';
            return;
        }

        let p = currentProject;
        let asgn = currentAssignment || {};
        let updates = currentUpdates || [];

        let contractorOptions = '<option value="">-- Select an available contractor --</option>';
        allContractors.forEach(function (c) {
            let selected = (asgn.contractor_id && Number(asgn.contractor_id) === Number(c.id)) ? ' selected' : '';
            let title = c.company_name ? (c.company_name + ' (' + c.full_name + ')') : c.full_name;
            contractorOptions += '<option value="' + DDO.esc(c.id) + '"' + selected + '>' + DDO.esc(title) + '</option>';
        });

        let updatesHtml = '';
        if (updates.length > 0) {
            updatesHtml = updates.map(function (u) {
                return '<div class="border-bottom pb-3 mb-3">' +
                    '<div class="d-flex justify-content-between align-items-center mb-1">' +
                        '<span class="badge bg-primary fs-6">' + u.percentage + '% Complete</span>' +
                        '<span class="small text-muted"><i class="bi bi-calendar3 me-1"></i>' + DDO.esc(u.update_date) + '</span>' +
                    '</div>' +
                    '<p class="mb-1 text-dark">' + DDO.esc(u.note) + '</p>' +
                    '<small class="text-muted">Reported by: ' + DDO.esc(u.created_by_name || 'Contractor') + ' · ' + DDO.esc(u.created_at) + '</small>' +
                '</div>';
            }).join('');
        } else {
            updatesHtml = '<p class="text-muted small mb-0">No progress updates have been submitted by the contractor yet.</p>';
        }

        let html = '<div class="row g-4">' +
            '<div class="col-lg-7">' +
                '<div class="ddo-panel mb-4">' +
                    '<div class="ddo-panel-header">' +
                        '<div class="ddo-eyebrow text-primary mb-1">' + DDO.esc(p.constituency || 'Constituency') + ' · ' + DDO.esc(p.sector || 'Sector') + '</div>' +
                        '<h1 class="h3 mb-2">' + DDO.esc(p.title) + '</h1>' +
                        '<div class="d-flex flex-wrap gap-2 align-items-center">' +
                            DDO.badge(p) +
                            '<span class="badge bg-light text-dark border"><i class="bi bi-geo-alt me-1"></i>' + DDO.esc(p.area) + '</span>' +
                            '<span class="badge bg-light text-dark border"><i class="bi bi-check2-square me-1"></i>' + Number(p.votes || 0).toLocaleString() + ' votes</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ddo-panel-body">' +
                        '<div class="mb-3">' +
                            '<label class="text-muted small fw-semibold d-block">Description</label>' +
                            '<p class="mb-0">' + DDO.esc(p.description || 'No description provided.') + '</p>' +
                        '</div>' +
                        '<div class="row g-3 pt-3 border-top">' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted small d-block">Approved Budget</span>' +
                                '<strong class="fs-5 text-success">' + DDO.money(p.budget) + '</strong>' +
                            '</div>' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted small d-block">Estimated Beneficiaries</span>' +
                                '<strong>' + (p.beneficiaries ? Number(p.beneficiaries).toLocaleString() + ' citizens' : 'Not specified') + '</strong>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<!-- Progress & Status View -->' +
                '<div class="ddo-panel mb-4">' +
                    '<div class="ddo-panel-header d-flex justify-content-between align-items-center">' +
                        '<div>' +
                            '<h2 class="h5 mb-0">Project Progress & Status</h2>' +
                            '<p class="text-muted small mb-0">Contractor recorded delivery progress</p>' +
                        '</div>' +
                        '<span class="badge bg-info text-dark fs-6">' + (p.progress || 0) + '% Completed</span>' +
                    '</div>' +
                    '<div class="ddo-panel-body">' +
                        '<div class="mb-4">' +
                            '<div class="d-flex justify-content-between small text-muted mb-1">' +
                                '<span>Progress</span>' +
                                '<span class="fw-bold">' + (p.progress || 0) + '%</span>' +
                            '</div>' +
                            '<div class="progress" style="height: 12px;">' +
                                '<div class="progress-bar bg-success" role="progressbar" style="width: ' + (p.progress || 0) + '%;" aria-valuenow="' + (p.progress || 0) + '" aria-valuemin="0" aria-valuemax="100"></div>' +
                            '</div>' +
                        '</div>' +
                        '<h3 class="h6 fw-bold mb-3">Progress Update History</h3>' +
                        '<div class="progress-history-box p-3 bg-light rounded border">' +
                            updatesHtml +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="col-lg-5">' +
                '<!-- Contractor Assignment Form -->' +
                '<div class="ddo-panel">' +
                    '<div class="ddo-panel-header">' +
                        '<h2 class="h5 mb-1">Contractor Assignment</h2>' +
                        '<p class="text-muted small mb-0">Assign this project to an available contractor</p>' +
                    '</div>' +
                    '<div class="ddo-panel-body">' +
                        '<form id="assignForm">' +
                            '<div class="mb-3">' +
                                '<label class="form-label small fw-bold" for="contractor_id">Available Contractor <span class="text-danger">*</span></label>' +
                                '<select class="form-select" id="contractor_id" name="contractor_id" required>' +
                                    contractorOptions +
                                '</select>' +
                                '<div class="form-text small">Only active registered contractors are listed.</div>' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label small fw-bold" for="supervisor">Technical Supervisor</label>' +
                                '<input type="text" class="form-control" id="supervisor" name="supervisor" placeholder="e.g. Eng. Patrick Banda" value="' + DDO.esc(asgn.supervisor || '') + '">' +
                            '</div>' +
                            '<div class="row g-2 mb-3">' +
                                '<div class="col-6">' +
                                    '<label class="form-label small fw-bold" for="start">Start Date</label>' +
                                    '<input type="date" class="form-control" id="start" name="start" value="' + DDO.esc(asgn.start_date || '') + '">' +
                                '</div>' +
                                '<div class="col-6">' +
                                    '<label class="form-label small fw-bold" for="end">Target Completion</label>' +
                                    '<input type="date" class="form-control" id="end" name="end" value="' + DDO.esc(asgn.target_date || '') + '">' +
                                '</div>' +
                            '</div>' +
                            '<div class="mb-3">' +
                                '<label class="form-label small fw-bold" for="scope">Scope of Work / Instructions</label>' +
                                '<textarea class="form-control" id="scope" name="scope" rows="3" placeholder="Provide contractual scope, milestones or instructions">' + DDO.esc(asgn.scope || '') + '</textarea>' +
                            '</div>' +
                            '<div class="mb-4">' +
                                '<label class="form-label small fw-bold" for="asgn_status">Assignment Status</label>' +
                                '<select class="form-select" id="asgn_status" name="status">' +
                                    '<option value="assigned"' + (asgn.assignment_status === 'assigned' ? ' selected' : '') + '>Assigned (Pending Start)</option>' +
                                    '<option value="active"' + (asgn.assignment_status === 'active' ? ' selected' : '') + '>Active (In Progress)</option>' +
                                    '<option value="completed"' + (asgn.assignment_status === 'completed' ? ' selected' : '') + '>Completed</option>' +
                                    '<option value="blocked"' + (asgn.assignment_status === 'blocked' ? ' selected' : '') + '>Blocked / On Hold</option>' +
                                '</select>' +
                            '</div>' +
                            '<button type="submit" id="saveBtn" class="btn btn-cdf w-100 py-2 fw-semibold">' +
                                '<i class="bi bi-person-check me-2"></i>' + (asgn.contractor_id ? 'Update Contractor Assignment' : 'Assign Project to Contractor') +
                            '</button>' +
                        '</form>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

        document.getElementById('content').innerHTML = html;

        document.getElementById('assignForm').addEventListener('submit', function (e) {
            e.preventDefault();
            let form = this;
            let contractorId = form.contractor_id.value;
            let supervisor = form.supervisor.value.trim();
            let scope = form.scope.value.trim();
            let start = form.start.value;
            let end = form.end.value;
            let status = form.status.value;

            if (!contractorId) {
                document.getElementById('alert').innerHTML = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Please select an available contractor.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                return;
            }

            let btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving assignment…';

            DDO.assignContractor(p.id, contractorId, supervisor, scope, start, end, status, function (err, res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-check me-2"></i>Update Contractor Assignment';
                if (err) {
                    document.getElementById('alert').innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + DDO.esc(err.message) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

                document.getElementById('alert').innerHTML = '<div class="alert alert-success alert-dismissible fade show" role="alert">Project successfully assigned to contractor!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                window.scrollTo({ top: 0, behavior: 'smooth' });

                // Reload data
                loadData();
            });
        });
    }

    function loadData() {
        if (!id) {
            document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">No project ID specified. <a href="projects.php">Return to projects list</a></div>';
            return;
        }

        DDO.loadOne(id, function (err, proj, contractors, asgn, updates) {
            if (err) {
                document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">' + DDO.esc(err.message) + '</div>';
                return;
            }
            currentProject = proj;
            allContractors = contractors || [];
            currentAssignment = asgn;
            currentUpdates = updates || [];
            render();
        });
    }

    loadData();
</script>
</body>
</html>