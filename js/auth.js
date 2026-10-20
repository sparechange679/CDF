// Staff sign-in / registration page (auth.php).
// Keeps the role switcher and signs in / registers through the PHP API.
// The selected role is stored in two hidden fields (#loginRole and #regRole)
// and that hidden value is what gets sent to the server.
(function () {
    "use strict";

    // approval: true  -> the account waits for the District Development Officer.
    // This only controls the notice on the form. The server decides using
    // ROLES_REQUIRING_APPROVAL in config/database.php, so keep the two in step.
    let ROLES = {
        council: {
            title: "District Council",
            icon: "bi-diagram-3",
            text: "Propose development projects for your area and follow each one through review.",
            tasks: ["Submit project proposals", "Track proposals and decisions", "See citizen priority rankings"],
            dest: "District Council workspace",
            extra: ["Council or ward", "Position"],
            approval: true
        },
        mp: {
            title: "Member of Parliament",
            icon: "bi-bank",
            text: "Review proposals, weigh citizen votes and record decisions for your constituency.",
            tasks: ["Approve or reject proposals", "Verify fund allocations", "Monitor delivery progress"],
            dest: "MP workspace",
            extra: ["Constituency", "Parliamentary office"],
            approval: false
        },
        ddo: {
            title: "District Development Officer",
            icon: "bi-clipboard2-pulse",
            text: "Turn approved projects into delivered results with funds, suppliers and timelines.",
            tasks: ["Allocate and track funds", "Assign suppliers and supervisors", "Publish progress reports"],
            dest: "DDO workspace",
            extra: ["District", "Employee or staff number"],
            approval: false
        },
        contractor: {
            title: "Contractor",
            icon: "bi-tools",
            text: "Deliver assigned projects and keep the district office updated on site progress.",
            tasks: ["View assigned projects", "Upload milestones and photos", "Submit completion reports"],
            dest: "Contractor workspace",
            extra: ["Company name", "Business registration number"],
            approval: true
        }
    };

    let card = document.getElementById("authCard");
    let csrf = null;

    /* ---------- Role switcher ---------- */
    function setRole(key) {
        let r = ROLES[key];
        if (!r) return;

        // The hidden fields are the single source of truth for the role.
        document.getElementById("loginRole").value = key;
        document.getElementById("regRole").value = key;

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

        // Only roles that need approval see the notice.
        let note = document.getElementById("approvalNote");
        if (r.approval) {
            note.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> ' +
                "A " + r.title + " account must be approved by the District Development Officer " +
                "before you can sign in.";
            note.classList.remove("d-none");
        } else {
            note.textContent = "";
            note.classList.add("d-none");
        }

        // MPs pick their constituency from the database list.
        let extra1 = document.getElementById("extra1");
        if (key === "mp") {
            extra1.setAttribute("list", "constituencyList");
            extra1.setAttribute("placeholder", "Start typing to search");
        } else {
            extra1.removeAttribute("list");
            extra1.removeAttribute("placeholder");
        }
        clearAlerts();
    }

    document.querySelectorAll('input[name="role"][type="radio"]').forEach(function (input) {
        input.addEventListener("change", function () { setRole(this.value); });
    });

    fetch("api/constituencies.php", { credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            let list = document.createElement("datalist");
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
        let box = document.createElement("div");
        box.className = "js-alert alert alert-" + type + " alert-gov py-2";
        box.setAttribute("role", "alert");
        box.textContent = message;
        form.insertBefore(box, form.firstChild);
    }

    function busy(form, on, label) {
        let btn = form.querySelector('button[type="submit"]');
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
    let loginForm = document.getElementById("loginForm");
    loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        busy(loginForm, true, "Signing in…");
        post("api/login.php", {
            role: val("loginRole"),               // hidden field
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
    let registerForm = document.getElementById("registerForm");
    registerForm.addEventListener("submit", function (e) {
        e.preventDefault();
        if (val("regPw") !== val("regPw2")) {
            showAlert(registerForm, "The two passwords do not match.", "danger");
            return;
        }
        busy(registerForm, true, "Creating account…");
        post("api/register.php", {
            role: val("regRole"),                 // hidden field
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
                // MP and DDO accounts are active at once: go straight to the workspace.
                window.location.href = res.redirect;
                return;
            }
            // Council and contractor accounts wait for approval: send them to the sign-in tab.
            registerForm.reset();
            setRole(val("regRole"));
            bootstrap.Tab.getOrCreateInstance(document.getElementById("login-tab")).show();
            showAlert(loginForm, res.message, "success");
        });
    });

    /* ---------- Small UI helpers (unchanged) ---------- */
    document.querySelectorAll(".pw-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
            let input = document.getElementById(this.dataset.target);
            let show = input.type === "password";
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