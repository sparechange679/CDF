<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CDF Malawi Citizen Portal</title>
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
                <li class="nav-item"><a class="nav-link" href="projects.php"><i class="bi bi-folder2-open me-1"></i> Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="vote.php"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</a></li>
                <li class="nav-item"><a class="nav-link" href="feedback.php"><i class="bi bi-chat-left-text me-1"></i> Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="track.php"><i class="bi bi-geo-alt me-1"></i> Track</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="nav-link btn-login active" href="login.php"><i class="bi bi-person-circle me-1"></i> Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="py-5 login-wrap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="feedback-card">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-check text-danger fs-1"></i>
                        <h1 class="h4 fw-bold mt-2">Citizen login</h1>
                        <p class="text-muted small mb-0">Use this portal to vote, track and comment on CDF projects.</p>
                    </div>
                    <div id="login-alert"></div>
                    <form id="login-form">
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="email">Email or phone</label>
                            <input type="text" class="form-control" id="email" required placeholder="e.g. 0888 000 000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="password">Password</label>
                            <input type="password" class="form-control" id="password" required minlength="4">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="constituency">Constituency</label>
                            <select class="form-select" id="constituency" required>
                                <option value="" disabled selected>Select your constituency</option>
                            </select>
                            <div class="form-text">Choose the constituency added by the administrator. Developments on the portal will be limited to this area.</div>
                        </div>
                        <button type="submit" class="btn btn-cdf w-100 mb-3">Sign in</button>
                        <p class="small text-muted text-center mb-0">Frontend only for now. The list of constituencies will later come from the admin backend.</p>
                    </form>
                    <div id="logged-in" class="d-none">
                        <div class="text-center mb-3">
                            <p class="mb-1">Signed in as <strong id="session-name"></strong></p>
                            <p class="text-muted small mb-0">Constituency: <strong id="session-constituency"></strong></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="switch-constituency">Change constituency</label>
                            <select class="form-select" id="switch-constituency"></select>
                        </div>
                        <div class="d-grid gap-2">
                            <a class="btn btn-cdf" href="projects.php">View my constituency projects</a>
                            <button type="button" class="btn btn-outline-gov" id="logout">Sign out</button>
                        </div>
                    </div>
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
        var form = document.getElementById("login-form");
        var logged = document.getElementById("logged-in");
        var constituencySelect = document.getElementById("constituency");
        var switchSelect = document.getElementById("switch-constituency");

        CDF.fillConstituencySelect(constituencySelect);

        function showSession() {
            var session = CDF.getSession();
            if (!session) {
                form.classList.remove("d-none");
                logged.classList.add("d-none");
                CDF.updateNavLogin();
                return;
            }
            document.getElementById("session-name").textContent = session.id;
            document.getElementById("session-constituency").textContent = session.constituencyName;
            CDF.fillConstituencySelect(switchSelect, session.constituencyId);
            form.classList.add("d-none");
            logged.classList.remove("d-none");
            CDF.updateNavLogin();
        }

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            var constituency = CDF.findConstituency(constituencySelect.value);
            if (!constituency) {
                document.getElementById("login-alert").innerHTML =
                    '<div class="alert alert-warning alert-gov py-2">Please select a constituency.</div>';
                return;
            }
            CDF.saveSession({
                id: document.getElementById("email").value.trim(),
                constituencyId: constituency.id,
                constituencyName: constituency.name,
                at: Date.now()
            });
            window.location.href = "projects.php";
        });

        switchSelect.addEventListener("change", function () {
            var constituency = CDF.findConstituency(switchSelect.value);
            var session = CDF.getSession();
            if (!constituency || !session) return;
            session.constituencyId = constituency.id;
            session.constituencyName = constituency.name;
            CDF.saveSession(session);
            window.location.href = "projects.php";
        });

        document.getElementById("logout").addEventListener("click", function () {
            CDF.clearSession();
            showSession();
            window.location.reload();
        });

        showSession();
    })();
</script>
</body>
</html>
