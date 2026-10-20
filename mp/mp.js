(function () {
    "use strict";

    var cachedData = null;
    var csrfToken = null;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function getCsrf(callback) {
        if (csrfToken) {
            callback(csrfToken);
            return;
        }
        fetch('../api/csrf.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.ok && d.token) {
                    csrfToken = d.token;
                    callback(csrfToken);
                } else {
                    callback('');
                }
            })
            .catch(function () {
                callback('');
            });
    }

    function load(callback) {
        fetch('../api/projects.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) {
                    cachedData = d;
                    callback(null, d.projects || [], d.constituency || null);
                } else {
                    callback(new Error(d.error || 'Failed to load projects.'));
                }
            })
            .catch(function (err) {
                callback(err);
            });
    }

    function loadOne(id, callback) {
        fetch('../api/projects.php?id=' + encodeURIComponent(id), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) {
                    callback(null, d.project, d.comments || []);
                } else {
                    callback(new Error(d.error || 'Failed to load project details.'));
                }
            })
            .catch(function (err) {
                callback(err);
            });
    }

    function postDecision(projectId, decision, reason, comment, callback) {
        getCsrf(function (token) {
            fetch('../api/projects.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token
                },
                body: JSON.stringify({
                    action: 'decision',
                    project_id: projectId,
                    status: decision,
                    reason: reason,
                    comment: comment || ''
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) {
                    callback(null, d);
                } else {
                    callback(new Error(d.error || 'Failed to save decision.'));
                }
            })
            .catch(function (err) {
                callback(err);
            });
        });
    }

    function postComment(projectId, text, callback) {
        getCsrf(function (token) {
            fetch('../api/projects.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token
                },
                body: JSON.stringify({
                    action: 'comment',
                    project_id: projectId,
                    text: text
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) {
                    callback(null, d);
                } else {
                    callback(new Error(d.error || 'Failed to post comment.'));
                }
            })
            .catch(function (err) {
                callback(err);
            });
        });
    }

    function money(n) {
        var num = Number(n) || 0;
        if (num >= 1000000) {
            return "MK " + (num / 1000000).toFixed(1) + "m";
        }
        return "MK " + num.toLocaleString('en-GB');
    }

    function badge(p) {
        var st = p.status || 'submitted';
        var label = p.statusLabel || (st.charAt(0).toUpperCase() + st.slice(1));
        return '<span class="mp-status ' + esc(st) + '">' +
            '<i class="bi bi-circle-fill" style="font-size:.42rem"></i>' +
            esc(label) +
            '</span>';
    }

    function row(p) {
        return '<tr>' +
            '<td>' +
            '<strong>' + esc(p.title) + '</strong>' +
            '<div class="mp-kicker">' + esc(p.reference ? p.reference + ' · ' : '') + esc(p.area) + ' · ' + esc(p.sector) + ' · ' + esc(p.proposer || 'Council') + '</div>' +
            '</td>' +
            '<td>' + money(p.budget) + '</td>' +
            '<td>' + Number(p.votes || 0).toLocaleString() + '</td>' +
            '<td>' + badge(p) + '</td>' +
            '<td class="text-end"><a class="btn btn-outline-gov btn-sm" href="review.php?id=' + encodeURIComponent(p.id) + '">Review</a></td>' +
            '</tr>';
    }

    window.MP = Object.assign(window.MP || {}, {
        load: load,
        loadOne: loadOne,
        postDecision: postDecision,
        postComment: postComment,
        money: money,
        badge: badge,
        row: row,
        esc: esc
    });
})();
