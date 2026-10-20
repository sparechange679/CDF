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
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="projects.php"><i class="bi bi-folder2-open me-1"></i> Projects</a></li>
                <li class="nav-item"><a class="nav-link active" href="vote.php"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</a></li>
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
                <li class="breadcrumb-item active">Vote</li>
            </ol>
        </nav>
        <h1>Vote on CDF Projects</h1>
        <p>Cast your vote on projects opened for public voting by the District Council (DC / VDC). Counts and rankings directly guide committee decisions.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div id="vote-alert"></div>

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
                    <p class="text-muted small mb-0 d-none" id="vote-empty">No vote statistics for this constituency yet.</p>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-4" id="vote-grid">
                    <div class="col-12"><p class="text-muted">Loading votable projects…</p></div>
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
            <p class="mb-0">© 2026 Government of Malawi • Constituency Development Fund (CDF) Citizen Monitoring System</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<script>
    (function () {
        var grid = document.getElementById("vote-grid");
        var alertBox = document.getElementById("vote-alert");
        var dbProjects = [];
        var currentUser = null;

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function showAlert(type, msg, isHtml) {
            if (!alertBox) return;
            alertBox.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                (isHtml ? msg : esc(msg)) +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function formatMoney(amount) {
            if (typeof amount === 'string' && amount.indexOf('MK') > -1) return amount;
            var num = Number(amount);
            if (isNaN(num)) return amount || '—';
            if (num >= 1000000) {
                return 'MK ' + (num / 1000000).toFixed(1) + ' Million';
            }
            return 'MK ' + num.toLocaleString('en-GB');
        }

        function csrf() {
            return fetch('api/csrf.php', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) { return d.token; });
        }

        function renderStats() {
            var list = dbProjects.slice().sort(function (a, b) {
                return Number(b.votes || 0) - Number(a.votes || 0);
            });

            var total = list.reduce(function (s, p) {
                return s + Number(p.votes || 0);
            }, 0);

            var openList = list.filter(function (p) {
                return Number(p.voting_open) === 1 && p.status !== 'completed';
            });

            document.getElementById("stat-total").textContent = total.toLocaleString();
            document.getElementById("stat-open").textContent = openList.length;
            document.getElementById("stat-leader").textContent = list.length && total > 0 ? list[0].title : "—";

            var statsEl = document.getElementById("vote-stats");
            statsEl.innerHTML = list.map(function (p, i) {
                var n = Number(p.votes || 0);
                var pct = total ? Math.round((n / total) * 100) : 0;
                var isOpen = Number(p.voting_open) === 1 && p.status !== 'completed';
                return (
                    '<div class="mb-3">' +
                    '<div class="d-flex justify-content-between small mb-1">' +
                    '<span><strong>' + (i + 1) + '.</strong> ' + esc(p.title) + (!isOpen ? ' <span class="badge text-bg-secondary ms-1">Closed</span>' : '') + '</span>' +
                    '<span class="vote-count">' + n.toLocaleString() + ' <span class="text-muted">(' + pct + '%)</span></span>' +
                    '</div>' +
                    '<div class="vote-stat-bar"><div class="fill" style="width:' + pct + '%"></div></div>' +
                    '</div>'
                );
            }).join("");

            var emptyEl = document.getElementById("vote-empty");
            if (emptyEl) emptyEl.classList.toggle("d-none", list.length > 0);
        }

        function renderCards() {
            var list = dbProjects.slice().sort(function (a, b) {
                return Number(b.votes || 0) - Number(a.votes || 0);
            });

            if (!list.length) {
                grid.innerHTML = '<div class="col-12"><p class="text-muted mb-0">No projects are open for voting in this constituency.</p></div>';
                return;
            }

            grid.innerHTML = list.map(function (p) {
                var isOpen = Number(p.voting_open) === 1 && p.status !== 'completed';
                var voted = !!p.user_voted;
                var voteBtn = "";

                if (isOpen) {
                    if (voted) {
                        voteBtn = '<button class="btn btn-cdf btn-sm flex-fill" disabled><i class="bi bi-check2 me-1"></i> Voted</button>';
                    } else {
                        voteBtn = '<button class="btn btn-cdf btn-sm flex-fill js-db-vote" data-id="' + p.id + '"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</button>';
                    }
                } else {
                    voteBtn = '<button class="btn btn-secondary btn-sm flex-fill" disabled><i class="bi bi-pause-circle me-1"></i> Voting closed</button>';
                }

                var defaultImg = "https://images.unsplash.com/photo-1581092919534-89d2a0c94f36?auto=format&fit=crop&w=800&q=80";
                var img = p.image || defaultImg;
                var statusClass = p.status === 'progress' ? 'progress' : (p.status || 'review');

                return (
                    '<div class="col-md-6 col-lg-6" data-id="' + p.id + '">' +
                    '<div class="project-card h-100 d-flex flex-column">' +
                    '<img src="' + img + '" alt="' + esc(p.title) + '" style="height:180px; object-fit:cover;">' +
                    '<div class="project-body d-flex flex-column flex-grow-1">' +
                    '<div class="d-flex justify-content-between align-items-start mb-2 gap-2">' +
                    '<h5 class="mb-0 fw-semibold">' + esc(p.title) + '</h5>' +
                    '<span class="status-badge ' + esc(statusClass) + '">' + esc(p.statusLabel || p.status) + '</span>' +
                    '</div>' +
                    '<p class="meta-info mb-2 small text-muted">' +
                    '<i class="bi bi-geo-alt-fill text-danger me-1"></i> ' + esc(p.area ? p.area + ' · ' + p.constituency : p.constituency) + '<br>' +
                    '<i class="bi bi-cash-stack text-success me-1"></i> Budget: ' + esc(formatMoney(p.budget)) + '<br>' +
                    '<i class="bi bi-tag text-primary me-1"></i> Sector: ' + esc(p.sector || p.category || 'General') +
                    '</p>' +
                    '<p class="mb-3 small"><i class="bi bi-hand-thumbs-up-fill text-danger"></i> <span class="vote-count js-count" data-id="' + p.id + '">' + Number(p.votes || 0).toLocaleString() + '</span> citizen votes</p>' +
                    '<div class="mt-auto">' +
                    '<div class="d-flex justify-content-between small mb-1">' +
                    '<span>Progress</span>' +
                    '<span class="fw-semibold">' + (p.progress || 0) + '%</span>' +
                    '</div>' +
                    '<div class="progress mb-3" style="height:6px;">' +
                    '<div class="progress-bar bg-success" style="width: ' + (p.progress || 0) + '%"></div>' +
                    '</div>' +
                    '<div class="d-flex gap-2">' +
                    voteBtn +
                    '<a class="btn btn-outline-gov btn-sm flex-fill" href="feedback.php?project=' + encodeURIComponent(p.id) + '">Details</a>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
            }).join("");
        }

        function castDbVote(projectId, btn) {
            if (!currentUser || !currentUser.is_citizen) {
                showAlert('warning', 'Please <a href="login.php" class="alert-link">sign in with your citizen account</a> to vote on CDF projects.', true);
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Voting…';

            csrf().then(function (token) {
                return fetch('api/vote.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
                    body: JSON.stringify({ project_id: projectId })
                });
            }).then(function (r) {
                return r.json().catch(function () {
                    return { ok: false, error: 'Invalid response from server.' };
                });
            }).catch(function () {
                return { ok: false, error: 'Could not connect to server. Check your connection.' };
            }).then(function (res) {
                if (!res.ok) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-hand-thumbs-up me-1"></i> Vote';
                    showAlert('danger', res.error || 'Failed to record your vote.');
                    return;
                }

                // Update local model
                var p = dbProjects.find(function (item) { return item.id === projectId; });
                if (p) {
                    p.votes = res.votes;
                    p.user_voted = true;
                }

                showAlert('success', res.message || 'Your vote has been cast successfully!');
                renderCards();
                renderStats();
            });
        }

        // Attach event listener to grid for real DB voting
        grid.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-db-vote');
            if (!btn) return;
            var id = Number(btn.getAttribute('data-id'));
            castDbVote(id, btn);
        });

        // Load projects from API
        fetch('api/vote.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok && Array.isArray(d.projects) && d.projects.length > 0) {
                    dbProjects = d.projects;
                    currentUser = d.user;
                    renderCards();
                    renderStats();
                } else {
                    // Fallback to client storage seed projects if database has no projects yet
                    var votes = CDF.getVotes();
                    var list = CDF.votableProjects();
                    dbProjects = list.map(function (p) {
                        return {
                            id: p.id,
                            title: p.title,
                            area: p.constituency,
                            constituency: p.constituency,
                            sector: p.category,
                            budget: p.budget,
                            status: p.status,
                            statusLabel: p.statusLabel,
                            progress: p.progress,
                            voting_open: p.votable ? 1 : 0,
                            votes: votes[p.id] || 0,
                            user_voted: CDF.hasVoted(p.id),
                            image: p.image
                        };
                    });
                    renderCards();
                    renderStats();
                    CDF.bindVotes(grid);
                }
            })
            .catch(function () {
                // Fallback to client storage
                var votes = CDF.getVotes();
                var list = CDF.votableProjects();
                dbProjects = list.map(function (p) {
                    return {
                        id: p.id,
                        title: p.title,
                        area: p.constituency,
                        constituency: p.constituency,
                        sector: p.category,
                        budget: p.budget,
                        status: p.status,
                        statusLabel: p.statusLabel,
                        progress: p.progress,
                        voting_open: p.votable ? 1 : 0,
                        votes: votes[p.id] || 0,
                        user_voted: CDF.hasVoted(p.id),
                        image: p.image
                    };
                });
                renderCards();
                renderStats();
                CDF.bindVotes(grid);
            });
    })();
</script>
</body>
</html>
