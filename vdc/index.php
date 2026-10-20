<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me  = current_user();
$tab = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VDC Workspace | CDF Malawi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="vdc.css" rel="stylesheet">
</head>
<body class="vdc-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<div class="gov-topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <div><i class="bi bi-building"></i> Official Government of Malawi Portal</div>
        <div>
            <?php if ($topbarUser): ?>
                <span class="d-none d-md-inline" style="opacity:.75">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($topbarUser['full_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="mx-2 d-none d-md-inline">|</span>
                <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            <?php else: ?>
                <a href="../api/login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                <span class="mx-2">|</span>
                <a href="../api/login.php?tab=register"><i class="bi bi-person-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>

    <nav class="navbar navbar-expand-lg sticky-top vdc-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="vdc-brand-mark"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <div>VDC Workspace</div>
                    <small class="text-muted">Village Development Committee</small>
                </div>
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#vdcNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="vdcNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li><a class="nav-link active" href="index.php">Overview</a></li>
                    <li><a class="nav-link" href="submit.php">Submit project</a></li>
                    <li><a class="nav-link" href="projects.php">My projects</a></li>
                    <li><a class="nav-link" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link" href="priorities.php">Priorities</a></li>
                    <li><a class="nav-link" href="reports.php">Reports</a></li>
                    <li class="ms-lg-2">
                        <a class="btn btn-cdf btn-sm" href="../index.php">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Citizen portal
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="vdc-hero">
        <div class="container">
            <div class="vdc-eyebrow mb-2">Committee workspace · 2026 funding cycle</div>
            <h1>Turn local priorities into accountable development.</h1>
            <p class="lead mb-4">
                Propose projects for your area, follow every submission through review,
                and use citizen priorities to guide committee decisions.
            </p>
            <a class="btn btn-light fw-semibold" href="submit.php">
                <i class="bi bi-plus-circle me-2"></i>Submit a project
            </a>
        </div>
    </header>

    <main class="container py-5">

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="vdc-panel vdc-stat">
                    <div class="number" id="total">0</div>
                    <div class="label">Total submissions</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="vdc-panel vdc-stat red">
                    <div class="number" id="review">0</div>
                    <div class="label">Awaiting committee action</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="vdc-panel vdc-stat gold">
                    <div class="number" id="accepted">0</div>
                    <div class="label">Accepted projects</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="vdc-panel vdc-stat blue">
                    <div class="number" id="votes">0</div>
                    <div class="label">Citizen priority votes</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <section class="vdc-panel">
                    <div class="vdc-panel-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-1">Submission pipeline</h2>
                            <p class="text-muted small mb-0">A live view of projects proposed by the committee.</p>
                        </div>
                        <a href="projects.php" class="btn btn-outline-gov btn-sm">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table vdc-table mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Budget</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th class="text-end">Votes</th>
                                </tr>
                            </thead>
                            <tbody id="recent"></tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-lg-4">
                <section class="vdc-panel h-100">
                    <div class="vdc-panel-header">
                        <h2 class="h5 mb-1">Committee actions</h2>
                        <p class="text-muted small mb-0">Keep the local development cycle moving.</p>
                    </div>
                    <div class="vdc-panel-body d-grid gap-2">
                        <a href="submit.php" class="btn btn-cdf text-start">
                            <i class="bi bi-file-earmark-plus me-2"></i> Propose new project
                        </a>
                        <a href="decisions.php" class="btn btn-outline-gov text-start">
                            <i class="bi bi-clipboard-check me-2"></i> Review decisions
                        </a>
                        <a href="priorities.php" class="btn btn-outline-gov text-start">
                            <i class="bi bi-bar-chart-line me-2"></i> See citizen priorities
                        </a>
                        <a href="reports.php" class="btn btn-outline-gov text-start">
                            <i class="bi bi-file-bar-graph me-2"></i> Open reports
                        </a>
                        <div class="vdc-callout mt-3 small">
                            <strong>Committee note</strong><br>
                            Use vote rankings as evidence during review, alongside need, feasibility, and available funding.
                        </div>
                    </div>
                </section>
            </div>
        </div>

    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">VDC Workspace · CDF Malawi · <a href="../index.php">Return to citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="vdc.js"></script>
    <script>
        var list = VDC.get();

        document.getElementById('total').textContent = list.length;
        document.getElementById('review').textContent = list.filter(function (p) {
            return p.status === 'review' || p.status === 'submitted';
        }).length;
        document.getElementById('accepted').textContent = list.filter(function (p) {
            return p.status === 'accepted' || p.status === 'progress' || p.status === 'completed';
        }).length;
        document.getElementById('votes').textContent = list.reduce(function (s, p) {
            return s + p.votes;
        }, 0).toLocaleString();
        document.getElementById('recent').innerHTML = list.slice(0, 5).map(function (p) {
            return VDC.row(p, false);
        }).join('');
    </script>
</body>
</html>
