(function () {
    "use strict";

    var KEY = "cdf_mp_projects";
    var COMMENTS = "cdf_mp_comments";

    var SEED = [
        { id: "vdc-101", title: "Makwasa Community Borehole", area: "Makwasa Village", sector: "Water", budget: 18500000, submitted: "12 Aug 2026", status: "review", statusLabel: "Under review", votes: 284, reason: "", proposer: "Makwasa VDC" },
        { id: "vdc-102", title: "Chisomo Primary Classroom Block", area: "Chisomo Village", sector: "Education", budget: 42000000, submitted: "29 Jul 2026", status: "accepted", statusLabel: "Accepted", votes: 231, reason: "", proposer: "Chisomo VDC" },
        { id: "vdc-103", title: "Mvera Feeder Road Repair", area: "Mvera Village", sector: "Roads", budget: 31800000, submitted: "16 Jun 2026", status: "denied", statusLabel: "Denied", votes: 98, reason: "The proposed route overlaps an active district road contract. Resubmit with a revised scope and route map.", proposer: "Mvera VDC" },
        { id: "vdc-104", title: "Kachere Health Centre Maternity Wing", area: "Kachere Village", sector: "Health", budget: 27500000, submitted: "08 May 2026", status: "progress", statusLabel: "Implementation", votes: 196, reason: "", proposer: "Kachere VDC" },
        { id: "vdc-105", title: "Nkhata Bay Market Sanitation", area: "Nkhata Bay", sector: "Markets", budget: 12400000, submitted: "21 Apr 2026", status: "completed", statusLabel: "Completed", votes: 177, reason: "", proposer: "Nkhata Bay VDC" }
    ];

    var ALLOC = {
        "vdc-102": { amount: 42000000, source: "2026 CDF allocation", ddo: "Grace Mbewe", reference: "DDO/CDF/2026/044", date: "03 Aug 2026", scope: "Two-classroom block, furniture, latrines" },
        "vdc-104": { amount: 27500000, source: "2026 CDF allocation", ddo: "Grace Mbewe", reference: "DDO/CDF/2026/031", date: "18 May 2026", scope: "Maternity wing, solar backup, water connection" },
        "vdc-105": { amount: 12400000, source: "2025 CDF allocation", ddo: "Grace Mbewe", reference: "DDO/CDF/2025/119", date: "06 May 2025", scope: "Toilets, handwashing stations, drainage" }
    };

    function get() {
        try {
            var a = JSON.parse(localStorage.getItem(KEY) || "null");
            if (Array.isArray(a)) {
                return a;
            }
        } catch (e) {}

        var a = SEED.map(function (p) {
            return Object.assign(
                {},
                p,
                ALLOC[p.id] ? { allocation: ALLOC[p.id] } : {},
                p.id === "vdc-104"
                    ? { progress: 62, lastUpdate: "22 Aug 2026", updateNote: "Walling complete; roofing materials delivered" }
                    : p.id === "vdc-105"
                        ? { progress: 100, lastUpdate: "14 Feb 2026", updateNote: "Final inspection completed and facility handed over" }
                        : { progress: 0 }
            );
        });

        save(a);
        return a;
    }

    function save(a) {
        localStorage.setItem(KEY, JSON.stringify(a));
    }

    function money(n) {
        return "MK " + (n / 1000000).toFixed(1) + "m";
    }

    function badge(p) {
        return '<span class="mp-status ' + p.status + '">' +
            '<i class="bi bi-circle-fill" style="font-size:.42rem"></i>' +
            p.statusLabel +
            '</span>';
    }

    function comments() {
        try {
            return JSON.parse(localStorage.getItem(COMMENTS) || "[]");
        } catch (e) {
            return [];
        }
    }

    function addComment(id, text) {
        var a = comments();

        a.unshift({
            id: id,
            text: text,
            date: new Date().toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }),
            author: "MP Office"
        });

        localStorage.setItem(COMMENTS, JSON.stringify(a));
    }

    function setDecision(id, decision, reason) {
        var a = get();

        a.forEach(function (p) {
            if (p.id === id) {
                p.status = decision;
                p.statusLabel = decision === "accepted" ? "Accepted" : "Denied";
                p.reason = reason || "";
                p.decisionDate = new Date().toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
            }
        });

        save(a);
    }

    function allocate(id, form) {
        var a = get();

        a.forEach(function (p) {
            if (p.id === id) {
                p.allocation = {
                    amount: Number(form.amount.value),
                    source: form.source.value,
                    ddo: form.ddo.value,
                    reference: form.reference.value,
                    date: form.date.value,
                    scope: form.scope.value
                };
            }
        });

        save(a);
    }

    function updateProgress(id, form) {
        var a = get();

        a.forEach(function (p) {
            if (p.id === id) {
                p.progress = Number(form.progress.value);
                p.lastUpdate = form.date.value;
                p.updateNote = form.note.value;
                p.status = p.progress >= 100 ? "completed" : "progress";
                p.statusLabel = p.progress >= 100 ? "Completed" : "Implementation";
            }
        });

        save(a);
    }

    function row(p) {
        return '<tr>' +
            '<td>' +
            '<strong>' + p.title + '</strong>' +
            '<div class="mp-kicker">' + p.area + ' · ' + p.sector + ' · ' + p.proposer + '</div>' +
            '</td>' +
            '<td>' + money(p.budget) + '</td>' +
            '<td>' + p.votes.toLocaleString() + '</td>' +
            '<td>' + badge(p) + '</td>' +
            '<td class="text-end"><a class="btn btn-outline-gov btn-sm" href="review.php?id=' + p.id + '">Review</a></td>' +
            '</tr>';
    }

    window.MP = {
        get: get,
        save: save,
        money: money,
        badge: badge,
        comments: comments,
        addComment: addComment,
        setDecision: setDecision,
        allocate: allocate,
        updateProgress: updateProgress,
        row: row
    };
})();
