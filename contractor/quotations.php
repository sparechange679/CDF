<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('contractor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotations | Contractor Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="ddo.css" rel="stylesheet">
</head>
<body class="ddo-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>Contractor Workspace<small class="text-muted d-block">Contractor delivery</small></div>
        </a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#ddoNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="ddoNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li><a class="nav-link" href="index.php">Overview</a></li>
                <li><a class="nav-link" href="projects.php">Assigned projects</a></li>
                <li><a class="nav-link" href="quotations.php">Quotations</a></li>
                <li><a class="nav-link" href="reports.php">Reports &amp; results</a></li>
                <li><a class='nav-link active' href='quotations.php'>Quotations</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="ddo-hero">
    <div class="container">
        <div class="ddo-eyebrow">Procurement</div>
        <h1 class="h2">Quotation requests</h1>
        <p class="mb-0">The District Development Officer asks for your price before work starts. Give the cost and how long the work will take.</p>
    </div>
</header>

<main class="container py-5">
    <div id="alert"></div>
    <div id="list"><div class="ddo-panel ddo-empty">Loading quotation requests…</div></div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">Contractor Workspace · <a href="../index.php">Citizen portal</a></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";
        var items = [];

        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
        function mk(n) { return 'MK ' + Number(n || 0).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function alertBox(type, msg) {
            var b = document.getElementById('alert');
            b.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show"></div>';
            b.firstChild.textContent = msg;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function post(payload) {
            return fetch('../api/csrf.php', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (t) {
                return fetch('../api/quotations.php', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': t.token },
                    body: JSON.stringify(payload)
                });
            }).then(function (r) { return r.json(); })
                .catch(function () { return { ok: false, error: 'Could not reach the server.' }; });
        }

        var BADGE = {
            requested: ['planning', 'Price requested'],
            submitted: ['active', 'Sent, awaiting decision'],
            accepted:  ['completed', 'Accepted'],
            rejected:  ['blocked', 'Rejected']
        };

        function card(q) {
            var b = BADGE[q.status];
            var open = q.status === 'requested' || q.status === 'submitted';
            var form = open
                ? '<hr><form class="qForm" data-pid="' + q.project_id + '"><div class="row g-3">' +
                '<div class="col-md-6"><label class="form-label small fw-bold">Your price (MK) <span class="text-danger">*</span></label>' +
                '<input name="amount" type="number" min="1" step="0.01" class="form-control" required value="' + (q.amount != null ? q.amount : '') + '"></div>' +
                '<div class="col-md-6"><label class="form-label small fw-bold">Days to complete <span class="text-danger">*</span></label>' +
                '<input name="duration_days" type="number" min="1" max="3650" class="form-control" required value="' + (q.duration_days || '') + '"></div>' +
                '<div class="col-12"><label class="form-label small fw-bold">Cost breakdown</label>' +
                '<textarea name="breakdown" rows="4" maxlength="3000" class="form-control" placeholder="Materials, labour, transport, other costs…">' + esc(q.breakdown || '') + '</textarea></div>' +
                '<div class="col-12"><button class="btn btn-cdf" type="submit"><i class="bi bi-send-check me-2"></i>' + (q.status === 'submitted' ? 'Update quotation' : 'Send quotation') + '</button></div>' +
                '</div></form>'
                : '';
            var sent = q.status !== 'requested' && q.amount != null && !open
                ? '<hr><div class="row"><div class="col-sm-6"><div class="small text-muted">Your price</div><div class="fs-5 fw-bold">' + mk(q.amount) + '</div></div>' +
                '<div class="col-sm-6"><div class="small text-muted">Duration</div><div class="fs-5 fw-bold">' + q.duration_days + ' days</div></div></div>'
                : '';
            return '<div class="ddo-panel mb-4"><div class="ddo-panel-header d-flex justify-content-between align-items-start gap-3">' +
                '<div><h2 class="h5 mb-1">' + esc(q.title) + '</h2><div class="ddo-kicker">' + esc(q.reference) + ' · ' + esc(q.area) + ' · Budget ' + mk(q.budget) + '</div></div>' +
                '<span class="ddo-status ' + b[0] + '">' + b[1] + '</span></div>' +
                '<div class="ddo-panel-body">' +
                '<div class="small text-muted">Requested ' + esc(q.requested_at) + (q.due_date ? ' · please reply by ' + esc(q.due_date) : '') + '</div>' +
                (q.request_note ? '<p class="mt-2 mb-0">' + esc(q.request_note) + '</p>' : '') +
                (q.decision_note ? '<div class="ddo-callout small mt-3"><strong>DDO note:</strong> ' + esc(q.decision_note) + '</div>' : '') +
                sent + form + '</div></div>';
        }

        function render() {
            document.getElementById('list').innerHTML = items.length
                ? items.map(card).join('')
                : '<div class="ddo-panel ddo-empty">No quotation has been requested from you yet.</div>';
        }

        document.getElementById('list').addEventListener('submit', function (e) {
            var f = e.target.closest('.qForm'); if (!f) return;
            e.preventDefault();
            var btn = f.querySelector('button'); btn.disabled = true;
            post({
                action: 'submit', project_id: Number(f.dataset.pid),
                amount: f.amount.value, duration_days: f.duration_days.value, breakdown: f.breakdown.value
            }).then(function (r) {
                btn.disabled = false;
                if (!r.ok) return alertBox('danger', r.error);
                alertBox('success', r.message); load();
            });
        });

        function load() {
            fetch('../api/quotations.php', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) { document.getElementById('list').innerHTML = '<div class="ddo-panel ddo-empty">' + esc(d.error) + '</div>'; return; }
                    items = d.quotations; render();
                })
                .catch(function () { document.getElementById('list').innerHTML = '<div class="ddo-panel ddo-empty">Could not reach the server.</div>'; });
        }
        load();
    })();
</script>
</body>
</html>