// Replaces the inline <script> at the bottom of auth.php.
// Keeps the role switcher and now signs in / registers through the PHP API.
(function () {
    "use strict";

    var ROLES = {
        council: {
            title: "District Council",
            icon: "bi-diagram-3",
            text: "Propose development projects for your area and follow each one through review.",
            tasks: ["Submit project proposals", "Track proposals and decisions", "See citizen priority rankings"],
            dest: "District Council workspace",
            extra: ["Council or ward", "Position"]
        },
        mp: {
            title: "Member of Parliament",
            icon: "bi-bank",
            text: "Review proposals, weigh citizen votes and record decisions for your constituency.",
            tasks: ["Approve or reject proposals", "Verify fund allocations", "Monitor delivery progress"],
            dest: "MP workspace",
            extra: ["Constituency", "Parliamentary office"]
        },
        ddo: {
            title: "District Development Officer",
            icon: "bi-clipboard2-pulse",
            text: "Turn approved projects into delivered results with funds, suppliers and timelines.",
            tasks: ["Allocate and track funds", "Assign suppliers and supervisors", "Publish progress reports"],
            dest: "DDO workspace",
            extra: ["District", "Employee or staff number"]
        },
        contractor: {
            title: "Contractor",
            icon: "bi-tools",
            text: "Deliver assigned projects and keep the district office updated on site progress.",
            tasks: ["View assigned projects", "Upload milestones and photos", "Submit completion reports"],
            dest: "Contractor workspace",
            extra: ["Company name", "Business registration number"]
        }
    };

    var card = document.getElementById("authCard");
    var current = "council";
    var csrf = null;

    /* ---------- Role switcher ---------- */
    function setRole(key) {
        var r = ROLES[key];
        current = key;
        card.setAttribute("data-role", key);
        document.getElementById("asideTitle").textContent = r.title;
        document.getElementById("asideIcon").className = "bi " + r.icon;
        document.getElementById("asideText").textContent = r.text;
        document.getElementById("asideDest").textContent = r.dest;
        document.getElementById("asideTasks").innerHTML = r.tasks.map(function (t) {
            return '<li><i class="bi bi-check2-circle"></i><span>' + t + "</span></li>";
        }).join("");
        document.getElementById("extra1Label").textContent = r.extra[0];
        document.getElementById("extra2Label").textContent = r.extra[1];

        // MPs pick their constituency from the database list.
        var extra1 = document.getElementById("extra1");
        if (key === "mp") {
            extra1.setAttribute("list", "constituencyList");
            extra1.setAttribute("placeholder", "Start typing to search");
        } else {
            extra1.removeAttribute("list");
            extra1.removeAttribute("placeholder");
        }
        clearAlerts();
    }

    document.querySelectorAll('input[name="role"]').forEach(function (input) {
        input.addEventListener("change", function () { setRole(this.value); });
    });

    fetch("api/constituencies.php", { credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            var list = document.createElement("datalist");
            list.id = "constituencyList";
            list.innerHTML = (d.constituencies || []).map(function (c) {
                return '<option value="' + c.name + '">';
            }).join("");
            document.body.appendChild(list);
        })
        .catch(function () { /* the field still accepts typing */ });

    /* ---------- API helpers ---------- */
    function getCsrf() {
        if (csrf) return Promise.resolve(csrf);
        return fetch("api/csrf.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (d) { csrf = d.token; return csrf; });
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

    /* ---------- Alerts and button state ---------- */
    function clearAlerts() {
        document.querySelectorAll(".js-alert").forEach(function (a) { a.remove(); });
    }

    function showAlert(form, message, type) {
        clearAlerts();
        var box = document.createElement("div");
        box.className = "js-alert alert alert-" + type + " alert-gov py-2";
        box.setAttribute("role", "alert");
        box.textContent = message;
        form.insertBefore(box, form.firstChild);
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

    /* ---------- Sign in ---------- */
    var loginForm = document.getElementById("loginForm");
    loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        busy(loginForm, true, "Signing in…");
        post("api/login.php", {
            role: current,
            email: val("loginId").trim(),
            password: val("loginPw"),
            remember: document.getElementById("remember").checked
        }).then(function (res) {
            if (res.ok) {
                window.location.href = res.redirect;
                return;
            }
            busy(loginForm, false);
            showAlert(loginForm, res.error, "danger");
        });
    });

    /* ---------- Create account ---------- */
    var registerForm = document.getElementById("registerForm");
    registerForm.addEventListener("submit", function (e) {
        e.preventDefault();
        if (val("regPw") !== val("regPw2")) {
            showAlert(registerForm, "The two passwords do not match.", "danger");
            return;
        }
        busy(registerForm, true, "Creating account…");
        post("api/register.php", {
            role: current,
            full_name: val("regName").trim(),
            email: val("regEmail").trim(),
            extra1: val("extra1").trim(),
            extra2: val("extra2").trim(),
            password: val("regPw"),
            confirm: val("regPw2"),
            terms: document.getElementById("terms").checked
        }).then(function (res) {
            busy(registerForm, false);
            if (!res.ok) {
                showAlert(registerForm, res.error, "danger");
                return;
            }
            if (!res.pending) {
                window.location.href = res.redirect;
                return;
            }
            // Staff accounts wait for approval: send them to the sign-in tab.
            registerForm.reset();
            bootstrap.Tab.getOrCreateInstance(document.getElementById("login-tab")).show();
            showAlert(loginForm, res.message, "success");
        });
    });

    /* ---------- Small UI helpers (unchanged) ---------- */
    document.querySelectorAll(".pw-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var input = document.getElementById(this.dataset.target);
            var show = input.type === "password";
            input.type = show ? "text" : "password";
            this.innerHTML = '<i class="bi ' + (show ? "bi-eye-slash" : "bi-eye") + '"></i>';
            this.setAttribute("aria-label", show ? "Hide password" : "Show password");
        });
    });

    function openTab(id) { bootstrap.Tab.getOrCreateInstance(document.getElementById(id)).show(); }
    document.querySelector(".js-to-register").addEventListener("click", function (e) { e.preventDefault(); openTab("register-tab"); });
    document.querySelector(".js-to-login").addEventListener("click", function (e) { e.preventDefault(); openTab("login-tab"); });

    setRole("council");
})();
