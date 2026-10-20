<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('council', '../auth.php');   // only District Council / VDC accounts may access this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Decisions &amp; Voting | DC Workspace</title>
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
                DC Workspace
                <small class="text-muted d-block">District Council</small>
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
                <li><a class="nav-link active" href="decisions.php">Decisions</a></li>
                <li><a class="nav-link" href="priorities.php">Priorities</a></li>
                <li><a class="nav-link" href="reports.php">Reports</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="vdc-hero">
    <div class="container">
        <div class="vdc-eyebrow">Decision &amp; Voting Register</div>
        <h1 class="h2">Project Decisions &amp; Citizen Voting</h1>
        <p class="mb-0">Track review outcomes, start or stop citizen voting windows on proposals, and review recorded committee reasons.</p>
    </div>
</header>

<main class="container py-5">
    <div id="alert"></div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="vdc-panel vdc-stat">
                <div class="number" id="stat-total">0</div>
                <div class="label">Total proposals</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="vdc-panel vdc-stat" style="border-left: 4px solid var(--bs-success, #28a745);">
                <div class="number text-success" id="stat-open">0</div>
                <div class="label">Voting Active</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="vdc-panel vdc-stat gold">
                <div class="number text-warning" id="stat-accepted">0</div>
                <div class="label">Accepted projects</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="vdc-panel vdc-stat blue">
                <div class="number" id="stat-votes">0</div>
                <div class="label">Total citizen votes</div>
            </div>
        </div>
    </div>

    <!-- Main voting control panel -->
    <div class="vdc-panel mb-4">
        <div class="vdc-panel-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h5 mb-1"><i class="bi bi-hand-thumbs-up me-2 text-primary"></i>Citizen Voting Control &amp; Status</h2>
                    <p class="text-muted small mb-0">Start or stop citizen voting for any project. Citizens can only vote on projects where voting is currently open.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <input id="q" class="form-control form-control-sm" style="max-width: 200px;" placeholder="Search projects…">
                    <select id="voteFilter" class="form-select form-select-sm" style="max-width: 170px;">
                        <option value="">All voting states</option>
                        <option value="open">Voting Open</option>
                        <option value="closed">Voting Closed</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table vdc-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Budget</th>
                        <th>Decision Status</th>
                        <th>Voting Status</th>
                        <th class="text-end">Citizen Votes</th>
                        <th class="text-end">Voting Action</th>
                    </tr>
                </thead>
                <tbody id="all-rows">
                    <tr><td colspan="6" class="vdc-empty">Loading project decisions…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="vdc-panel h-100">
                <div class="vdc-panel-header">
                    <h2 class="h5 mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Accepted for development</h2>
                    <p class="text-muted small mb-0">Projects approved for funding or implementation.</p>
                </div>
                <div class="table-responsive">
                    <table class="table vdc-table mb-0">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th class="text-end">Votes</th>
                            </tr>
                        </thead>
                        <tbody id="accepted"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="vdc-panel h-100">
                <div class="vdc-panel-header">
                    <h2 class="h5 mb-1"><i class="bi bi-x-circle-fill text-danger me-2"></i>Denied or returned</h2>
                    <p class="text-muted small mb-0">Reasons are kept visible for accountability and resubmission.</p>
                </div>
                <div class="vdc-panel-body" id="denied"></div>
            </div>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">
            <p class="mb-0">DC Workspace · <a href="../index.php">Citizen portal</a> · <a href="../vote.php">Citizen voting portal</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";

        var allProjects = [];

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function money(n) {
            return 'MK ' + Number(n).toLocaleString('en-GB', { maximumFractionDigits: 2 });
        }

        function badge(p) {
            return '<span class="vdc-status ' + esc(p.status) + '">' +
                '<i class="bi bi-circle-fill" style="font-size:.42rem"></i>' + esc(p.statusLabel) + '</span>';
        }

        function voteBadge(p) {
            var isOpen = Number(p.voting_open) === 1;
            if (isOpen) {
                return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-broadcast me-1"></i>Voting Open</span>';
            }
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-pause-circle me-1"></i>Voting Closed</span>';
        }

        function showAlert(type, msg) {
            var box = document.getElementById('alert');
            box.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                esc(msg) +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function csrf() {
            return fetch('../api/csrf.php', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) { return d.token; });
        }

        function toggleVoting(projectId, startVoting) {
            var action = startVoting ? 'start_voting' : 'stop_voting';
            var btn = document.querySelector('button[data-id="' + projectId + '"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Updating…';
            }

            csrf().then(function (token) {
                return fetch('../api/projects.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
                    body: JSON.stringify({
                        action: action,
                        project_id: projectId
                    })
                });
            }).then(function (r) {
                return r.json().catch(function () {
                    return { ok: false, error: 'Invalid response from server.' };
                });
            }).catch(function () {
                return { ok: false, error: 'Could not connect to the server.' };
            }).then(function (res) {
                if (!res.ok) {
                    showAlert('danger', res.error || 'Failed to update voting status.');
                    render();
                    return;
                }

                // Update in-memory project state
                var p = allProjects.find(function (item) { return item.id === projectId; });
                if (p) {
                    p.voting_open = res.voting_open;
                    if (res.votes !== undefined) {
                        p.votes = res.votes;
                    }
                }

                showAlert('success', res.message || ('Voting has been ' + (startVoting ? 'started' : 'stopped') + ' for this project.'));
                render();
            });
        }

        function render() {
            var q = (document.getElementById('q').value || '').toLowerCase().trim();
            var vf = document.getElementById('voteFilter').value;

            // Update header stats
            var openVotingCount = allProjects.filter(function (p) { return Number(p.voting_open) === 1; }).length;
            var acceptedCount = allProjects.filter(function (p) { return ['accepted', 'progress', 'completed'].indexOf(p.status) > -1; }).length;
            var totalVotes = allProjects.reduce(function (sum, p) { return sum + Number(p.votes || 0); }, 0);

            document.getElementById('stat-total').textContent = allProjects.length;
            document.getElementById('stat-open').textContent = openVotingCount;
            document.getElementById('stat-accepted').textContent = acceptedCount;
            document.getElementById('stat-votes').textContent = totalVotes.toLocaleString();

            // Filter for all proposals table
            var filtered = allProjects.filter(function (p) {
                var hay = [p.title, p.reference, p.area, p.sector].join(' ').toLowerCase();
                var matchQ = !q || hay.indexOf(q) > -1;
                var isOpen = Number(p.voting_open) === 1;
                var matchV = !vf || (vf === 'open' && isOpen) || (vf === 'closed' && !isOpen);
                return matchQ && matchV;
            });

            document.getElementById('all-rows').innerHTML = filtered.length
                ? filtered.map(function (p) {
                    var isOpen = Number(p.voting_open) === 1;
                    return '<tr>' +
                        '<td>' +
                        '<strong>' + esc(p.title) + '</strong>' +
                        '<div class="vdc-kicker">' + esc(p.reference) + ' · ' + esc(p.area) + ' · ' + esc(p.sector) + '</div>' +
                        '</td>' +
                        '<td>' + money(p.budget) + '</td>' +
                        '<td>' + badge(p) + (p.reason ? '<div class="small text-danger mt-1"><i class="bi bi-info-circle me-1"></i>' + esc(p.reason) + '</div>' : '') + '</td>' +
                        '<td>' + voteBadge(p) + '</td>' +
                        '<td class="text-end"><span class="fw-semibold">' + Number(p.votes || 0).toLocaleString() + '</span></td>' +
                        '<td class="text-end">' +
                        (isOpen
                            ? '<button class="btn btn-sm btn-outline-danger btn-toggle-voting" data-id="' + p.id + '" data-start="0"><i class="bi bi-stop-circle me-1"></i>Stop voting</button>'
                            : '<button class="btn btn-sm btn-outline-success btn-toggle-voting" data-id="' + p.id + '" data-start="1"><i class="bi bi-play-circle me-1"></i>Start voting</button>') +
                        '</td>' +
                        '</tr>';
                }).join('')
                : '<tr><td colspan="6" class="vdc-empty">' +
                (allProjects.length ? 'No projects match your filter.' : 'No proposals submitted yet.') +
                '</td></tr>';

            // Accepted table
            var accepted = allProjects.filter(function (p) {
                return ['accepted', 'progress', 'completed'].indexOf(p.status) > -1;
            });

            document.getElementById('accepted').innerHTML = accepted.length
                ? accepted.map(function (p) {
                    return '<tr>' +
                        '<td>' +
                        '<strong>' + esc(p.title) + '</strong>' +
                        '<div class="vdc-kicker">' + esc(p.area) + ' · ' + esc(p.sector) + '</div>' +
                        '</td>' +
                        '<td>' + money(p.budget) + '</td>' +
                        '<td>' + badge(p) + '</td>' +
                        '<td class="text-end">' + Number(p.votes || 0).toLocaleString() + '</td>' +
                        '</tr>';
                }).join('')
                : '<tr><td colspan="4" class="vdc-empty">No accepted projects yet.</td></tr>';

            // Denied table
            var denied = allProjects.filter(function (p) {
                return p.status === 'denied';
            });

            document.getElementById('denied').innerHTML = denied.length
                ? denied.map(function (p) {
                    return '<div class="border-bottom pb-3 mb-3">' +
                        '<div class="d-flex justify-content-between gap-2">' +
                        '<strong>' + esc(p.title) + '</strong>' +
                        badge(p) +
                        '</div>' +
                        '<div class="vdc-kicker mt-1">' + esc(p.area) + ' · ' + money(p.budget) + '</div>' +
                        '<p class="small text-danger mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>' + esc(p.reason || 'Denied by committee without specific note.') + '</p>' +
                        '</div>';
                }).join('')
                : '<p class="vdc-empty mb-0">No denied projects.</p>';
        }

        // Attach event listener for start/stop voting buttons
        document.getElementById('all-rows').addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-toggle-voting');
            if (!btn) return;
            var id = Number(btn.getAttribute('data-id'));
            var start = btn.getAttribute('data-start') === '1';
            toggleVoting(id, start);
        });

        document.getElementById('q').addEventListener('input', render);
        document.getElementById('voteFilter').addEventListener('change', render);

        // Fetch projects from API
        fetch('../api/projects.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) {
                    showAlert('danger', d.error || 'Could not load decisions.');
                    document.getElementById('all-rows').innerHTML = '<tr><td colspan="6" class="vdc-empty">Could not load projects.</td></tr>';
                    return;
                }
                allProjects = d.projects || [];
                render();
            })
            .catch(function () {
                showAlert('danger', 'Could not reach the server. Please refresh.');
                document.getElementById('all-rows').innerHTML = '<tr><td colspan="6" class="vdc-empty">Server connection error.</td></tr>';
            });
    })();
</script>
</body>
</html>
