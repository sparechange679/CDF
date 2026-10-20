<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me  = current_user();
$tab = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | CDF Malawi Citizen Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<?php include __DIR__ . '/includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-shield-check text-danger fs-3"></i>
            <div>
                <div style="font-size: 1.15rem; line-height: 1.1;">CDF Malawi</div>
                <small class="text-muted" style="font-size: 0.7rem; font-weight: 500;">Citizen Monitoring Portal</small>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="projects.php"><i class="bi bi-folder2-open me-1"></i> Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="vote.php"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</a></li>
                <li class="nav-item"><a class="nav-link active" href="feedback.php"><i class="bi bi-chat-left-text me-1"></i> Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="track.php"><i class="bi bi-geo-alt me-1"></i> Track</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-gov">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Feedback</li>
            </ol>
        </nav>
        <h1>Project Feedback &amp; Progress</h1>
        <p>Submit observations on a project and review whether work is complete or still underway.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="feedback-card">
                    <h2 class="h5 fw-bold mb-3">Submit feedback</h2>
                    <div id="form-alert"></div>
                    <?php if (!$me || $me['role'] !== 'citizen'): ?>
                        <div class="alert alert-warning alert-gov py-2 small">
                            <?php if ($me): ?>
                                Only citizen accounts can submit feedback.
                            <?php else: ?>
                                <a href="login.php" class="alert-link">Sign in with your citizen account</a> to submit feedback.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <form id="feedback-form">
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="projectSelect">Select project</label>
                            <select class="form-select" id="projectSelect" required>
                                <option value="" selected disabled>Choose a project...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="message">Your feedback</label>
                            <textarea class="form-control" id="message" rows="4" required maxlength="2000" placeholder="Describe what you observed, any issues, or suggestions..."></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium" for="rating">Overall rating</label>
                            <select class="form-select" id="rating" required>
                                <option value="" selected disabled>Rate this project</option>
                                <option value="5">Excellent (5)</option>
                                <option value="4">Good (4)</option>
                                <option value="3">Average (3)</option>
                                <option value="2">Poor (2)</option>
                                <option value="1">Very Poor (1)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-cdf w-100">
                            <i class="bi bi-send me-2"></i>Submit Feedback
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="feedback-card mb-4" id="progress-panel">
                    <h2 class="h5 fw-bold mb-1">Progress &amp; completion status</h2>
                    <p class="text-muted small mb-4">Select a project to see implementation progress and whether it is complete.</p>
                    <div id="progress-detail">
                        <p class="text-muted mb-0">Choose a project from the list on the left.</p>
                    </div>
                </div>

                <div class="feedback-card">
                    <h2 class="h5 fw-bold mb-3">Citizen comments</h2>
                    <div id="comments">
                        <p class="text-muted mb-0">Choose a project to see feedback.</p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h5 fw-bold mt-5 mb-3">All projects — completion overview</h2>
        <div class="table-responsive bg-white rounded-3 border" style="border-color:#eef0f2 !important;">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Project</th>
                    <th>Constituency</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Complete</th>
                </tr>
                </thead>
                <tbody id="status-table">
                <tr><td colspan="5" class="text-center text-muted">Loading projects…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5><i class="bi bi-shield-check me-2"></i>CDF Malawi Portal</h5>
                <p class="small" style="opacity:0.8;">Official platform of the Government of Malawi for monitoring Constituency Development Fund projects.</p>
            </div>
            <div class="col-md-2">
                <h5>Quick Links</h5>
                <a href="index.php">Dashboard</a>
                <a href="projects.php">All Projects</a>
                <a href="vote.php">Voting</a>
                <a href="feedback.php">Feedback</a>
            </div>
            <div class="col-md-3">
                <h5>Resources</h5>
                <a href="track.php">Track Progress</a>
                <a href="login.php">Citizen Login</a>
            </div>
            <div class="col-md-3">
                <h5>Contact</h5>
                <a href="#"><i class="bi bi-envelope me-1"></i> cdf@localgov.mw</a>
                <a href="#"><i class="bi bi-telephone me-1"></i> +265 994 507 343</a>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">© 2026 Government of Malawi • Constituency Development Fund (CDF) Citizen Monitoring System</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";

        var select = document.getElementById("projectSelect");
        var projects = [];
        var currentUser = null;

        function esc(s) {
            var d = document.createElement("div");
            d.textContent = s == null ? "" : String(s);
            return d.innerHTML;
        }

        function statusClass(s) {
            return s === "accepted" ? "status-approved" : s === "progress" ? "status-progress" : "status-completed";
        }

        function barClass(p, done) {
            if (done || p >= 100) return "bg-success";
            if (p >= 50) return "bg-primary";
            if (p >= 25) return "bg-warning";
            return "bg-danger";
        }

        function findProject(id) {
            return projects.filter(function (p) { return String(p.id) === String(id); })[0];
        }

        function showAlert(type, msg) {
            document.getElementById("form-alert").innerHTML =
                '<div class="alert alert-' + type + ' alert-gov py-2"></div>';
            document.getElementById("form-alert").firstChild.textContent = msg;
        }

        function csrf() {
            return fetch("api/csrf.php", { credentials: "same-origin" })
                .then(function (r) { return r.json(); })
                .then(function (d) { return d.token; });
        }

        function renderComments(list) {
            document.getElementById("comments").innerHTML = list.length
                ? list.map(function (n) {
                    return '<div class="comment-item">' +
                        '<div class="d-flex justify-content-between small mb-1">' +
                        "<strong>" + esc(n.name) + "</strong>" +
                        '<span class="text-muted">Rating: ' + n.rating + "/5 · " + esc(n.date) + "</span>" +
                        "</div>" +
                        '<p class="mb-0 small">' + esc(n.comment) + "</p>" +
                        "</div>";
                }).join("")
                : '<p class="text-muted mb-0">No feedback yet for this project.</p>';
        }

        function loadComments(id) {
            document.getElementById("comments").innerHTML = '<p class="text-muted mb-0">Loading feedback…</p>';
            fetch("api/feedback.php?project_id=" + encodeURIComponent(id), { credentials: "same-origin" })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (select.value !== String(id)) return;
                    renderComments(d.ok ? d.feedback : []);
                })
                .catch(function () {
                    document.getElementById("comments").innerHTML =
                        '<p class="text-danger mb-0">Could not load feedback.</p>';
                });
        }

        function renderProgress(id) {
            var p = findProject(id);
            var panel = document.getElementById("progress-detail");
            if (!p) {
                panel.innerHTML = '<p class="text-muted mb-0">Choose a project from the list on the left.</p>';
                document.getElementById("comments").innerHTML = '<p class="text-muted mb-0">Choose a project to see feedback.</p>';
                return;
            }
            var doneLabel = p.complete
                ? '<span class="status-badge status-completed">Complete</span>'
                : '<span class="status-badge status-progress">Not complete</span>';
            var updates = (p.updates || []).length
                ? '<h3 class="h6 fw-semibold mt-3">Field updates</h3><div class="timeline">' +
                p.updates.map(function (u) {
                    return '<div class="timeline-item"><div class="small text-muted">' + esc(u.date) +
                        " · " + u.percentage + "%</div><div>" + esc(u.text) + "</div></div>";
                }).join("") + "</div>"
                : "";

            panel.innerHTML =
                '<div class="d-flex justify-content-between align-items-start mb-2">' +
                '<div><h3 class="h5 mb-1">' + esc(p.title) + '</h3><p class="meta-info mb-0"><i class="bi bi-geo-alt-fill"></i> ' +
                esc(p.area) + " · " + esc(p.constituency) + "</p></div>" + doneLabel + "</div>" +
                '<p class="small text-muted">' + esc(p.description) + "</p>" +
                '<div class="d-flex justify-content-between small mb-1"><span>Implementation progress</span><span class="fw-semibold">' + p.progress + "%</span></div>" +
                '<div class="progress mb-3" style="height:10px"><div class="progress-bar ' + barClass(p.progress, p.complete) + '" style="width:' + p.progress + '%"></div></div>' +
                '<p class="small mb-0"><strong>Official status:</strong> ' + esc(p.statusLabel) +
                " · <strong>Completion:</strong> " + (p.complete ? "Yes — works handed over" : "No — still in delivery") + "</p>" +
                updates;

            loadComments(id);
        }

        function renderTable() {
            document.getElementById("status-table").innerHTML = projects.length
                ? projects.map(function (p) {
                    return "<tr>" +
                        '<td><a href="feedback.php?project=' + p.id + '">' + esc(p.title) + "</a></td>" +
                        "<td>" + esc(p.constituency) + "</td>" +
                        '<td style="min-width:140px"><div class="progress"><div class="progress-bar ' + barClass(p.progress, p.complete) + '" style="width:' + p.progress + '%"></div></div><span class="small">' + p.progress + "%</span></td>" +
                        '<td><span class="status-badge ' + statusClass(p.status) + '">' + esc(p.statusLabel) + "</span></td>" +
                        "<td>" + (p.complete ? "Yes" : "No") + "</td>" +
                        "</tr>";
                }).join("")
                : '<tr><td colspan="5" class="text-center text-muted">No approved projects yet.</td></tr>';
        }

        select.addEventListener("change", function () { renderProgress(select.value); });

        document.getElementById("feedback-form").addEventListener("submit", function (e) {
            e.preventDefault();
            var id = select.value;
            if (!id) return;

            if (!currentUser || !currentUser.is_citizen) {
                document.getElementById("form-alert").innerHTML =
                    '<div class="alert alert-warning alert-gov py-2"><a href="login.php" class="alert-link">Sign in with your citizen account</a> to submit feedback.</div>';
                return;
            }

            var btn = this.querySelector('button[type="submit"]');
            var label = btn.innerHTML;
            btn.disabled = true;
            btn.textContent = "Sending…";

            csrf().then(function (token) {
                return fetch("api/feedback.php", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { "Content-Type": "application/json", "X-CSRF-Token": token },
                    body: JSON.stringify({
                        project_id: Number(id),
                        rating: Number(document.getElementById("rating").value),
                        comment: document.getElementById("message").value.trim()
                    })
                });
            }).then(function (r) {
                return r.json().catch(function () { return { ok: false, error: "Unexpected response from the server." }; });
            }).catch(function () {
                return { ok: false, error: "Could not reach the server. Check your connection." };
            }).then(function (res) {
                btn.disabled = false;
                btn.innerHTML = label;
                if (!res.ok) {
                    showAlert("danger", res.error || "Could not submit feedback.");
                    return;
                }
                document.getElementById("message").value = "";
                document.getElementById("rating").value = "";
                showAlert("success", res.message);
                loadComments(id);
            });
        });

        fetch("api/public_projects.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) throw new Error(d.error || "Failed");
                projects = d.projects;
                currentUser = d.user;
                projects.forEach(function (p) {
                    var opt = document.createElement("option");
                    opt.value = p.id;
                    opt.textContent = p.title + " — " + p.constituency;
                    select.appendChild(opt);
                });
                renderTable();

                var initial = new URLSearchParams(window.location.search).get("project");
                if (initial && findProject(initial)) {
                    select.value = String(findProject(initial).id);
                    renderProgress(select.value);
                }
            })
            .catch(function () {
                document.getElementById("status-table").innerHTML =
                    '<tr><td colspan="5" class="text-center text-danger">Could not load projects. Please refresh.</td></tr>';
            });
    })();
</script>
</body>
</html>