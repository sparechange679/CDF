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
    <title>Login | CDF Malawi Citizen Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .citizen-tabs { gap: .5rem; }
        .citizen-tabs .nav-link { text-align: center; }
        .pw-field { position: relative; }
        .pw-field .form-control { padding-right: 2.75rem; }
        .pw-toggle {
            position: absolute; right: .35rem; top: 50%; transform: translateY(-50%);
            border: none; background: none; color: #667078; padding: .4rem .55rem;
        }
    </style>
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
            <div class="col-md-8 col-lg-6">
                <div class="feedback-card">

                    <?php if ($me): ?>
                        <!-- Already signed in -->
                        <div id="logged-in" class="text-center">
                            <i class="bi bi-person-check text-success fs-1"></i>
                            <h1 class="h4 fw-bold mt-2">You are signed in</h1>
                            <p class="mb-1">Signed in as <strong><?= htmlspecialchars($me['full_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                            <p class="text-muted small mb-4"><?= htmlspecialchars($me['role_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="d-grid gap-2">
                                <?php if ($me['role'] === 'citizen'): ?>
                                    <a class="btn btn-cdf" href="projects.php">View my constituency projects</a>
                                <?php else: ?>
                                    <a class="btn btn-cdf" href="<?= htmlspecialchars($me['dashboard'], ENT_QUOTES, 'UTF-8') ?>">Go to my workspace</a>
                                <?php endif; ?>
                                <a class="btn btn-outline-gov" href="logout.php">Logout</a>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-check text-danger fs-1"></i>
                            <h1 class="h4 fw-bold mt-2">Citizen account</h1>
                            <p class="text-muted small mb-0">Sign in or register to vote, track and comment on CDF projects.</p>
                        </div>

                        <ul class="nav nav-pills nav-fill citizen-tabs mb-4" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $tab === 'login' ? 'active' : '' ?>" id="citizen-login-tab" data-bs-toggle="pill" data-bs-target="#citizen-login-pane" type="button" role="tab" aria-controls="citizen-login-pane" aria-selected="<?= $tab === 'login' ? 'true' : 'false' ?>">Sign in</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $tab === 'register' ? 'active' : '' ?>" id="citizen-register-tab" data-bs-toggle="pill" data-bs-target="#citizen-register-pane" type="button" role="tab" aria-controls="citizen-register-pane" aria-selected="<?= $tab === 'register' ? 'true' : 'false' ?>">Create account</button>
                            </li>
                        </ul>

                        <div class="tab-content">

                            <!-- Sign in -->
                            <div class="tab-pane fade <?= $tab === 'login' ? 'show active' : '' ?>" id="citizen-login-pane" role="tabpanel" aria-labelledby="citizen-login-tab" tabindex="0">
                                <div id="login-alert"></div>
                                <form id="login-form">
                                    <!-- Account type: always citizen on this page -->
                                    <input type="hidden" name="role" id="loginRole" value="citizen">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium" for="email">Email</label>
                                        <input type="email" class="form-control" id="email" required autocomplete="username" placeholder="name@example.mw">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium" for="password">Password</label>
                                        <div class="pw-field">
                                            <input type="password" class="form-control" id="password" required autocomplete="current-password">
                                            <button type="button" class="pw-toggle" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="remember">
                                        <label class="form-check-label small" for="remember">Keep me signed in on this device</label>
                                    </div>
                                    <button type="submit" class="btn btn-cdf w-100 mb-3">Sign in</button>
                                </form>
                            </div>

                            <!-- Register -->
                            <div class="tab-pane fade <?= $tab === 'register' ? 'show active' : '' ?>" id="citizen-register-pane" role="tabpanel" aria-labelledby="citizen-register-tab" tabindex="0">
                                <div id="register-alert"></div>
                                <form id="register-form">
                                    <!-- Account type: always citizen on this page -->
                                    <input type="hidden" name="role" id="regRole" value="citizen">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium" for="regName">Full name</label>
                                        <input type="text" class="form-control" id="regName" required autocomplete="name" maxlength="120">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium" for="regEmail">Email</label>
                                        <input type="email" class="form-control" id="regEmail" required autocomplete="email" placeholder="name@example.mw">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium" for="regConstituency">Constituency</label>
                                        <select class='form-select' id='regConstituency' required>
                                            <option value='' selected disabled>Select your constituency</option>
                                        </select>
                                        <div class="form-text">Developments on the portal will be limited to this area.</div>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium" for="regPw">Password</label>
                                            <div class="pw-field">
                                                <input type="password" class="form-control" id="regPw" required minlength="8" autocomplete="new-password">
                                                <button type="button" class="pw-toggle" data-target="regPw" aria-label="Show password"><i class="bi bi-eye"></i></button>
                                            </div>
                                            <div class="form-text">At least eight characters.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium" for="regPw2">Confirm password</label>
                                            <div class="pw-field">
                                                <input type="password" class="form-control" id="regPw2" required minlength="8" autocomplete="new-password">
                                                <button type="button" class="pw-toggle" data-target="regPw2" aria-label="Show password"><i class="bi bi-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label small" for="terms">I confirm the details above are correct and I agree to the portal’s terms of use.</label>
                                    </div>
                                    <button type="submit" class="btn btn-cdf w-100 mb-3">Create account</button>
                                </form>
                            </div>
                        </div>

                        <hr class="my-3">
                        <p class="small text-muted text-center mb-0">
                            Are you not a citizen? Go to the <a href="auth.php" class="text-link">staff sign-in and registration</a>
                            for District Council, MPs, District Development Officers and Contractors.
                        </p>
                    <?php endif; ?>

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
<script src="js/citizen-login.js"></script>
</body>
</html>