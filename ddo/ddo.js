(function () {
    "use strict";

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
                    callback(null, d.projects || [], d.contractors || []);
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
                    // Also fetch active contractors list
                    fetch('../api/projects.php?contractors=1', { credentials: 'same-origin' })
                        .then(function (cr) { return cr.json(); })
                        .then(function (cd) {
                            callback(null, d.project, cd.contractors || [], d.assignment || null, d.progress_updates || []);
                        })
                        .catch(function () {
                            callback(null, d.project, [], d.assignment || null, d.progress_updates || []);
                        });
                } else {
                    callback(new Error(d.error || 'Failed to load project details.'));
                }
            })
            .catch(function (err) {
                callback(err);
            });
    }

    function assignContractor(projectId, contractorId, supervisor, scope, start, end, status, callback) {
        getCsrf(function (token) {
            fetch('../api/projects.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token
                },
                body: JSON.stringify({
                    action: 'assign',
                    project_id: projectId,
                    contractor_id: contractorId,
                    supervisor: supervisor,
                    scope: scope,
                    start_date: start,
                    target_date: end,
                    status: status || 'assigned'
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.ok) {
                    callback(null, d);
                } else {
                    callback(new Error(d.error || 'Failed to assign contractor.'));
                }
            })
            .catch(function (err) {
                callback(err);
            });
        });
    }

    function money(amount) {
        var num = Number(amount) || 0;
        if (num >= 1000000) {
            return "MK " + (num / 1000000).toFixed(1) + "m";
        }
        return "MK " + num.toLocaleString('en-GB');
    }

    function badge(project) {
        var status = project.assignment ? project.assignment.status : (project.status === 'completed' ? 'completed' : (project.status === 'progress' ? 'active' : 'planning'));
        var label = status === 'active' ? 'In progress' : (status === 'completed' ? 'Completed' : (status === 'blocked' ? 'Blocked' : 'Assignment pending'));
        if (project.assignment && project.assignment.status) {
            status = project.assignment.status;
            if (status === 'assigned') label = 'Contractor assigned';
            else if (status === 'active') label = 'In progress';
            else if (status === 'completed') label = 'Completed';
            else if (status === 'blocked') label = 'Blocked';
        } else if (project.status === 'completed') {
            status = 'completed';
            label = 'Completed';
        }
        return '<span class="ddo-status ' + esc(status) + '">' +
            '<i class="bi bi-circle-fill" style="font-size:.42rem"></i> ' +
            esc(label) +
            '</span>';
    }

    function priority(list) {
        return list.slice().sort(function (a, b) {
            return (Number(b.votes || 0) + Number(b.progress || 0) * 2) - (Number(a.votes || 0) + Number(a.progress || 0) * 2);
        });
    }

    function row(project) {
        return '<tr>' +
            '<td>' +
            '<strong>' + esc(project.title) + '</strong>' +
            '<div class="ddo-kicker">' + esc(project.area) + " · " + esc(project.sector) + (project.supplier ? ' · ' + esc(project.supplier) : '') + '</div>' +
            '</td>' +
            '<td>' + money(project.budget) + '</td>' +
            '<td>' + Number(project.votes || 0).toLocaleString() + '</td>' +
            '<td>' + badge(project) + '</td>' +
            '<td class="text-end"><a class="btn btn-outline-gov btn-sm" href="project.php?id=' + encodeURIComponent(project.id) + '">Manage</a></td>' +
            '</tr>';
    }

    window.DDO = {
        esc: esc,
        load: load,
        loadOne: loadOne,
        assignContractor: assignContractor,
        money: money,
        badge: badge,
        priority: priority,
        row: row
    };
})();
