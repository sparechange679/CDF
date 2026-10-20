<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me  = current_user();
$tab = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Vote | CDF Malawi Citizen Portal</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap' rel='stylesheet'>
    <link href='css/style.css' rel='stylesheet'>
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
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>
                        Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="projects.php"><i class="bi bi-folder2-open me-1"></i>
                        Projects</a></li>
                <li class="nav-item"><a class="nav-link active" href="vote.php"><i
                                class="bi bi-hand-thumbs-up me-1"></i> Vote</a></li>
                <li class="nav-item"><a class="nav-link" href="feedback.php"><i class="bi bi-chat-left-text me-1"></i>
                        Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="track.php"><i class="bi bi-geo-alt me-1"></i> Track</a>
                </li>
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
                <li class="breadcrumb-item active">Vote</li>
            </ol>
        </nav>
        <h1>Vote on CDF Projects</h1>
        <p>Cast your vote on open projects in your constituency. Counts and ranking stay local to that area.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-hand-thumbs-up"></i></div>
                    <h3 class="fw-bold mb-1" id="stat-total">0</h3>
                    <p class="text-muted mb-0 small">Total citizen votes</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-trophy"></i></div>
                    <h3 class="fw-bold mb-1" id="stat-leader">—</h3>
                    <p class="text-muted mb-0 small">Leading project</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-bar-chart"></i></div>
                    <h3 class="fw-bold mb-1" id="stat-open">0</h3>
                    <p class="text-muted mb-0 small">Projects open for voting</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="feedback-card h-100">
                    <h2 class="h5 fw-bold mb-1">Live vote statistics</h2>
                    <p class="text-muted small mb-4">Share of all votes among projects that are currently open.</p>
                    <div id="vote-stats"></div>
                    <p class="text-muted small mb-0 d-none" id="vote-empty">No vote statistics for this constituency
                        yet.</p>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-4" id="vote-grid"></div>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5><i class="bi bi-shield-check me-2"></i>CDF Malawi Portal</h5>
                <p class="small" style="opacity:0.8;">Official platform of the Government of Malawi for monitoring
                    Constituency Development Fund projects.</p>
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
            <p class="mb-0">© 2026 Government of Malawi • Constituency Development Fund (CDF) Citizen Monitoring
                System</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<script>
    (function () {
        let grid = document.getElementById("vote-grid");

        function ranked() {
            let votes = CDF.getVotes();
            return CDF.votableProjects().slice().sort(function (a, b) {
                return (votes[b.id] || 0) - (votes[a.id] || 0);
            });
        }

        function renderStats() {
            let votes = CDF.getVotes();
            let list = ranked();
            let total = list.reduce(function (s, p) {
                return s + (votes[p.id] || 0);
            }, 0);
            document.getElementById("stat-total").textContent = CDF.votesForProjects(list).toLocaleString();
            document.getElementById("stat-open").textContent = list.length;
            document.getElementById("stat-leader").textContent = list.length ? list[0].title : "—";

            document.getElementById("vote-stats").innerHTML = list.map(function (p, i) {
                let n = votes[p.id] || 0;
                let pct = total ? Math.round((n / total) * 100) : 0;
                return (
                    '<div class="mb-3">' +
                    '<div class="d-flex justify-content-between small mb-1">' +
                    '<span><strong>' + (i + 1) + '.</strong> ' + p.title + '</span>' +
                    '<span class="vote-count">' + n.toLocaleString() + ' <span class="text-muted">(' + pct + '%)</span></span>' +
                    '</div>' +
                    '<div class="vote-stat-bar"><div class="fill" style="width:' + pct + '%"></div></div>' +
                    '</div>'
                );
            }).join("");
            let emptyEl = document.getElementById("vote-empty");
            if (emptyEl) emptyEl.classList.toggle("d-none", list.length > 0);
        } // FIXED: this closing brace for renderStats() was missing

        function renderCards() {
            let list = ranked();
            grid.innerHTML = list.length
                ? list.map(function (p) {
                    return CDF.renderProjectCard(p, {
                        showVote: true,
                        showVoteCount: true,
                        detailsHref: "feedback.php?project=" + p.id
                    });
                }).join("")
                : '<div class="col-12"><p class="text-muted mb-0">No projects are open for voting in this constituency.</p></div>';
        }

        window.CDF_onVote = function () {
            renderStats();
        };

        renderCards();
        renderStats();
        CDF.bindVotes(grid);
    })();
</script>
</body>
</html>