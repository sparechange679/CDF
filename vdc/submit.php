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
    <title>Submit Project | VDC Workspace</title>
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
                    VDC Workspace
                    <small class="text-muted d-block">Village Development Committee</small>
                </div>
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto">
                    <li><a class="nav-link" href="index.php">Overview</a></li>
                    <li><a class="nav-link active" href="submit.php">Submit project</a></li>
                    <li><a class="nav-link" href="projects.php">My projects</a></li>
                    <li><a class="nav-link" href="decisions.php">Decisions</a></li>
                    <li><a class="nav-link" href="priorities.php">Priorities</a></li>
                    <li><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="vdc-hero">
        <div class="container">
            <div class="vdc-eyebrow">Project intake</div>
            <h1 class="h2">Submit a development proposal</h1>
            <p class="mb-0">Give the committee enough detail to assess the need, cost, and expected community benefit.</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="vdc-panel">
                    <div class="vdc-panel-body">
                        <div id="success" class="alert alert-success d-none">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Proposal submitted.</strong>
                            It has been added to your project register for committee review.
                        </div>

                        <form id="proposalForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Project title</label>
                                <input name="title" class="form-control" required placeholder="e.g. Community water point">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label fw-semibold">Village or area</label>
                                    <input name="area" class="form-control" required placeholder="Area served by the project">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Development sector</label>
                                    <select name="sector" class="form-select" required>
                                        <option value="">Choose sector</option>
                                        <option>Water</option>
                                        <option>Education</option>
                                        <option>Health</option>
                                        <option>Roads</option>
                                        <option>Agriculture</option>
                                        <option>Markets</option>
                                        <option>Energy</option>
                                        <option>Community</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Estimated budget (MK)</label>
                                    <input name="budget" type="number" min="1" class="form-control" required placeholder="e.g. 18500000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Expected beneficiaries</label>
                                    <input name="beneficiaries" type="number" min="1" class="form-control" placeholder="Number of people or households">
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold">Problem and proposed solution</label>
                                <textarea name="description" class="form-control" rows="5" required placeholder="Describe the local need, proposed works, and intended result."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Supporting document
                                    <span class="text-muted fw-normal">(optional)</span>
                                </label>
                                <input type="file" class="form-control">
                                <div class="form-text">Attach a needs assessment, sketch, quotation, or meeting resolution.</div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <a href="index.php" class="btn btn-outline-gov">Cancel</a>
                                <button class="btn btn-cdf" type="submit">
                                    <i class="bi bi-send me-2"></i>Submit proposal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-bottom text-center mt-0">
                <p class="mb-0">VDC Workspace · <a href="../index.php">Citizen portal</a></p>
            </div>
        </div>
    </footer>

    <script src="vdc.js"></script>
    <script>
        document.getElementById('proposalForm').addEventListener('submit', function (e) {
            e.preventDefault();
            VDC.add(this);
            this.reset();
            document.getElementById('success').classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>
