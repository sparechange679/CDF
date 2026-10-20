(function () {
    "use strict";

    const PROJECTS = [
        {
            id: "p1",
            title: "Borehole Water Project",
            constituencyId: "c-ll-south",
            constituency: "Lilongwe South Constituency",
            budget: "MK 18.5 Million",
            status: "approved",
            statusLabel: "Approved",
            progress: 72,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 412,
            category: "Water",
            image: "https://images.unsplash.com/photo-1581092919534-89d2a0c94f36?auto=format&fit=crop&w=800&q=80",
            description: "Drilling and installation of a community borehole with solar pump and storage tank.",
            updates: [
                {date: "12 Mar 2026", text: "Site survey completed and approved."},
                {date: "04 May 2026", text: "Drilling finished; pump installation underway."},
                {date: "18 Jul 2026", text: "Storage tank delivered to site."}
            ]
        },
        {
            id: "p2",
            title: "Primary School Renovation",
            constituencyId: "c-bt-west",
            constituency: "Blantyre City West",
            budget: "MK 42.0 Million",
            status: "proposed",
            statusLabel: "Proposed",
            progress: 28,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 287,
            category: "Education",
            image: "https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80",
            description: "Roof replacement, classroom plastering and furniture for a government primary school.",
            updates: [
                {date: "02 Feb 2026", text: "Proposal submitted by constituency office."},
                {date: "20 Apr 2026", text: "Awaiting citizen vote ranking and committee approval."}
            ]
        },
        {
            id: "p3",
            title: "Rural Access Road",
            constituencyId: "c-mz-central",
            constituency: "Mzuzu Central",
            budget: "MK 65.8 Million",
            status: "progress",
            statusLabel: "In Progress",
            progress: 54,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 356,
            category: "Roads",
            image: "https://images.unsplash.com/photo-1509099836639-18ba1795216d?auto=format&fit=crop&w=800&q=80",
            description: "Grading and culvert works on a 6.4 km rural feeder road.",
            updates: [
                {date: "11 Jan 2026", text: "Contractor mobilized."},
                {date: "09 Jun 2026", text: "3.4 km graded; two culverts cast."}
            ]
        },
        {
            id: "p4",
            title: "Health Post Construction",
            constituencyId: "c-zb-central",
            constituency: "Zomba Central",
            budget: "MK 31.2 Million",
            status: "progress",
            statusLabel: "In Progress",
            progress: 41,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 198,
            category: "Health",
            image: "https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=800&q=80",
            description: "New community health post with consultation rooms and a waiting shelter.",
            updates: [
                {date: "15 Mar 2026", text: "Foundation complete."},
                {date: "22 Jul 2026", text: "Walling at window level."}
            ]
        },
        {
            id: "p5",
            title: "Market Shelter Upgrade",
            constituencyId: "c-ll-centre",
            constituency: "Lilongwe City Centre",
            budget: "MK 22.4 Million",
            status: "completed",
            statusLabel: "Completed",
            progress: 100,
            complete: true,
            uploaded: true,
            votable: false,
            seedVotes: 521,
            category: "Markets",
            image: "https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=800&q=80",
            description: "Covered stalls, drainage and sanitation for the main trading centre.",
            updates: [
                {date: "08 Aug 2025", text: "Works commenced."},
                {date: "30 Jan 2026", text: "Handover to market committee completed."}
            ]
        },
        {
            id: "p6",
            title: "Smallholder Irrigation Scheme",
            constituencyId: "c-sa-north",
            constituency: "Salima North",
            budget: "MK 48.6 Million",
            status: "approved",
            statusLabel: "Approved",
            progress: 15,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 164,
            category: "Agriculture",
            image: "https://images.unsplash.com/photo-1625246333195-78d9c38ad449?auto=format&fit=crop&w=800&q=80",
            description: "Canal lining and intake works serving 80 farming households.",
            updates: [
                {date: "19 Jun 2026", text: "Design approved by district office."}
            ]
        },
        {
            id: "p7",
            title: "Community Hall",
            constituencyId: "c-ka-town",
            constituency: "Karonga Town",
            budget: "MK 27.0 Million",
            status: "progress",
            statusLabel: "In Progress",
            progress: 63,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 231,
            category: "Community",
            image: "https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80",
            description: "Multi-purpose hall for civic meetings, youth activities and public hearings.",
            updates: [
                {date: "03 Apr 2026", text: "Roofing started."},
                {date: "12 Aug 2026", text: "Electrical first fix in progress."}
            ]
        },
        {
            id: "p8",
            title: "Solar Street Lights",
            constituencyId: "c-bt-east",
            constituency: "Blantyre City East",
            budget: "MK 14.9 Million",
            status: "completed",
            statusLabel: "Completed",
            progress: 100,
            complete: true,
            uploaded: true,
            votable: false,
            seedVotes: 445,
            category: "Energy",
            image: "https://images.unsplash.com/photo-1518002171953-a080ee817e1f?auto=format&fit=crop&w=800&q=80",
            description: "Forty solar street lights along the main trading corridor.",
            updates: [
                {date: "14 Nov 2025", text: "Poles installed."},
                {date: "09 Feb 2026", text: "Commissioned and handed over."}
            ]
        },
        {
            id: "p9",
            title: "Classroom Block",
            constituencyId: "c-dz-south",
            constituency: "Dedza South",
            budget: "MK 36.5 Million",
            status: "approved",
            statusLabel: "Approved",
            progress: 8,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 119,
            category: "Education",
            image: "https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80",
            description: "Two-classroom block with latrines at a rural primary school.",
            updates: [
                {date: "01 Aug 2026", text: "Contractor awarded after approval."}
            ]
        },
        {
            id: "p10",
            title: "Youth Skills Centre",
            constituencyId: "c-mz-central",
            constituency: "Mzuzu Central",
            budget: "MK 19.8 Million",
            status: "proposed",
            statusLabel: "Proposed",
            progress: 0,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 96,
            category: "Youth",
            image: "https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80",
            description: "Workshop space for vocational training in welding, tailoring and ICT.",
            updates: [
                {date: "10 Aug 2026", text: "Proposal uploaded; open for citizen votes."}
            ]
        },
        {
            id: "p11",
            title: "Village Clinic Upgrade",
            constituencyId: "c-ll-south",
            constituency: "Lilongwe South Constituency",
            budget: "MK 12.7 Million",
            status: "progress",
            statusLabel: "In Progress",
            progress: 38,
            complete: false,
            uploaded: true,
            votable: true,
            seedVotes: 203,
            category: "Health",
            image: "https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=800&q=80",
            description: "Extension of a village clinic with a maternity waiting room and solar power.",
            updates: [
                {date: "21 May 2026", text: "Materials delivered to the health centre."}
            ]
        }
    ];

    const SEED_CONSTITUENCIES = [
        {id: "c-ll-south", name: "Lilongwe South Constituency", district: "Lilongwe"},
        {id: "c-ll-centre", name: "Lilongwe City Centre", district: "Lilongwe"},
        {id: "c-bt-west", name: "Blantyre City West", district: "Blantyre"},
        {id: "c-bt-east", name: "Blantyre City East", district: "Blantyre"},
        {id: "c-mz-central", name: "Mzuzu Central", district: "Mzimba"},
        {id: "c-zb-central", name: "Zomba Central", district: "Zomba"},
        {id: "c-sa-north", name: "Salima North", district: "Salima"},
        {id: "c-ka-town", name: "Karonga Town", district: "Karonga"},
        {id: "c-dz-south", name: "Dedza South", district: "Dedza"},
        {id: "c-mg-central", name: "Mangochi Central", district: "Mangochi"}
    ];

    const VOTES_KEY = "cdf_malawi_votes";
    const VOTED_KEY = "cdf_malawi_voted";
    const FEEDBACK_KEY = "cdf_malawi_feedback";
    const SESSION_KEY = "cdf_malawi_session";
    const CONSTITUENCIES_KEY = "cdf_malawi_constituencies";

    function getConstituencies() {
        try {
            const saved = localStorage.getItem(CONSTITUENCIES_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length) return parsed;
            }
        } catch (e) { /* ignore */ }
        localStorage.setItem(CONSTITUENCIES_KEY, JSON.stringify(SEED_CONSTITUENCIES));
        return SEED_CONSTITUENCIES.slice();
    }

    function findConstituency(id) {
        return getConstituencies().filter(function (c) { return c.id === id; })[0];
    }

    function getSession() {
        try {
            return JSON.parse(localStorage.getItem(SESSION_KEY) || "null");
        } catch (e) {
            return null;
        }
    }

    function saveSession(session) {
        localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    }

    function clearSession() {
        localStorage.removeItem(SESSION_KEY);
    }

    // CHANGED: match on the constituency id (code) OR the constituency name,
    // so the filter still works if the database codes differ from the ids above.
    function filterForCitizen(list) {
        let session = getSession();
        if (!session || (!session.constituencyId && !session.constituencyName)) return list;
        return list.filter(function (p) {
            return p.constituencyId === session.constituencyId ||
                p.constituency === session.constituencyName;
        });
    }

    function statusClass(status) {
        if (status === "approved") return "status-approved";
        if (status === "proposed") return "status-proposed";
        if (status === "progress") return "status-progress";
        return "status-completed";
    }

    function progressBarClass(progress, complete) {
        if (complete || progress >= 100) return "bg-success";
        if (progress >= 50) return "bg-primary";
        if (progress >= 25) return "bg-warning";
        return "bg-danger";
    }

    function getVotes() {
        try {
            let saved = localStorage.getItem(VOTES_KEY);
            if (saved) return JSON.parse(saved);
        } catch (e) { /* ignore */ }
        let seed = {};
        PROJECTS.forEach(function (p) {
            seed[p.id] = p.seedVotes;
        });
        localStorage.setItem(VOTES_KEY, JSON.stringify(seed));
        return seed;
    }

    function saveVotes(votes) {
        localStorage.setItem(VOTES_KEY, JSON.stringify(votes));
    }

    function getVotedMap() {
        try {
            return JSON.parse(localStorage.getItem(VOTED_KEY) || "{}");
        } catch (e) {
            return {};
        }
    }

    function hasVoted(id) {
        return !!getVotedMap()[id];
    }

    function castVote(id) {
        if (hasVoted(id)) {
            return { ok: false, message: "You have already voted for this project on this device." };
        }
        let votes = getVotes();
        votes[id] = (votes[id] || 0) + 1;
        saveVotes(votes);
        let voted = getVotedMap();
        voted[id] = true;
        localStorage.setItem(VOTED_KEY, JSON.stringify(voted));
        return { ok: true, count: votes[id] };
    }

    function totalVotes() {
        let votes = getVotes();
        return Object.keys(votes).reduce(function (sum, key) {
            return sum + (votes[key] || 0);
        }, 0);
    }

    function getFeedback() {
        try {
            return JSON.parse(localStorage.getItem(FEEDBACK_KEY) || "[]");
        } catch (e) {
            return [];
        }
    }

    function addFeedback(entry) {
        let list = getFeedback();
        list.unshift(entry);
        localStorage.setItem(FEEDBACK_KEY, JSON.stringify(list));
    }

    function findProject(id) {
        return PROJECTS.filter(function (p) { return p.id === id; })[0];
    }

    function approvedProjects() {
        return filterForCitizen(PROJECTS.filter(function (p) {
            return p.uploaded && p.status !== "proposed";
        }));
    }

    function votableProjects() {
        return filterForCitizen(PROJECTS.filter(function (p) { return p.votable; }));
    }

    function citizenProjects() {
        return filterForCitizen(PROJECTS.slice());
    }

    function votesForProjects(list) {
        const votes = getVotes();
        return list.reduce(function (sum, p) { return sum + (votes[p.id] || 0); }, 0);
    }

    function fillConstituencySelect(selectEl, selectedId) {
        if (!selectEl) return;
        const groups = {};
        getConstituencies().forEach(function (c) {
            if (!groups[c.district]) groups[c.district] = [];
            groups[c.district].push(c);
        });
        selectEl.innerHTML = '<option value="" disabled selected>Select your constituency</option>';
        Object.keys(groups).sort().forEach(function (district) {
            let group = document.createElement("optgroup");
            group.label = district + " District";
            groups[district].forEach(function (c) {
                let opt = document.createElement("option");
                opt.value = c.id;
                opt.textContent = c.name;
                group.appendChild(opt);
            });
            selectEl.appendChild(group);
        });
        if (selectedId) selectEl.value = selectedId;
    }

    function updateNavLogin() {
        let session = getSession();
        document.querySelectorAll(".btn-login").forEach(function (link) {
            if (session && session.constituencyName) {
                link.innerHTML = '<i class="bi bi-person-circle me-1"></i> ' + session.constituencyName;
            } else {
                link.innerHTML = '<i class="bi bi-person-circle me-1"></i> Login';
            }
        });
    }

    function insertConstituencyBanner() {
        if (document.getElementById("constituency-banner")) return;
        if (document.getElementById("login-form") || document.getElementById("logged-in")) return;
        let nav = document.querySelector("nav.navbar");
        if (!nav) return;
        let session = getSession();
        let banner = document.createElement("div");
        banner.id = "constituency-banner";
        banner.className = session ? "constituency-banner is-local" : "constituency-banner is-national";
        // CHANGED: links now go to login.php (was ../api/login.php)
        banner.innerHTML = session
            ? '<div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">' +
            '<span><i class="bi bi-geo-alt-fill me-1"></i> Viewing developments in <strong>' + session.constituencyName + "</strong></span>" +
            '<a href="login.php">Change constituency</a>' +
            "</div>"
            : '<div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">' +
            '<span><i class="bi bi-info-circle me-1"></i> Select your constituency at login to view local CDF developments.</span>' +
            '<a href="login.php">Choose constituency</a>' +
            "</div>";
        nav.insertAdjacentElement("afterend", banner);
    }

    function renderProjectCard(p, options) {
        options = options || {};
        let votes = getVotes();
        let count = votes[p.id] || 0;
        let voted = hasVoted(p.id);
        let voteBtn = "";
        if (options.showVote) {
            if (p.votable) {
                voteBtn = voted
                    ? '<button class="btn btn-cdf btn-sm flex-fill" disabled><i class="bi bi-check2 me-1"></i> Voted</button>'
                    : '<button class="btn btn-cdf btn-sm flex-fill js-vote" data-id="' + p.id + '"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</button>';
            } else {
                voteBtn = '<button class="btn btn-cdf btn-sm flex-fill" disabled>Voting closed</button>';
            }
        }
        let detailsHref = options.detailsHref || "feedback.php?project=" + encodeURIComponent(p.id);
        let extra = options.showVoteCount
            ? '<p class="mb-2 small"><i class="bi bi-hand-thumbs-up-fill text-danger"></i> <span class="vote-count js-count" data-id="' + p.id + '">' + count + '</span> citizen votes</p>'
            : "";

        return (
            '<div class="col-md-6 col-lg-4" data-status="' + p.status + '" data-category="' + p.category + '">' +
            '<div class="project-card">' +
            '<img src="' + p.image + '" alt="' + p.title + '">' +
            '<div class="project-body">' +
            '<div class="d-flex justify-content-between align-items-start mb-2 gap-2">' +
            '<h5 class="mb-0 fw-semibold">' + p.title + '</h5>' +
            '<span class="status-badge ' + statusClass(p.status) + '">' + p.statusLabel + '</span>' +
            '</div>' +
            '<p class="meta-info mb-3">' +
            '<i class="bi bi-geo-alt-fill"></i> ' + p.constituency + '<br>' +
            '<i class="bi bi-cash-stack"></i> Budget: ' + p.budget +
            '</p>' + extra +
            '<div class="d-flex justify-content-between small mb-1">' +
            '<span>Progress</span>' +
            '<span class="fw-semibold">' + p.progress + '%</span>' +
            '</div>' +
            '<div class="progress mb-3">' +
            '<div class="progress-bar ' + progressBarClass(p.progress, p.complete) + '" style="width: ' + p.progress + '%"></div>' +
            '</div>' +
            '<div class="d-flex gap-2">' +
            (voteBtn || '<a class="btn btn-cdf btn-sm flex-fill" href="/vote.php"><i class="bi bi-hand-thumbs-up me-1"></i> Vote</a>') +
            '<a class="btn btn-outline-gov btn-sm flex-fill" href="' + detailsHref + '">Details</a>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
    }

    function bindVotes(container) {
        if (!container) return;
        container.addEventListener("click", function (e) {
            let btn = e.target.closest(".js-vote");
            if (!btn) return;
            let id = btn.getAttribute("data-id");
            let result = castVote(id);
            if (!result.ok) {
                alert(result.message);
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Voted';
            let countEl = container.querySelector('.js-count[data-id="' + id + '"]');
            if (countEl) countEl.textContent = result.count;
            if (typeof window.CDF_onVote === "function") window.CDF_onVote();
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        updateNavLogin();
        insertConstituencyBanner();
    });

    window.CDF = {
        PROJECTS: PROJECTS,
        getVotes: getVotes,
        totalVotes: totalVotes,
        votesForProjects: votesForProjects,
        castVote: castVote,
        hasVoted: hasVoted,
        getFeedback: getFeedback,
        addFeedback: addFeedback,
        findProject: findProject,
        approvedProjects: approvedProjects,
        votableProjects: votableProjects,
        citizenProjects: citizenProjects,
        renderProjectCard: renderProjectCard,
        bindVotes: bindVotes,
        statusClass: statusClass,
        progressBarClass: progressBarClass,
        SESSION_KEY: SESSION_KEY,
        CONSTITUENCIES_KEY: CONSTITUENCIES_KEY,
        getConstituencies: getConstituencies,
        findConstituency: findConstituency,
        getSession: getSession,
        saveSession: saveSession,
        clearSession: clearSession,
        fillConstituencySelect: fillConstituencySelect,
        updateNavLogin: updateNavLogin
    };
})();