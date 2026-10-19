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

<div class="gov-topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <div><i class="bi bi-building"></i> Official Government of Malawi Portal</div>
        <div class="d-none d-md-block">
            <a href="#"><i class="bi bi-globe2"></i> English</a>
            <span class="mx-2">|</span>
            <a href="#"><i class="bi bi-telephone"></i> Support</a>
        </div>
    </div>
</div>

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
                <li class="nav-item ms-lg-2">
                    <a class="nav-link btn-login" href="login.php"><i class="bi bi-person-circle me-1"></i> Login</a>
                </li>
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
        <p>Approved projects uploaded for your selected constituency. Log in to limit this list to your area.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="filter-bar">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-medium small mb-1">Search</label>
                    <input type="search" id="search" class="form-control" placeholder="Project name or constituency">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small mb-1">Status</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">All approved statuses</option>
                        <option value="approved">Approved</option>
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

        <div class="row g-4" id="project-grid"></div>
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
                <a href="#"><i class="bi bi-telephone me-1"></i> +265 1 789 000</a>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">© 2026 Government of Malawi • Constituency Development Fund (CDF) Citizen Monitoring System</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<script>
    (function () {
        var list = CDF.approvedProjects();
        var cats = [];
        list.forEach(function (p) {
            if (cats.indexOf(p.category) === -1) cats.push(p.category);
        });
        var catSel = document.getElementById("categoryFilter");
        cats.forEach(function (c) {
            catSel.insertAdjacentHTML("beforeend", '<option value="' + c + '">' + c + "</option>");
        });

        function render() {
            var q = document.getElementById("search").value.toLowerCase();
            var st = document.getElementById("statusFilter").value;
            var cat = document.getElementById("categoryFilter").value;
            var filtered = list.filter(function (p) {
                var matchQ = !q || p.title.toLowerCase().indexOf(q) !== -1 || p.constituency.toLowerCase().indexOf(q) !== -1;
                var matchS = !st || p.status === st;
                var matchC = !cat || p.category === cat;
                return matchQ && matchS && matchC;
            });
            document.getElementById("project-grid").innerHTML = filtered.map(function (p) {
                return CDF.renderProjectCard(p, { detailsHref: "feedback.php?project=" + p.id });
            }).join("");
            document.getElementById("resultCount").textContent = filtered.length + " project" + (filtered.length === 1 ? "" : "s");
            document.getElementById("empty").classList.toggle("d-none", filtered.length > 0);
        }

        document.getElementById("search").addEventListener("input", render);
        document.getElementById("statusFilter").addEventListener("change", render);
        document.getElementById("categoryFilter").addEventListener("change", render);
        render();
    })();
</script>
</body>
</html>
