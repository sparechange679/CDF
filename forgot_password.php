<?php require_once __DIR__ . '/includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password | CDF Malawi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet"></head>
<body>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="container py-5" style="max-width:480px">
    <div class="feedback-card">
        <h1 class="h4 fw-bold">Forgot your password?</h1>
        <p class="text-muted small">Enter your account email and we will send you a reset link.</p>
        <div id="msg"></div>
        <form id="f">
            <input type="email" id="email" class="form-control mb-3" placeholder="name@example.mw" required>
            <button class="btn btn-cdf w-100" type="submit">Send reset link</button>
        </form>
        <p class="small text-center mt-3 mb-0"><a href="login.php">Citizen sign-in</a> · <a href="auth.php">Staff sign-in</a></p>
    </div>
</div>
<script>
    document.getElementById('f').addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = this.querySelector('button'); btn.disabled = true;
        fetch('api/csrf.php', {credentials:'same-origin'}).then(r => r.json()).then(t =>
            fetch('api/forgot_password.php', {method:'POST', credentials:'same-origin',
                headers:{'Content-Type':'application/json','X-CSRF-Token':t.token},
                body: JSON.stringify({action:'request', email: document.getElementById('email').value.trim()})}))
            .then(r => r.json()).then(d => {
            var m = document.getElementById('msg');
            m.innerHTML = '<div class="alert alert-' + (d.ok ? 'success' : 'danger') + '"></div>';
            m.firstChild.textContent = d.message || d.error;
            btn.disabled = false;
        }).catch(() => { btn.disabled = false; });
    });
</script></body></html>