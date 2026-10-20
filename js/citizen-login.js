// Include on login.php after js/app.js:  <script src="js/citizen-login.js"></script>
// Handles citizen sign-in AND registration through the PHP API, loads the
// constituency list from the database, and stores the constituency in the
// CDF browser session so the portal keeps filtering projects by it.
(function () {
    "use strict";

    var loginForm = document.getElementById("login-form");
    var registerForm = document.getElementById("register-form");
    if (!loginForm && !registerForm) return;   // already signed in: nothing to do

    /* ---------- Helpers ---------- */
    function showAlert(boxId, message, type) {
        var box = document.getElementById(boxId);
        if (!box) return;
        box.innerHTML = '<div class="alert alert-' + type + ' alert-gov py-2" role="alert"></div>';
        box.firstChild.textContent = message;
    }

    function getCsrf() {
        return fetch("api/csrf.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (d) { return d.token; });
    }

    function post(url, payload) {
        return getCsrf().then(function (token) {
            return fetch(url, {
                method: "POST",
                credentials: "same-origin",
                headers: { "Content-Type": "application/json", "X-CSRF-Token": token },
                body: JSON.stringify(payload)
            });
        }).then(function (r) {
            return r.json().catch(function () {
                return { ok: false, error: "Unexpected response from the server." };
            });
        }).catch(function () {
            return { ok: false, error: "Could not reach the server. Check your connection and try again." };
        });
    }

    function busy(form, on, label) {
        var btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        if (on) {
            btn.dataset.label = btn.textContent;
            btn.textContent = label;
            btn.disabled = true;
        } else {
            btn.textContent = btn.dataset.label || btn.textContent;
            btn.disabled = false;
        }
    }

    function val(id) { return document.getElementById(id).value; }

    function saveConstituency(email, c) {
        if (window.CDF && c) {
            CDF.saveSession({
                id: email,
                constituencyId: c.code,
                constituencyName: c.name,
                at: Date.now()
            });
        }
    }

    /* ---------- Show / hide password ---------- */
    document.querySelectorAll(".pw-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var input = document.getElementById(this.dataset.target);
            var show = input.type === "password";
            input.type = show ? "text" : "password";
            this.innerHTML = '<i class="bi ' + (show ? "bi-eye-slash" : "bi-eye") + '"></i>';
            this.setAttribute("aria-label", show ? "Hide password" : "Show password");
        });
    });

    /* ---------- Constituency list (from the database) ---------- */
    var select = document.getElementById("regConstituency");
    if (select) {
        fetch("api/constituencies.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var groups = {};
                (d.constituencies || []).forEach(function (c) {
                    (groups[c.district] = groups[c.district] || []).push(c);
                });
                Object.keys(groups).sort().forEach(function (district) {
                    var g = document.createElement("optgroup");
                    g.label = district + " District";
                    groups[district].forEach(function (c) {
                        var o = document.createElement("option");
                        o.value = c.code;
                        o.textContent = c.name;
                        g.appendChild(o);
                    });
                    select.appendChild(g);
                });
            })
            .catch(function () {
                showAlert("register-alert", "Could not load the constituency list. Refresh the page.", "danger");
            });
    }

    /* ---------- Sign in ---------- */
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();
            var email = val("email").trim();
            busy(loginForm, true, "Signing in…");
            post("api/login.php", {
                role: "citizen",
                email: email,
                password: val("password"),
                remember: document.getElementById("remember").checked
            }).then(function (res) {
                if (!res.ok) {
                    busy(loginForm, false);
                    showAlert("login-alert", res.error, "danger");
                    return;
                }
                saveConstituency(email, res.constituency);
                window.location.href = "projects.php";
            });
        });
    }

    /* ---------- Create account ---------- */
    if (registerForm) {
        registerForm.addEventListener("submit", function (e) {
            e.preventDefault();
            if (!val("regConstituency")) {
                showAlert("register-alert", "Choose your constituency from the list.", "danger");
                return;
            }
            if (val("regPw") !== val("regPw2")) {
                showAlert("register-alert", "The two passwords do not match.", "danger");
                return;
            }
            var email = val("regEmail").trim();
            busy(registerForm, true, "Creating account…");
            post("api/register.php", {
                role: "citizen",
                full_name: val("regName").trim(),
                email: email,
                extra1: val("regConstituency"),
                extra2: "",
                password: val("regPw"),
                confirm: val("regPw2"),
                terms: document.getElementById("terms").checked
            }).then(function (res) {
                if (!res.ok) {
                    busy(registerForm, false);
                    showAlert("register-alert", res.error, "danger");
                    return;
                }
                saveConstituency(email, res.constituency);
                window.location.href = "projects.php";
            });
        });
    }
})();