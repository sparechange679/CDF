<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('ddo');
include 'includes/head.php';
include 'includes/nav.php';
$projectId = (int) ($_GET['project'] ?? 0);
?>

<header class="ddo-hero">
    <div class="container">
        <div class="ddo-eyebrow">Procurement</div>
        <h1 class="h2">Contractor quotation</h1>
        <p class="mb-0">Request a price from the contractor you assigned, review what they send back, accept or reject it, then pay the accepted amount.</p>
    </div>
</header>

<main class="container py-5">
    <a href="project.php?id=<?= $projectId ?>" class="btn btn-outline-gov btn-sm mb-3"><i class="bi bi-arrow-left me-1"></i>Back to project</a>
    <div id="alert"></div>
    <div id="content"><div class="ddo-panel ddo-empty">Loading quotation…</div></div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">DDO Workspace · <a href="../index.php">Citizen portal</a></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";
        var PID = <?= $projectId ?>;
        var project = null, q = null;

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
                payload.project_id = PID;
                return fetch('../api/quotations.php', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': t.token },
                    body: JSON.stringify(payload)
                });
            }).then(function (r) { return r.json(); })
                .catch(function () { return { ok: false, error: 'Could not reach the server.' }; });
        }

        function steps(status) {
            var at = !q ? 0 : (status === 'requested' ? 1 : (status === 'submitted' ? 2 : 3));
            var labels = ['Contractor assigned', 'Quotation requested', 'Quotation received', 'Decision'];
            return '<div class="d-flex flex-wrap gap-2 mb-4">' + labels.map(function (l, i) {
                var done = i <= at;
                return '<span class="badge ' + (done ? 'bg-primary' : 'bg-light text-muted border') + ' px-3 py-2">' + (i + 1) + '. ' + l + '</span>';
            }).join('') + '</div>';
        }

        function requestForm(title) {
            return '<div class="ddo-panel"><div class="ddo-panel-header"><h2 class="h5 mb-1">' + title + '</h2>' +
                '<p class="text-muted small mb-0">The contractor will be emailed and will see this on their Quotations page.</p></div>' +
                '<div class="ddo-panel-body"><form id="reqForm">' +
                '<div class="mb-3"><label class="form-label small fw-bold" for="due">Reply by (optional)</label><input type="date" id="due" class="form-control"></div>' +
                '<div class="mb-3"><label class="form-label small fw-bold" for="note">Instructions for the quotation</label>' +
                '<textarea id="note" class="form-control" rows="4" maxlength="2000" placeholder="What the price should cover, materials, labour, warranty…"></textarea></div>' +
                '<button class="btn btn-cdf" type="submit"><i class="bi bi-send me-2"></i>Request quotation</button></form></div></div>';
        }

        function render() {
            var p = project;
            if (!p.contractor_id) {
                document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">No contractor is assigned to this project yet. <a href="project.php?id=' + PID + '">Assign one first</a>.</div>';
                return;
            }
            var who = p.company_name ? p.company_name + ' (' + p.contractor_name + ')' : p.contractor_name;
            var main = '';
            var status = q ? q.status : null;

            if (!q || status === 'rejected') {
                main = (status === 'rejected'
                        ? '<div class="alert alert-warning">Last quotation was rejected' + (q.decision_note ? ': ' + esc(q.decision_note) : '') + '. You can request a new one.</div>' : '') +
                    requestForm(status === 'rejected' ? 'Request a new quotation' : 'Request a quotation');
            } else if (status === 'requested') {
                main = '<div class="ddo-panel"><div class="ddo-panel-body"><div class="text-center py-3">' +
                    '<i class="bi bi-hourglass-split fs-1 text-primary"></i><h2 class="h5 mt-2">Waiting for the contractor</h2>' +
                    '<p class="text-muted mb-0">Requested on ' + esc(q.requested_at) + (q.due_date ? ' · reply by ' + esc(q.due_date) : '') + '.</p></div>' +
                    (q.request_note ? '<hr><div class="small text-muted">Your instructions</div><p class="mb-0">' + esc(q.request_note) + '</p>' : '') +
                    '</div></div>';
            } else if (status === 'submitted' || status === 'accepted') {
                var diff = q.amount - p.budget;
                main = '<div class="ddo-panel"><div class="ddo-panel-header d-flex justify-content-between align-items-center">' +
                    '<div><h2 class="h5 mb-1">Quotation received</h2><div class="text-muted small">Submitted ' + esc(q.submitted_at) + '</div></div>' +
                    (status === 'accepted' ? '<span class="ddo-status completed">Accepted</span>' : '<span class="ddo-status planning">Awaiting your decision</span>') + '</div>' +
                    '<div class="ddo-panel-body"><div class="row g-3 mb-3">' +
                    '<div class="col-sm-4"><div class="small text-muted">Quoted price</div><div class="fs-4 fw-bold text-success">' + mk(q.amount) + '</div></div>' +
                    '<div class="col-sm-4"><div class="small text-muted">Duration</div><div class="fs-5 fw-semibold">' + q.duration_days + ' days</div></div>' +
                    '<div class="col-sm-4"><div class="small text-muted">Against budget (' + mk(p.budget) + ')</div><div class="fw-semibold ' + (diff > 0 ? 'text-danger' : 'text-success') + '">' +
                    (diff > 0 ? mk(diff) + ' over' : mk(-diff) + ' under') + '</div></div></div>' +
                    (q.breakdown ? '<div class="small text-muted">Breakdown</div><p style="white-space:pre-line">' + esc(q.breakdown) + '</p>' : '') +
                    (status === 'accepted'
                        ? '<div class="ddo-callout small">Accepted on ' + esc(q.decided_at) + (q.decision_note ? ' · ' + esc(q.decision_note) : '') + '</div>' +
                        '<div id="payBox" class="mt-3"><div class="text-muted small">Loading payment details…</div></div>'
                        : '<hr><form id="decForm"><label class="form-label small fw-bold" for="dnote">Note to contractor (required if rejecting)</label>' +
                        '<textarea id="dnote" class="form-control mb-3" rows="2" maxlength="2000"></textarea>' +
                        '<div class="d-flex gap-2"><button type="button" class="btn btn-cdf" data-d="accepted"><i class="bi bi-check2-circle me-1"></i>Accept quotation</button>' +
                        '<button type="button" class="btn btn-outline-danger" data-d="rejected"><i class="bi bi-x-circle me-1"></i>Reject</button></div></form>') +
                    '</div></div>';
            }

            document.getElementById('content').innerHTML =
                steps(status) +
                '<div class="row g-4"><div class="col-lg-8">' + main + '</div>' +
                '<div class="col-lg-4"><div class="ddo-panel"><div class="ddo-panel-header"><h2 class="h6 mb-0">Project</h2></div><div class="ddo-panel-body small">' +
                '<p class="mb-2"><strong>' + esc(p.title) + '</strong><br><span class="text-muted">' + esc(p.reference) + '</span></p>' +
                '<p class="mb-2"><span class="text-muted">Location</span><br>' + esc(p.area) + ', ' + esc(p.constituency) + '</p>' +
                '<p class="mb-2"><span class="text-muted">Approved budget</span><br>' + mk(p.budget) + '</p>' +
                '<p class="mb-0"><span class="text-muted">Contractor</span><br>' + esc(who) + '<br><span class="text-muted">' + esc(p.contractor_email) + '</span></p>' +
                '</div></div></div></div>';

            var rf = document.getElementById('reqForm');
            if (rf) rf.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = rf.querySelector('button'); btn.disabled = true;
                post({ action: 'request', note: document.getElementById('note').value, due_date: document.getElementById('due').value }).then(function (r) {
                    btn.disabled = false;
                    if (!r.ok) return alertBox('danger', r.error);
                    alertBox('success', r.message); load();
                });
            });
            var df = document.getElementById('decForm');
            if (df) df.addEventListener('click', function (e) {
                var b = e.target.closest('button[data-d]'); if (!b) return;
                post({ action: 'decide', decision: b.dataset.d, note: document.getElementById('dnote').value }).then(function (r) {
                    if (!r.ok) return alertBox('danger', r.error);
                    alertBox('success', b.dataset.d === 'accepted'
                        ? 'Quotation accepted. You can now pay the quoted amount below.'
                        : r.message);
                    load();
                });
            });

            if (status === 'accepted') loadPayment();
        }

        /* ---------------- Payment (Stripe) ---------------- */
        function loadPayment() {
            fetch('../api/payments.php?project_id=' + PID, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var box = document.getElementById('payBox');
                    if (!box) return;
                    if (!d.ok) {
                        box.innerHTML = '<div class="alert alert-danger mb-0">' + esc(d.error) + '</div>';
                        return;
                    }
                    if (d.payment) {
                        box.innerHTML = '<div class="alert alert-success mb-0"><i class="bi bi-check-circle-fill me-2"></i>' +
                            '<strong>Paid.</strong> ' + mk(d.payment.amount_mk) + ' (' + esc(d.payment.currency) + ' ' +
                            Number(d.payment.charge_amount).toFixed(2) + ') was paid' +
                            (d.allocation ? ' and allocated under <strong>' + esc(d.allocation.reference) + '</strong>' : '') +
                            '. A receipt was emailed to you.</div>';
                        return;
                    }
                    if (d.preview) {
                        box.innerHTML = '<div class="border rounded p-3 bg-light">' +
                            '<div class="small text-muted">Amount to pay</div>' +
                            '<div class="fs-4 fw-bold">' + mk(d.preview.amount_mk) + '</div>' +
                            '<div class="small text-muted mb-3">Charged as ' + esc(d.preview.currency) + ' ' +
                            Number(d.preview.charge_amount).toFixed(2) + ' via Stripe. This becomes the fund allocation for the project.</div>' +
                            '<button id="payBtn" type="button" class="btn btn-cdf"><i class="bi bi-credit-card me-2"></i>Pay now</button></div>';
                        document.getElementById('payBtn').addEventListener('click', startPayment);
                    } else {
                        box.innerHTML = '';
                    }
                })
                .catch(function () {
                    var box = document.getElementById('payBox');
                    if (box) box.innerHTML = '<div class="alert alert-danger mb-0">Could not load payment details.</div>';
                });
        }

        function startPayment() {
            var btn = document.getElementById('payBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Redirecting to Stripe…';
            fetch('../api/csrf.php', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (t) {
                    return fetch('../api/payments.php', {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': t.token },
                        body: JSON.stringify({ action: 'checkout', project_id: PID })
                    });
                })
                .then(function (r) { return r.json(); })
                .then(function (r) {
                    if (!r.ok) throw new Error(r.error || 'Could not start payment.');
                    window.location.href = r.url;
                })
                .catch(function (e) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Pay now';
                    alertBox('danger', e.message);
                });
        }

        function load() {
            if (!PID) { document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">No project selected. <a href="projects.php">Back to projects</a></div>'; return; }
            fetch('../api/quotations.php?project_id=' + PID, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) { document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">' + esc(d.error) + '</div>'; return; }
                    project = d.project; q = d.quotation; render();
                })
                .catch(function () { document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">Could not reach the server.</div>'; });
        }

        // Message when Stripe sends the DDO back to this page
        var qs = new URLSearchParams(location.search);
        if (qs.get('paid')) {
            alertBox('success', 'Payment received. Funds have been allocated and a receipt was emailed to you.');
        } else if (qs.get('payment') === 'cancelled') {
            alertBox('warning', 'Payment was cancelled. No money was taken.');
        } else if (qs.get('payment') === 'failed') {
            alertBox('danger', 'We could not confirm the payment. If you were charged, refresh this page in a minute.');
        }
        if (qs.get('paid') || qs.get('payment')) {
            history.replaceState(null, '', 'quotation.php?project=' + PID);
        }

        load();
    })();
</script>
</body>
</html>