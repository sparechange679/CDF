<?php require_once __DIR__ . '/includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in or register | CDF Malawi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Each role takes the colour of its own workspace */
        .auth-card { --role: #006b3f; --role-dark: #113c2d; --role-tint: #e8f5e9; }
        .auth-card[data-role="mp"]         { --role: #2b2b2b; --role-dark: #121212; --role-tint: #f0f0f0; }
        .auth-card[data-role="ddo"]        { --role: #1769aa; --role-dark: #123a58; --role-tint: #e5f1fb; }
        .auth-card[data-role="contractor"] { --role: #9a6700; --role-dark: #4a3200; --role-tint: #fff3d6; }

        .auth-wrap { padding: 3rem 0 1rem; }

        .auth-card {
            background: #fff;
            border: 1px solid #e4e9ee;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
        }

        /* Left panel */
        .auth-aside {
            background: linear-gradient(155deg, var(--role-dark), var(--role));
            color: #fff;
            padding: 2.5rem 2.25rem;
            display: flex;
            flex-direction: column;
            transition: background .3s ease;
        }
        .aside-icon {
            width: 56px; height: 56px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .16);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1.5rem;
        }
        .auth-aside h2 { font-size: 1.6rem; font-weight: 700; margin-bottom: .6rem; }
        .auth-aside .lead-text { opacity: .9; font-size: .98rem; max-width: 34ch; }
        .aside-tasks { list-style: none; padding: 0; margin: 1.75rem 0 0; }
        .aside-tasks li {
            display: flex; gap: .65rem;
            font-size: .92rem;
            padding: .55rem 0;
            border-top: 1px solid rgba(255, 255, 255, .18);
        }
        .aside-tasks i { opacity: .85; margin-top: 2px; }
        .aside-dest {
            margin-top: auto;
            padding-top: 2rem;
            font-size: .85rem;
            opacity: .85;
        }
        .aside-dest strong { display: block; font-size: .98rem; opacity: 1; }

        /* Right panel */
        .auth-main { padding: 2.25rem; }
        .auth-main h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: .25rem; }

        .role-group { display: grid; grid-template-columns: repeat(2, 1fr); gap: .65rem; }
        @media (min-width: 768px) { .role-group { grid-template-columns: repeat(4, 1fr); } }

        .role-tile input { position: absolute; opacity: 0; pointer-events: none; }
        .role-tile .tile-body {
            display: flex; flex-direction: column; align-items: center; text-align: center;
            gap: .35rem;
            padding: .85rem .5rem;
            border: 1.5px solid #dfe4e8;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            font-size: .82rem; font-weight: 600; line-height: 1.25;
            color: #444;
            height: 100%;
            transition: border-color .15s, background .15s, color .15s;
        }
        .role-tile .tile-body i { font-size: 1.35rem; color: #8a949c; transition: color .15s; }
        .role-tile .tile-body:hover { border-color: #b9c1c7; }
        .role-tile input:checked + .tile-body {
            border-color: var(--role);
            background: var(--role-tint);
            color: var(--role);
        }
        .role-tile input:checked + .tile-body i { color: var(--role); }
        .role-tile input:focus-visible + .tile-body { outline: 3px solid var(--role); outline-offset: 2px; }

        .auth-tabs { border-bottom: 1px solid #e4e9ee; gap: 1.5rem; margin-top: 1.75rem; }
        .auth-tabs .nav-link {
            background: none !important;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            padding: .6rem 0 !important;
            color: #667078 !important;
            font-weight: 600;
        }
        .auth-tabs .nav-link.active {
            color: var(--role) !important;
            border-bottom-color: var(--role);
        }

        .auth-card .form-control:focus,
        .auth-card .form-select:focus {
            border-color: var(--role);
            box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--role) 18%, transparent);
        }
        .auth-card .btn-auth {
            background: var(--role);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: .7rem 1.4rem;
            transition: filter .15s;
        }
        .auth-card .btn-auth:hover { filter: brightness(1.12); color: #fff; }
        .auth-card a.text-link { color: var(--role); font-weight: 600; text-decoration: none; }
        .auth-card a.text-link:hover { text-decoration: underline; }

        .pw-field { position: relative; }
        .pw-field .form-control { padding-right: 2.75rem; }
        .pw-toggle {
            position: absolute; right: .35rem; top: 50%; transform: translateY(-50%);
            border: none; background: none; color: #667078; padding: .4rem .55rem;
        }

        .auth-note { font-size: .82rem; color: #667078; }
        .approval-note {
            font-size: .85rem;
            background: var(--role-tint);
            border-left: 4px solid var(--role);
            border-radius: 6px;
            padding: .65rem .9rem;
            margin-top: 1rem;
        }

        @media (max-width: 767px) {
            .auth-wrap { padding-top: 1.5rem; }
            .auth-aside, .auth-main { padding: 1.5rem; }
            .aside-dest { padding-top: 1.25rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .auth-aside, .role-tile .tile-body, .auth-card .btn-auth { transition: none; }
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
        <a class="btn btn-outline-gov btn-sm" href="index.php"><i class="bi bi-arrow-left me-1"></i> Citizen portal</a>
    </div>
</nav>

<section class="auth-wrap">
    <div class="container">
        <div class="auth-card" id="authCard" data-role="council">
            <div class="row g-0">

                <!-- Role summary -->
                <aside class="col-lg-5 auth-aside" aria-live="polite">
                    <div class="aside-icon"><i class="bi bi-diagram-3" id="asideIcon"></i></div>
                    <h2 id="asideTitle">District Council</h2>
                    <p class="lead-text" id="asideText"></p>
                    <ul class="aside-tasks" id="asideTasks"></ul>
                    <div class="aside-dest">
                        After you sign in, you go to
                        <strong id="asideDest">District Council workspace</strong>
                    </div>
                </aside>

                <!-- Forms -->
                <div class="col-lg-7 auth-main">
                    <h1>Welcome to CDF Malawi</h1>
                    <p class="text-muted mb-4">Choose your role, then sign in or create an account.</p>

                    <fieldset>
                        <legend class="form-label fw-medium small mb-2">I am a</legend>
                        <div class="role-group">
                            <label class="role-tile">
                                <input type="radio" name="role" value="council" checked>
                                <span class="tile-body"><i class="bi bi-diagram-3"></i>District Council</span>
                            </label>
                            <label class="role-tile">
                                <input type="radio" name="role" value="mp">
                                <span class="tile-body"><i class="bi bi-bank"></i>Member of Parliament</span>
                            </label>
                            <label class="role-tile">
                                <input type="radio" name="role" value="ddo">
                                <span class="tile-body"><i class="bi bi-clipboard2-pulse"></i>District Development Officer</span>
                            </label>
                            <label class="role-tile">
                                <input type="radio" name="role" value="contractor">
                                <span class="tile-body"><i class="bi bi-tools"></i>Contractor</span>
                            </label>
                        </div>
                    </fieldset>

                    <ul class="nav auth-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login-pane" type="button" role="tab" aria-controls="login-pane" aria-selected="true">Sign in</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="register-tab" data-bs-toggle="pill" data-bs-target="#register-pane" type="button" role="tab" aria-controls="register-pane" aria-selected="false">Create account</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-4">

                        <!-- Sign in -->
                        <div class="tab-pane fade show active" id="login-pane" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
                            <form id="loginForm">
                                <!-- Which kind of account is signing in: council, mp, ddo or contractor. Set by js/auth.js -->
                                <input type="hidden" name="role" id="loginRole" value="council">
                                <div class="mb-3">
                                    <label class="form-label fw-medium" for="loginId">Email</label>
                                    <input type="email" class="form-control" id="loginId" required autocomplete="username" placeholder="name@example.mw">
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <label class="form-label fw-medium" for="loginPw">Password</label>
                                        <a href="#" class="text-link small">Forgot password?</a>
                                    </div>
                                    <div class="pw-field">
                                        <input type="password" class="form-control" id="loginPw" required autocomplete="current-password">
                                        <button type="button" class="pw-toggle" data-target="loginPw" aria-label="Show password"><i class="bi bi-eye"></i></button>
                                    </div>
                                </div>
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="remember">
                                    <label class="form-check-label small" for="remember">Keep me signed in on this device</label>
                                </div>
                                <button type="submit" class="btn btn-auth w-100">Sign in</button>
                                <p class="auth-note text-center mt-3 mb-0">
                                    Don’t have an account? <a href="#" class="text-link js-to-register">Create one</a>
                                </p>
                            </form>
                        </div>

                        <!-- Register -->
                        <div class="tab-pane fade" id="register-pane" role="tabpanel" aria-labelledby="register-tab" tabindex="0">
                            <form id="registerForm">
                                <!-- Which kind of account is being created: council, mp, ddo or contractor. Set by js/auth.js -->
                                <input type="hidden" name="role" id="regRole" value="council">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium" for="regName">Full name</label>
                                        <input type="text" class="form-control" id="regName" required autocomplete="name">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium" for="regEmail">Email</label>
                                        <input type="email" class="form-control" id="regEmail" required autocomplete="email">
                                    </div>

                                    <!-- Changes with the selected role -->
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="extra1" id="extra1Label">Council or ward</label>
                                        <input type="text" class="form-control" id="extra1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="extra2" id="extra2Label">Position</label>
                                        <input type="text" class="form-control" id="extra2" required>
                                    </div>

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

                                <!-- Only shown for roles that need approval (set by js/auth.js) -->
                                <div class="approval-note d-none" id="approvalNote"></div>

                                <div class="form-check my-4">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label small" for="terms">I confirm the details above are correct and I agree to the portal’s terms of use.</label>
                                </div>
                                <button type="submit" class="btn btn-auth w-100">Create account</button>
                                <p class="auth-note text-center mt-3 mb-0">
                                    Already registered? <a href="#" class="text-link js-to-login">Sign in</a>
                                </p>
                            </form>
                        </div>
                    </div>

                    <hr class="my-4">
                    <p class="auth-note mb-0">
                        Are you a citizen? <a href="./login.php" class="text-link">Use the citizen login</a> to vote and give feedback.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0 border-0 pt-0">
            <p class="mb-0">&copy; 2026 Government of Malawi • Constituency Development Fund (CDF) Citizen Monitoring System</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/auth.js"></script>
</body>
</html>