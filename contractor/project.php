<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me  = current_user();
$tab = (($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Project | Contractor Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="ddo.css" rel="stylesheet">
</head>
<body class="ddo-shell">

<?php include __DIR__ . '/../includes/topbar.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top ddo-nav">
    <div class="container"><a class="navbar-brand" href="index.php"><span class="ddo-brand-mark"><i
                        class="bi bi-clipboard2-pulse"></i></span>
            <div>Contractor Workspace<small class="text-muted d-block">District delivery office</small></div>
        </a>
        <div><a class="btn btn-outline-gov btn-sm" href="projects.php">Back to projects</a></div>
    </div>
</nav>
<main class="container py-5">
    <div id="content"></div>
</main>
<footer>
    <div class="container">
        <div class="footer-bottom text-center mt-0">Contractor Workspace · <a href="../index.php">Citizen portal</a></div>
    </div>
</footer>
<script src="ddo.js"></script>
<script>
    let id = new URLSearchParams(location.search).get('id'), all = DDO.get(), p = all.filter(function (x) {
        return x.id === id
    })[0] || all[0];

    function render() {
        if (!p) {
            document.getElementById('content').innerHTML = '<div class="ddo-panel ddo-empty">No approved project is available.</div>';
            return
        }
        document.getElementById('content').innerHTML = '<div class="row g-4"><div class="col-lg-8"><div class="ddo-panel"><div class="ddo-panel-header d-flex justify-content-between gap-3"><div><div class="ddo-kicker">' + p.area + ' · ' + p.sector + ' · ' + p.proposer + '</div><h1 class="h3 mb-1">' + p.title + '</h1><p class="text-muted mb-0">Citizen priority: ' + p.votes.toLocaleString() + ' votes · Request: ' + DDO.money(p.budget) + '</p></div>' + DDO.badge(p) + '</div><div class="ddo-panel-body"><div class="d-flex justify-content-between small mb-1"><span>Project progress</span><strong>' + p.progress + '%</strong></div><div class="ddo-progress mb-3"><div class="progress-bar" style="width:' + p.progress + '%"></div></div><p class="small text-muted">' + (p.report || 'No delivery report recorded yet.') + '</p><hr><h2 class="h5">Assign project delivery</h2><form id="form" class="row g-3"><div class="col-md-6"><label class="form-label small fw-semibold">Supplier</label><input name="supplier" class="form-control" required value="' + p.supplier + '"></div><div class="col-md-6"><label class="form-label small fw-semibold">Supervisor</label><input name="supervisor" class="form-control" required value="' + p.supervisor + '"></div><div class="col-md-6"><label class="form-label small fw-semibold">Start date</label><input name="start" type="date" class="form-control" required value="' + p.start + '"></div><div class="col-md-6"><label class="form-label small fw-semibold">Target completion</label><input name="end" type="date" class="form-control" required value="' + p.end + '"></div><div class="col-md-4"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="planning">Assignment pending</option><option value="active">In progress</option><option value="completed">Completed</option><option value="blocked">Blocked</option></select></div><div class="col-md-4"><label class="form-label small fw-semibold">Progress %</label><input name="progress" type="number" min="0" max="100" class="form-control" required value="' + p.progress + '"></div><div class="col-md-4"><label class="form-label small fw-semibold">Latest report date</label><input name="date" type="date" class="form-control" value="' + (p.lastDate || '') + '"></div><div class="col-12"><label class="form-label small fw-semibold">Progress report</label><textarea name="report" class="form-control" rows="3" required>' + p.report + '</textarea></div><div class="col-12"><label class="form-label small fw-semibold">Expected result</label><textarea name="result" class="form-control" rows="3" required>' + p.result + '</textarea></div><div class="col-12"><button class="btn btn-cdf" type="submit"><i class="bi bi-save me-2"></i>Save delivery record</button><span id="saved" class="small text-success ms-3"></span></div></form></div></div></div><div class="col-lg-4"><div class="ddo-panel"><div class="ddo-panel-header"><h2 class="h5 mb-0">Funding record</h2></div><div class="ddo-panel-body small">' + (p.allocation ? '<p><strong>Allocated</strong><br>' + DDO.money(p.allocation.amount) + '</p><p><strong>Source</strong><br>' + p.allocation.source + '</p><p><strong>DDO reference</strong><br>' + p.allocation.reference + ' · ' + p.allocation.date + '</p><p><strong>Approved scope</strong><br>' + p.allocation.scope + '</p>' : '<div class="ddo-callout">No allocation record has been attached by the MP office yet.</div>') + '<hr><p class="mb-0"><strong>Expected result</strong><br>' + p.result + '</p></div></div></div></div>';
        document.querySelector('[name=status]').value = p.status;
        document.getElementById('form').addEventListener('submit', function (e) {
            e.preventDefault();
            DDO.save(p.id, this);
            all = DDO.get();
            p = all.filter(function (x) {
                return x.id === p.id
            })[0];
            document.getElementById('saved').textContent = 'Delivery record saved';
            render()
        })
    }

    render();</script>
</body>
</html>