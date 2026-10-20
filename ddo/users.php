<?php
include 'includes/head.php';
require_role('ddo');   // only District Development Officers may open this page
?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="ddo-brand-mark"><i class="bi bi-clipboard2-pulse"></i></span>
            <div>
                DDO Workspace
                <small class="text-muted d-block">District delivery office</small>
            </div>
        </a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#ddoNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="ddoNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li><a class="nav-link" href="index.php">Overview</a></li>
                <li><a class="nav-link" href="projects.php">Priority projects</a></li>
                <li><a class="nav-link active" href="users.php">User accounts</a></li>
                <li><a class="nav-link" href="reports.php">Reports &amp; results</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="ddo-hero">
    <div class="container">
        <div class="ddo-eyebrow">Access control</div>
        <h1 class="h2">User accounts</h1>
        <p class="mb-0">Approve new District Council and Contractor accounts, and suspend or restore staff access.</p>
    </div>
</header>

<main class="container py-5">

    <div id="alert"></div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ddo-panel ddo-stat gold">
                <div class="number" id="n-pending">0</div>
                <div class="label">Pending approval</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ddo-panel ddo-stat green">
                <div class="number" id="n-active">0</div>
                <div class="label">Active</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ddo-panel ddo-stat red">
                <div class="number" id="n-suspended">0</div>
                <div class="label">Suspended</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ddo-panel ddo-stat">
                <div class="number" id="n-all">0</div>
                <div class="label">All staff accounts</div>
            </div>
        </div>
    </div>

    <div class="ddo-panel">
        <div class="ddo-panel-header">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold" for="q">Search</label>
                    <input id="q" class="form-control" placeholder="Name, email, company, ward or staff number">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="status">Account status</label>
                    <select id="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="role">Role</label>
                    <select id="role" class="form-select">
                        <option value="">All roles</option>
                        <option value="council">District Council</option>
                        <option value="mp">Member of Parliament</option>
                        <option value="ddo">District Development Officer</option>
                        <option value="contractor">Contractor</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <span id="count" class="small text-muted"></span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table ddo-table mb-0">
                <thead>
                <tr>
                    <th>Person</th>
                    <th>Role</th>
                    <th>Details</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody id="rows">
                <tr><td colspan="6" class="ddo-empty">Loading accounts…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="ddo-callout small mt-4">
        <strong>How access works</strong><br>
        Pending accounts cannot sign in until you activate them. Suspended accounts are signed out and blocked
        until you restore them. Citizen accounts are active as soon as they register and are not listed here.
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">
            <p class="mb-0">DDO Workspace · CDF Malawi · <a href="../index.php">Citizen portal</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        "use strict";

        let API = '../api/ddo_users.php';
        let CSRF = '../api/csrf.php';
        let users = [];
        let me = 0;

        let STATUS = {
            pending:   { cls: 'planning',  label: 'Pending' },
            active:    { cls: 'completed', label: 'Active' },
            suspended: { cls: 'blocked',   label: 'Suspended' }
        };

        function esc(s) {
            let d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function fmt(d) {
            if (!d) return 'Never';
            let t = new Date(d.replace(' ', 'T'));
            return isNaN(t) ? d : t.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function showAlert(msg, type) {
            let box = document.getElementById('alert');
            box.innerHTML = '<div class="alert alert-' + type + ' alert-gov py-2" role="alert"></div>';
            box.firstChild.textContent = msg;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function badge(status) {
            let s = STATUS[status] || STATUS.pending;
            return '<span class="ddo-status ' + s.cls + '"><i class="bi bi-circle-fill" style="font-size:.42rem"></i>' + s.label + '</span>';
        }

        function actionButton(u) {
            if (u.id === me) return '<span class="small text-muted">You</span>';
            if (u.status === 'pending') {
                return '<button class="btn btn-cdf btn-sm" data-id="' + u.id + '" data-action="activate"><i class="bi bi-check2-circle me-1"></i>Activate</button>';
            }
            if (u.status === 'suspended') {
                return '<button class="btn btn-outline-gov btn-sm" data-id="' + u.id + '" data-action="activate"><i class="bi bi-arrow-counterclockwise me-1"></i>Reactivate</button>';
            }
            return '<button class="btn btn-outline-danger btn-sm" data-id="' + u.id + '" data-action="suspend"><i class="bi bi-slash-circle me-1"></i>Suspend</button>';
        }

        function counts() {
            let c = { pending: 0, active: 0, suspended: 0 };
            users.forEach(function (u) { if (c[u.status] !== undefined) c[u.status]++; });
            document.getElementById('n-pending').textContent = c.pending;
            document.getElementById('n-active').textContent = c.active;
            document.getElementById('n-suspended').textContent = c.suspended;
            document.getElementById('n-all').textContent = users.length;
        }

        function render() {
            let q = document.getElementById('q').value.toLowerCase().trim();
            let st = document.getElementById('status').value;
            let ro = document.getElementById('role').value;

            let list = users.filter(function (u) {
                let hay = [u.full_name, u.email, u.detail1, u.detail2, u.role_name].join(' ').toLowerCase();
                return (!st || u.status === st) && (!ro || u.role === ro) && (!q || hay.indexOf(q) > -1);
            });

            document.getElementById('rows').innerHTML = list.length
                ? list.map(function (u) {
                    return '<tr>' +
                        '<td><strong>' + esc(u.full_name) + '</strong>' +
                        '<div class="ddo-kicker">' + esc(u.email) + '</div></td>' +
                        '<td>' + esc(u.role_name) + '</td>' +
                        '<td class="small">' + esc(u.detail1 || '—') +
                        '<div class="ddo-kicker">' + esc(u.detail2 || '') + '</div></td>' +
                        '<td class="small">' + fmt(u.created_at) +
                        '<div class="ddo-kicker">Last sign-in: ' + fmt(u.last_login_at) + '</div></td>' +
                        '<td>' + badge(u.status) + '</td>' +
                        '<td class="text-end">' + actionButton(u) + '</td>' +
                        '</tr>';
                }).join('')
                : '<tr><td colspan="6" class="ddo-empty">No accounts match your filters.</td></tr>';

            document.getElementById('count').textContent = list.length;
        }

        function load() {
            fetch(API, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) {
                        showAlert(d.error || 'Could not load accounts.', 'danger');
                        document.getElementById('rows').innerHTML = '<tr><td colspan="6" class="ddo-empty">Accounts could not be loaded.</td></tr>';
                        return;
                    }
                    users = d.users;
                    me = d.me;
                    counts();
                    render();
                })
                .catch(function () {
                    showAlert('Could not reach the server. Check your connection and try again.', 'danger');
                });
        }

        function change(id, action, btn) {
            let u = users.filter(function (x) { return x.id === id; })[0];
            if (!u) return;

            let verb = action === 'suspend' ? 'suspend' : 'activate';
            let msg = action === 'suspend'
                ? 'Suspend ' + u.full_name + '? They will be signed out and blocked until you restore access.'
                : 'Activate the account for ' + u.full_name + '?';
            if (!window.confirm(msg)) return;

            btn.disabled = true;

            fetch(CSRF, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (t) {
                    return fetch(API, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': t.token },
                        body: JSON.stringify({ id: id, action: action })
                    });
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) {
                        btn.disabled = false;
                        showAlert(d.error || 'Could not update the account.', 'danger');
                        return;
                    }
                    u.status = d.status;
                    // Keep pending/suspended people at the top, like the server order.
                    let order = { pending: 0, active: 1, suspended: 2 };
                    users.sort(function (a, b) { return order[a.status] - order[b.status]; });
                    counts();
                    render();
                    showAlert(u.full_name + (d.status === 'active' ? ' can now sign in.' : ' has been suspended.'), 'success');
                })
                .catch(function () {
                    btn.disabled = false;
                    showAlert('Could not reach the server. Try again.', 'danger');
                });
        }

        document.getElementById('rows').addEventListener('click', function (e) {
            let btn = e.target.closest('button[data-action]');
            if (!btn) return;
            change(Number(btn.dataset.id), btn.dataset.action, btn);
        });

        document.getElementById('q').addEventListener('input', render);
        document.getElementById('status').addEventListener('change', render);
        document.getElementById('role').addEventListener('change', render);

        load();
    })();
</script>
</body>
</html>