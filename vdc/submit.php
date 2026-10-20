<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('council', '../auth.php');   // only District Council accounts may submit projects
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submit Project | DC Workspace</title>
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
                DC Workspace
                <small class="text-muted d-block">District Council</small>
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
                    <div id="alert"></div>

                    <form id="proposalForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="title">Project title</label>
                            <input id="title" name="title" class="form-control" required maxlength="180" placeholder="e.g. Community water point">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold" for="area">Village or area</label>
                                <input id="area" name="area" class="form-control" required maxlength="150" placeholder="Area served by the project">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="sector">Development sector</label>
                                <select id="sector" name="sector" class="form-select" required>
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

                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="constituency">Constituency</label>
                            <select id="constituency" name="constituency" class="form-select" required>
                                <option value="" selected disabled>Select the constituency this project is for</option>
                            </select>
                            <div class="form-text">The Member of Parliament and citizens of this constituency will see the proposal.</div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="budget">Estimated budget (MK)</label>
                                <input id="budget" name="budget" type="number" min="1" step="0.01" class="form-control" required placeholder="e.g. 18500000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="beneficiaries">Expected beneficiaries</label>
                                <input id="beneficiaries" name="beneficiaries" type="number" min="1" step="1" class="form-control" placeholder="Number of people or households">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label fw-semibold" for="description">Problem and proposed solution</label>
                            <textarea id="description" name="description" class="form-control" rows="5" required maxlength="5000" placeholder="Describe the local need, proposed works, and intended result."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="doc">
                                Supporting document
                                <span class="text-muted fw-normal">(coming soon)</span>
                            </label>
                            <input id="doc" type="file" class="form-control" disabled>
                            <div class="form-text">Document upload is not switched on yet. Keep needs assessments, sketches and quotations for the review meeting.</div>
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
            <p class="mb-0">DC Workspace · <a href="../index.php">Citizen portal</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";

        var form = document.getElementById('proposalForm');
        var box = document.getElementById('alert');

        function show(type, build) {
            box.innerHTML = '<div class="alert alert-' + type + '" role="alert"></div>';
            build(box.firstChild);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Constituency list from the database
        fetch('../api/constituencies.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var select = document.getElementById('constituency');
                var groups = {};
                (d.constituencies || []).forEach(function (c) {
                    (groups[c.district] = groups[c.district] || []).push(c);
                });
                Object.keys(groups).sort().forEach(function (district) {
                    var g = document.createElement('optgroup');
                    g.label = district + ' District';
                    groups[district].forEach(function (c) {
                        var o = document.createElement('option');
                        o.value = c.code;
                        o.textContent = c.name;
                        g.appendChild(o);
                    });
                    select.appendChild(g);
                });
            })
            .catch(function () {
                show('danger', function (el) { el.textContent = 'Could not load the constituency list. Refresh the page.'; });
            });

        function csrf() {
            return fetch('../api/csrf.php', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) { return d.token; });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            var label = btn.innerHTML;
            btn.disabled = true;
            btn.textContent = 'Submitting…';

            var payload = {
                title: form.title.value.trim(),
                area: form.area.value.trim(),
                sector: form.sector.value,
                constituency: form.constituency.value,
                budget: form.budget.value,
                beneficiaries: form.beneficiaries.value,
                description: form.description.value.trim()
            };

            csrf().then(function (token) {
                return fetch('../api/projects.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
                    body: JSON.stringify(payload)
                });
            }).then(function (r) {
                return r.json().catch(function () {
                    return { ok: false, error: 'Unexpected response from the server.' };
                });
            }).catch(function () {
                return { ok: false, error: 'Could not reach the server. Check your connection and try again.' };
            }).then(function (res) {
                btn.disabled = false;
                btn.innerHTML = label;

                if (!res.ok) {
                    show('danger', function (el) { el.textContent = res.error || 'Could not submit the proposal.'; });
                    return;
                }
                form.reset();
                show('success', function (el) {
                    var strong = document.createElement('strong');
                    strong.textContent = 'Proposal submitted. ';
                    el.appendChild(strong);
                    el.appendChild(document.createTextNode('Reference ' + res.reference + '. It is now in your project register for committee review. '));
                    var link = document.createElement('a');
                    link.href = 'projects.php';
                    link.textContent = 'View my projects';
                    el.appendChild(link);
                });
            });
        });
    })();
</script>
</body>
</html>