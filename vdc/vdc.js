(function () {
    "use strict";

    var KEY = "cdf_vdc_projects";

    var SEED = [
        { id: "vdc-101", title: "Makwasa Community Borehole", area: "Makwasa Village", sector: "Water", budget: 18500000, submitted: "12 Aug 2026", status: "review", statusLabel: "Under review", votes: 284, reason: "", proposer: "Makwasa VDC" },
        { id: "vdc-102", title: "Chisomo Primary Classroom Block", area: "Chisomo Village", sector: "Education", budget: 42000000, submitted: "29 Jul 2026", status: "accepted", statusLabel: "Accepted", votes: 231, reason: "", proposer: "Chisomo VDC" },
        { id: "vdc-103", title: "Mvera Feeder Road Repair", area: "Mvera Village", sector: "Roads", budget: 31800000, submitted: "16 Jun 2026", status: "denied", statusLabel: "Denied", votes: 98, reason: "The proposed route overlaps an active district road contract. Resubmit with a revised scope and route map.", proposer: "Mvera VDC" },
        { id: "vdc-104", title: "Kachere Health Centre Maternity Wing", area: "Kachere Village", sector: "Health", budget: 27500000, submitted: "08 May 2026", status: "progress", statusLabel: "Implementation", votes: 196, reason: "", proposer: "Kachere VDC" },
        { id: "vdc-105", title: "Nkhata Bay Market Sanitation", area: "Nkhata Bay", sector: "Markets", budget: 12400000, submitted: "21 Apr 2026", status: "completed", statusLabel: "Completed", votes: 177, reason: "", proposer: "Nkhata Bay VDC" }
    ];

    function get() {
        try {
            var saved = JSON.parse(localStorage.getItem(KEY) || "null");
            if (Array.isArray(saved)) {
                return saved;
            }
        } catch (e) {}

        localStorage.setItem(KEY, JSON.stringify(SEED));
        return SEED.slice();
    }

    function save(list) {
        localStorage.setItem(KEY, JSON.stringify(list));
    }

    function money(n) {
        return "MK " + (n / 1000000).toFixed(1) + "m";
    }

    function statusClass(status) {
        return status === "review" ? "review" : status;
    }

    function statusBadge(p) {
        return '<span class="vdc-status ' + statusClass(p.status) + '">' +
            '<i class="bi bi-circle-fill" style="font-size:.42rem"></i>' +
            p.statusLabel +
            '</span>';
    }

    function row(p, includeReason) {
        return '<tr>' +
            '<td>' +
            '<strong>' + p.title + '</strong>' +
            '<div class="vdc-kicker">' + p.area + ' · ' + p.sector + '</div>' +
            '</td>' +
            '<td>' + money(p.budget) + '</td>' +
            '<td>' + p.submitted + '</td>' +
            '<td>' +
            statusBadge(p) +
            (includeReason && p.reason
                ? '<div class="small text-danger mt-2"><i class="bi bi-info-circle me-1"></i>' + p.reason + '</div>'
                : '') +
            '</td>' +
            '<td class="text-end"><span class="fw-semibold">' + p.votes.toLocaleString() + '</span></td>' +
            '</tr>';
    }

    function add(form) {
        var list = get();

        list.unshift({
            id: "vdc-" + Date.now(),
            title: form.title.value.trim(),
            area: form.area.value.trim(),
            sector: form.sector.value,
            budget: Number(form.budget.value),
            submitted: new Date().toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }),
            status: "submitted",
            statusLabel: "Submitted",
            votes: 0,
            reason: "",
            proposer: "My Village Development Committee"
        });

        save(list);
    }

    window.VDC = {
        get: get,
        save: save,
        money: money,
        statusBadge: statusBadge,
        row: row,
        add: add
    };
})();
