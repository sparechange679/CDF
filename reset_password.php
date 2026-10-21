<?php require_once __DIR__ . '/includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password | CDF Malawi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet"></head>
<body>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-5" style="max-width:480px">
    <div class="feedback-card">
        <h1 class="h4 fw-bold">Choose a new password</h1>
        <div id="msg"></div>
        <form id="f">
            <label for="pw"></label><input type="password" id="pw" class="form-control mb-3" placeholder="New password (8+ characters)" minlength="8" required autocomplete="new-password">
            <label for="pw2"></label><input type="password" id="pw2" class="form-control mb-3" placeholder="Confirm password" minlength="8" required autocomplete="new-password">
            <button class="btn btn-cdf w-100" type="submit">Change password</button>
        </form>
    </div>
</div>
<script>
    let token = new URLSearchParams(location.search).get('token') || '';
    function show(type, text) {
        var m = document.getElementById('msg');
        m.innerHTML = '<div class="alert alert-' + type + '"></div>'; m.firstChild.textContent = text;
    }
    document.getElementById('f').addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = this.querySelector('button'); btn.disabled = true;
        fetch('api/csrf.php', {credentials:'same-origin'}).then(r => r.json()).then(t =>
            fetch('api/forgot_password.php', {method:'POST', credentials:'same-origin',
                headers:{'Content-Type':'application/json','X-CSRF-Token':t.token},
                body: JSON.stringify({action:'reset', token: token, password: pw.value, confirm: pw2.value})}))
            .then(r => r.json()).then(d => {
            if (d.ok) { show('success', d.message + ' Redirecting…'); setTimeout(() => location.href = 'auth.php', 2000); }
            else { show('danger', d.error); btn.disabled = false; }
        }).catch(() => { show('danger', 'Could not reach the server.'); btn.disabled = false; });
    });
</script></body></html>