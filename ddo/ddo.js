(function () {
    "use strict";

    var KEY = "cdf_ddo_projects";
    var MPKEY = "cdf_mp_projects";

    var BASE = [
        { id: "vdc-102", title: "Chisomo Primary Classroom Block", area: "Chisomo Village", sector: "Education", budget: 42000000, submitted: "29 Jul 2026", status: "accepted", statusLabel: "Accepted", votes: 231, proposer: "Chisomo VDC" },
        { id: "vdc-104", title: "Kachere Health Centre Maternity Wing", area: "Kachere Village", sector: "Health", budget: 27500000, submitted: "08 May 2026", status: "progress", statusLabel: "Implementation", votes: 196, proposer: "Kachere VDC" },
        { id: "vdc-105", title: "Nkhata Bay Market Sanitation", area: "Nkhata Bay", sector: "Markets", budget: 12400000, submitted: "21 Apr 2026", status: "completed", statusLabel: "Completed", votes: 177, proposer: "Nkhata Bay VDC" }
    ];

    var SEED = {
        "vdc-102": { supplier: "Mwezi Construction Ltd", supervisor: "Eng. Patrick Banda", start: "2026-08-15", end: "2026-11-30", status: "active", statusLabel: "In progress", progress: 18, report: "Foundation excavation and material delivery started.", result: "Two classrooms and a furnished learning space for Chisomo Primary School." },
        "vdc-104": { supplier: "Build Malawi Contractors", supervisor: "Eng. Alice Phiri", start: "2026-06-01", end: "2027-02-28", status: "active", statusLabel: "In progress", progress: 62, report: "Walling complete; roofing materials delivered.", result: "A safe maternity wing with solar backup and a water connection." },
        "vdc-105": { supplier: "CleanWorks Malawi", supervisor: "Mr. Joseph Tembo", start: "2025-05-12", end: "2026-02-14", status: "completed", statusLabel: "Completed", progress: 100, report: "Final inspection completed and facility handed over.", result: "Improved sanitation and drainage for market traders and visitors." }
    };

    var ALLOCATION = {
        "vdc-102": { amount: 42000000, source: "2026 CDF allocation", ddo: "Grace Mbewe", reference: "DDO/CDF/2026/044", date: "03 Aug 2026", scope: "Two-classroom block, furniture, latrines" },
        "vdc-104": { amount: 27500000, source: "2026 CDF allocation", ddo: "Grace Mbewe", reference: "DDO/CDF/2026/031", date: "18 May 2026", scope: "Maternity wing, solar backup, water connection" },
        "vdc-105": { amount: 12400000, source: "2025 CDF allocation", ddo: "Grace Mbewe", reference: "DDO/CDF/2025/119", date: "06 May 2025", scope: "Toilets, handwashing stations, drainage" }
    };

    function mpProjects() {
        try {
            var saved = JSON.parse(localStorage.getItem(MPKEY) || "null");
            if (Array.isArray(saved) && saved.length) {
                return saved;
            }
        } catch (e) {}

        return BASE.slice();
    }

    function get() {
        var projects = mpProjects().filter(function (p) {
            return ["accepted", "progress", "completed"].indexOf(p.status) > -1;
        });

        var saved = {};
        try {
            saved = JSON.parse(localStorage.getItem(KEY) || "{}") || {};
        } catch (e) {}

        projects.forEach(function (p) {
            if (!saved[p.id]) {
                saved[p.id] = Object.assign(
                    {},
                    SEED[p.id] || { supplier: "", supervisor: "", start: "", end: "", status: "planning", statusLabel: "Assignment pending", progress: 0, report: "", result: "" },
                    ALLOCATION[p.id] ? { allocation: ALLOCATION[p.id] } : {}
                );
            }
        });

        localStorage.setItem(KEY, JSON.stringify(saved));

        return projects.map(function (p) {
            return Object.assign(
                {},
                p,
                saved[p.id],
                { allocation: p.allocation || (saved[p.id] && saved[p.id].allocation) || null }
            );
        });
    }

    function save(id, form) {
        var saved = {};
        try {
            saved = JSON.parse(localStorage.getItem(KEY) || "{}") || {};
        } catch (e) {}

        saved[id] = {
            supplier: form.supplier.value.trim(),
            supervisor: form.supervisor.value.trim(),
            start: form.start.value,
            end: form.end.value,
            status: form.status.value,
            statusLabel: form.status.options[form.status.selectedIndex].text,
            progress: Number(form.progress.value),
            report: form.report.value.trim(),
            result: form.result.value.trim()
        };

        localStorage.setItem(KEY, JSON.stringify(saved));
    }

    function money(amount) {
        return "MK " + (amount / 1000000).toFixed(1) + "m";
    }

    function badge(project) {
        return '<span class="ddo-status ' + project.status + '">' +
            '<i class="bi bi-circle-fill" style="font-size:.42rem"></i>' +
            project.statusLabel +
            '</span>';
    }

    function priority(list) {
        return list.slice().sort(function (a, b) {
            return (b.votes + (b.progress || 0) * 2) - (a.votes + (a.progress || 0) * 2);
        });
    }

    function row(project) {
        return '<tr>' +
            '<td>' +
            '<strong>' + project.title + '</strong>' +
            '<div class="ddo-kicker">' + project.area + " · " + project.sector + '</div>' +
            '</td>' +
            '<td>' + money(project.budget) + '</td>' +
            '<td>' + project.votes.toLocaleString() + '</td>' +
            '<td>' + badge(project) + '</td>' +
            '<td class="text-end"><a class="btn btn-outline-gov btn-sm" href="project.php?id=' + project.id + '">Manage</a></td>' +
            '</tr>';
    }

    window.DDO = {
        get: get,
        save: save,
        money: money,
        badge: badge,
        priority: priority,
        row: row
    };
})();
