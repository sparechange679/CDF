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
    <title>Projects | CDF Malawi Citizen Portal</title>
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
                <li class="nav-item"><a class="nav-link active" href="projects.php"><i class="bi bi-folder2-open me-1"></i> Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="vote.php"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</a></li>
                <li class="nav-item"><a class="nav-link" href="feedback.php"><i class="bi bi-chat-left-text me-1"></i> Feedback</a></li>
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
                <li class="breadcrumb-item active">Projects</li>
            </ol>
        </nav>
        <h1>Approved CDF Projects</h1>
        <p>Projects approved by the Member of Parliament. Sign in as a citizen to limit this list to your constituency.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="filter-bar">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1">Search</label>
                    <input type="search" id="search" class="form-control" placeholder="Project name, area or constituency">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small mb-1">Status</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">All approved statuses</option>
                        <option value="accepted">Approved</option>
                        <option value="progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small mb-1">Sector</label>
                    <select id="categoryFilter" class="form-select">
                        <option value="">All sectors</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted" id="resultCount"></div>
                </div>
            </div>
        </div>

        <div class="row g-4" id="project-grid">
            <div class="col-12 text-center text-muted">Loading projects…</div>
        </div>
        <p class="text-center text-muted mt-4 d-none" id="empty">No approved projects for this constituency yet.</p>
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

        var list = [];
        var FALLBACK_IMG = "https://images.unsplash.com/photo-1581092919534-89d2a0c94f36?auto=format&fit=crop&w=800&q=80";

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

        function card(p) {
            var rating = p.avg_rating !== null
                ? ' · <i class="bi bi-star-fill text-warning"></i> ' + p.avg_rating.toFixed(1) + " (" + p.feedback_count + ")"
                : "";
            return '<div class="col-md-6 col-lg-4">' +
                '<div class="project-card">' +
                '<img src="' + esc(p.image_url || FALLBACK_IMG) + '" alt="' + esc(p.title) + '">' +
                '<div class="project-body">' +
                '<div class="d-flex justify-content-between align-items-start mb-2 gap-2">' +
                '<h5 class="mb-0 fw-semibold">' + esc(p.title) + '</h5>' +
                '<span class="status-badge ' + statusClass(p.status) + '">' + esc(p.statusLabel) + '</span>' +
                '</div>' +
                '<p class="meta-info mb-3">' +
                '<i class="bi bi-geo-alt-fill"></i> ' + esc(p.area) + ' · ' + esc(p.constituency) + '<br>' +
                '<i class="bi bi-tag"></i> ' + esc(p.sector) + '<br>' +
                '<i class="bi bi-cash-stack"></i> Budget: ' + money(p.budget) +
                '</p>' +
                '<p class="mb-2 small"><i class="bi bi-hand-thumbs-up-fill text-danger"></i> <span class="vote-count">' +
                p.votes.toLocaleString() + '</span> citizen votes' + rating + '</p>' +
                '<div class="d-flex justify-content-between small mb-1"><span>Progress</span><span class="fw-semibold">' + p.progress + '%</span></div>' +
                '<div class="progress mb-3"><div class="progress-bar ' + barClass(p.progress, p.complete) + '" style="width:' + p.progress + '%"></div></div>' +
                '<div class="d-flex gap-2">' +
                '<a class="btn btn-cdf btn-sm flex-fill" href="vote.php"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</a>' +
                '<a class="btn btn-outline-gov btn-sm flex-fill" href="feedback.php?project=' + p.id + '">Details &amp; feedback</a>' +
                '</div>' +
                '</div></div></div>';
        }

        function render() {
            var q = document.getElementById("search").value.toLowerCase();
            var st = document.getElementById("statusFilter").value;
            var cat = document.getElementById("categoryFilter").value;

            var filtered = list.filter(function (p) {
                var hay = (p.title + " " + p.area + " " + p.constituency).toLowerCase();
                return (!q || hay.indexOf(q) !== -1) && (!st || p.status === st) && (!cat || p.sector === cat);
            });

            document.getElementById("project-grid").innerHTML = filtered.map(card).join("");
            document.getElementById("resultCount").textContent = filtered.length + " project" + (filtered.length === 1 ? "" : "s");
            document.getElementById("empty").classList.toggle("d-none", filtered.length > 0);
        }

        fetch("api/public_projects.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) throw new Error(d.error || "Failed");
                list = d.projects;
                var sel = document.getElementById("categoryFilter");
                var seen = {};
                list.forEach(function (p) {
                    if (!seen[p.sector]) {
                        seen[p.sector] = true;
                        sel.insertAdjacentHTML("beforeend", '<option value="' + esc(p.sector) + '">' + esc(p.sector) + "</option>");
                    }
                });
                render();
            })
            .catch(function () {
                document.getElementById("project-grid").innerHTML =
                    '<div class="col-12 text-center text-danger">Could not load projects. Please refresh the page.</div>';
            });

        document.getElementById("search").addEventListener("input", render);
        document.getElementById("statusFilter").addEventListener("change", render);
        document.getElementById("categoryFilter").addEventListener("change", render);
    })();
</script>
</body>
</html>