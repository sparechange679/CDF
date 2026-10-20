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
    <title>Track | CDF Malawi Citizen Portal</title>
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
                <li class="nav-item"><a class="nav-link" href="feedback.php"><i class="bi bi-chat-left-text me-1"></i> Feedback</a></li>
                <li class="nav-item"><a class="nav-link active" href="track.php"><i class="bi bi-geo-alt me-1"></i> Track</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-gov">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Track</li>
            </ol>
        </nav>
        <h1>Track Implementation</h1>
        <p>Follow field updates, location and whether each approved project is complete or still in delivery.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="filter-bar">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-medium small mb-1">Project</label>
                    <select id="trackSelect" class="form-select"></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small mb-1">Show</label>
                    <select id="completeFilter" class="form-select">
                        <option value="">All projects</option>
                        <option value="no">Not complete</option>
                        <option value="yes">Complete</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="feedback-card" id="track-detail">
                    <p class="text-muted mb-0">Loading projects…</p>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="feedback-card">
                    <h2 class="h5 fw-bold mb-3">Delivery snapshot</h2>
                    <div id="snapshot"></div>
                </div>
            </div>
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
            <p class="mb-0">&copy; 2026 Government of Malawi • Constituency Development Fund (CDF) Citizen Monitoring System</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";

        var select = document.getElementById("trackSelect");
        var filter = document.getElementById("completeFilter");
        var projects = [];
        var FALLBACK_IMG = "https://images.unsplash.com/photo-1581092919534-89d2a0c94f36?auto=format&fit=crop&w=800&q=80";
        var wanted = new URLSearchParams(window.location.search).get("project");

        function esc(s) {
            var d = document.createElement("div");
            d.textContent = s == null ? "" : String(s);
            return d.innerHTML;
        }

        function money(n) {
            var num = Number(n) || 0;
            return num >= 1000000 ? "MK " + (num / 1000000).toFixed(1) + " Million" : "MK " + num.toLocaleString("en-GB");
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

        function fillSelect() {
            var mode = filter.value;
            var items = projects.filter(function (p) {
                if (mode === "yes") return p.complete;
                if (mode === "no") return !p.complete;
                return true;
            });
            var current = wanted || select.value;
            select.innerHTML = items.map(function (p) {
                return '<option value="' + p.id + '">' + esc(p.title) + " — " + esc(p.constituency) + "</option>";
            }).join("");
            if (items.some(function (p) { return String(p.id) === String(current); })) select.value = current;
            wanted = null;
            render();
        }

        function render() {
            var p = projects.filter(function (x) { return String(x.id) === String(select.value); })[0];
            var box = document.getElementById("track-detail");
            if (!p) {
                box.innerHTML = '<p class="text-muted mb-0">No projects match this filter.</p>';
                document.getElementById("snapshot").innerHTML = "";
                return;
            }

            var timeline = (p.updates || []).length
                ? p.updates.map(function (u) {
                    return '<div class="timeline-item"><div class="small text-muted">' + esc(u.date) + " · " + u.percentage + "%</div><div>" + esc(u.text) + "</div></div>";
                }).join("")
                : '<p class="text-muted small mb-0">No field updates have been posted yet.</p>';

            box.innerHTML =
                '<h2 class="h5 fw-bold mb-1">' + esc(p.title) + "</h2>" +
                '<p class="meta-info mb-3"><i class="bi bi-geo-alt-fill"></i> ' + esc(p.area) + " · " + esc(p.constituency) +
                ' · <i class="bi bi-cash-stack"></i> ' + money(p.budget) + "</p>" +
                '<img src="' + esc(p.image_url || FALLBACK_IMG) + '" class="rounded mb-3 w-100" style="height:220px;object-fit:cover;" alt="">' +
                "<p>" + esc(p.description) + "</p>" +
                '<div class="d-flex justify-content-between small mb-1"><span>Progress</span><span class="fw-semibold">' + p.progress + "%</span></div>" +
                '<div class="progress mb-4"><div class="progress-bar ' + barClass(p.progress, p.complete) + '" style="width:' + p.progress + '%"></div></div>' +
                '<h3 class="h6 fw-semibold">Field updates</h3>' +
                '<div class="timeline">' + timeline + "</div>" +
                '<a class="btn btn-outline-gov btn-sm mt-2" href="feedback.php?project=' + p.id + '">Give feedback</a>';

            var done = projects.filter(function (x) { return x.complete; }).length;
            document.getElementById("snapshot").innerHTML =
                '<p class="mb-2"><span class="status-badge ' + statusClass(p.status) + '">' + esc(p.statusLabel) + "</span> " +
                (p.complete
                    ? '<span class="status-badge status-completed ms-1">Complete</span>'
                    : '<span class="status-badge status-progress ms-1">Not complete</span>') + "</p>" +
                '<p class="small text-muted mb-3">Reference ' + esc(p.reference) + ". This project has been approved by the Member of Parliament" +
                (p.complete ? " and the works are complete." : " and is being processed for delivery.") + "</p>" +
                '<div class="row text-center">' +
                '<div class="col-4"><div class="fw-bold fs-4">' + projects.length + '</div><div class="small text-muted">Approved</div></div>' +
                '<div class="col-4"><div class="fw-bold fs-4">' + (projects.length - done) + '</div><div class="small text-muted">Ongoing</div></div>' +
                '<div class="col-4"><div class="fw-bold fs-4">' + done + '</div><div class="small text-muted">Complete</div></div>' +
                "</div><hr>" +
                '<a class="btn btn-cdf w-100" href="vote.php">Vote on open projects</a>';
        }

        function load() {
            fetch("api/public_projects.php", { credentials: "same-origin", cache: "no-store" })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) throw new Error(d.error || "Failed");
                    projects = d.projects;
                    fillSelect();
                })
                .catch(function () {
                    document.getElementById("track-detail").innerHTML =
                        '<p class="text-danger mb-0">Could not load projects. Please refresh the page.</p>';
                });
        }

        filter.addEventListener("change", fillSelect);
        select.addEventListener("change", render);
        load();
        setInterval(load, 30000);   // pick up new progress updates
    })();
</script>
</body>
</html>