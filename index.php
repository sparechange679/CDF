<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | CDF Malawi Citizen Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<div class="gov-topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-building"></i> Official Government of Malawi Portal
        </div>
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
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="projects.php"><i class="bi bi-folder2-open me-1"></i> Projects</a></li>
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

<section class="hero">
    <div class="container">
        <div class="hero-badge">
            <i class="bi bi-check-circle me-1"></i> Official Government Platform
        </div>
        <h1>Constituency Development Fund<br>Citizen Monitoring Portal</h1>
        <p class="lead">
            Transparent • Accountable • Citizen-Driven Development.<br>
            Monitor, vote on, and provide feedback on CDF projects across all constituencies in Malawi.
        </p>
        <div class="mt-4 d-flex flex-wrap gap-3">
            <a href="projects.php" class="btn btn-cdf btn-lg">
                <i class="bi bi-search me-2"></i>Explore Projects
            </a>
            <a href="vote.php" class="btn btn-outline-light btn-lg">
                Cast Your Vote
            </a>
            <a href="vdc/index.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-diagram-3 me-2"></i>VDC Workspace
            </a>
            <a href="mp/index.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-bank me-2"></i>MP Workspace
            </a>
            <a href="ddo/index.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-clipboard2-pulse me-2"></i>DDO Workspace
            </a>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-folder-check"></i></div>
                    <h3 class="fw-bold mb-1" id="stat-active">—</h3>
                    <p class="text-muted mb-0 small">Approved Projects</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-hand-thumbs-up"></i></div>
                    <h3 class="fw-bold mb-1" id="stat-votes">—</h3>
                    <p class="text-muted mb-0 small">Citizen Votes</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-hourglass-split"></i></div>
                    <h3 class="fw-bold mb-1" id="stat-progress">—</h3>
                    <p class="text-muted mb-0 small">In Progress</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="bi bi-trophy"></i></div>
                    <h3 class="fw-bold mb-1" id="stat-done">—</h3>
                    <p class="text-muted mb-0 small">Completed</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light-gov">
    <div class="container">
        <div class="section-header text-center">
            <h2>Featured Approved Projects</h2>
            <p>Uploaded projects that have passed official approval and are open for citizen monitoring</p>
        </div>
        <div class="row g-4" id="featured-projects"></div>
        <div class="text-center mt-5">
            <a href="projects.php" class="btn btn-outline-gov">
                View All Projects <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<section class="py-5" id="how-it-works">
    <div class="container">
        <div class="section-header text-center">
            <h2>How Citizen Monitoring Works</h2>
            <p>Your participation strengthens transparency and accountability in the use of public funds</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="how-card">
                    <div class="how-number">1</div>
                    <h6 class="fw-semibold">Browse Projects</h6>
                    <p class="small text-muted mb-0">View uploaded and approved CDF projects in your constituency.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="how-card">
                    <div class="how-number">2</div>
                    <h6 class="fw-semibold">Vote &amp; Prioritise</h6>
                    <p class="small text-muted mb-0">Cast your vote and see live counts and ranking statistics.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="how-card">
                    <div class="how-number">3</div>
                    <h6 class="fw-semibold">Track Progress</h6>
                    <p class="small text-muted mb-0">Follow implementation status until a project is marked complete.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="how-card">
                    <div class="how-number">4</div>
                    <h6 class="fw-semibold">Give Feedback</h6>
                    <p class="small text-muted mb-0">Submit observations and review progress on the feedback page.</p>
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
                <p class="small" style="opacity:0.8;">
                    Official platform of the Government of Malawi for monitoring Constituency Development Fund projects. Promoting transparency, accountability and citizen participation.
                </p>
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
                <a href="projects.php">Project Reports</a>
                <a href="login.php">Citizen Login</a>
                <a href="feedback.php">Submit Feedback</a>
            </div>
            <div class="col-md-3">
                <h5>Contact</h5>
                <a href="#"><i class="bi bi-envelope me-1"></i> cdf@localgov.mw</a>
                <a href="#"><i class="bi bi-telephone me-1"></i> +265 1 789 000</a>
                <a href="#"><i class="bi bi-geo-alt me-1"></i> Ministry of Local Government, Lilongwe</a>
            </div>
        </div>
        <div class="footer-bottom text-center">
            <p class="mb-0">
                © 2026 Government of Malawi • Ministry of Local Government &amp; Rural Development<br>
                Constituency Development Fund (CDF) Citizen Monitoring System
            </p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<script>
    (function () {
        var approved = CDF.approvedProjects();
        document.getElementById("stat-active").textContent = approved.length;
        document.getElementById("stat-votes").textContent = CDF.votesForProjects(approved).toLocaleString();
        document.getElementById("stat-progress").textContent = approved.filter(function (p) { return p.status === "progress"; }).length;
        document.getElementById("stat-done").textContent = approved.filter(function (p) { return p.complete; }).length;
        document.getElementById("featured-projects").innerHTML = approved.length
            ? approved.slice(0, 3).map(function (p) {
                return CDF.renderProjectCard(p, { detailsHref: "feedback.php?project=" + p.id });
            }).join("")
            : '<div class="col-12 text-center text-muted">No approved developments in this constituency yet. <a href="login.php">Change constituency</a></div>';
    })();
</script>
</body>
</html>
