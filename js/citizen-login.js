// Include on login.php after js/app.js:  <script src="js/citizen-login.js"></script>
// Signs citizens in through the PHP API. On success it also stores the
// constituency in the existing CDF session so the portal keeps filtering by it.
(function () {
    "use strict";

    var form = document.getElementById("login-form");
    if (!form) return;

    function showAlert(message) {
        document.getElementById("login-alert").innerHTML =
            '<div class="alert alert-danger alert-gov py-2" role="alert"></div>';
        document.querySelector("#login-alert .alert").textContent = message;
    }

    function csrfToken() {
        return fetch("api/csrf.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (d) { return d.token; });
    }

    // Capture phase so this runs before the old demo handler on the page.
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var btn = form.querySelector('button[type="submit"]');
        var label = btn.textContent;
        btn.disabled = true;
        btn.textContent = "Signing in…";

        csrfToken().then(function (token) {
            return fetch("api/login.php", {
                method: "POST",
                credentials: "same-origin",
                headers: { "Content-Type": "application/json", "X-CSRF-Token": token },
                body: JSON.stringify({
                    role: "citizen",
                    email: document.getElementById("email").value.trim(),
                    password: document.getElementById("password").value
                })
            });
        }).then(function (r) {
            return r.json();
        }).then(function (res) {
            if (!res.ok) {
                btn.disabled = false;
                btn.textContent = label;
                showAlert(res.error);
                return;
            }
            if (window.CDF && res.constituency) {
                CDF.saveSession({
                    id: document.getElementById("email").value.trim(),
                    constituencyId: res.constituency.code,
                    constituencyName: res.constituency.name,
                    at: Date.now()
                });
            }
            window.location.href = "projects.php";
        }).catch(function () {
            btn.disabled = false;
            btn.textContent = label;
            showAlert("Could not reach the server. Try again.");
        });
    }, true);
})();
