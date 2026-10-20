# CDEMS Project Progress

**Project:** Constituency Development Fund Management System (CDEMS), Blantyre City Council
**Stack:** PHP, MySQL, Bootstrap 5, JavaScript
**Last updated:** 20 October 2026

**Legend:** `[x]` done · `[~]` built but not yet confirmed working in the project · `[ ]` not started

---

## Summary

| # | Remaining item | Status |
|---|---|---|
| 1 | MySQL database | Partly done (authentication tables only) |
| 2 | Email notifications | Not started |
| 3 | Register and login for all users | Built, being verified in the project |
| 4 | Contractor dashboard, assignment and progress tracking | Not started |
| 5 | Citizen voting (votes actually cast and stored) | Interface done, voting not yet real |

---

## Already done

### Citizen portal (front end)
- [x] Dashboard (`index.php`) with stats, featured projects, "how it works"
- [x] Projects list with search and filters (`projects.php`)
- [x] Vote page layout with live statistics (`vote.php`)
- [x] Feedback page with ratings and comments (`feedback.php`)
- [x] Track page with progress timeline (`track.php`)
- [x] Citizen login page (`login.php`) with link to the staff sign-in page
- [x] Shared styling (`css/style.css`) in the Malawi flag colours

### Workspaces (front end)
- [x] **District Council / VDC:** overview, submit project, my projects, decisions, priorities, reports
- [x] **MP:** overview, all proposals, review (approve or reject with reason), decisions, funds and DDO details, progress updates, reports
- [x] **DDO:** overview, priority projects, project management (assign supplier, supervisor, timeline, progress), reports
- [x] Priority ranking based on votes plus readiness

> **Note:** these pages currently keep their data in the browser (localStorage), not in the database.

### Authentication
- [x] Sign-in and registration page design for the four staff roles (`auth.php`)
- [x] Phone number removed from the forms (no SMS)
- [x] Role selector with matching colours and destination per role

---

## 1. MySQL database

**Done**
- [x] Database `cdf_malawi` created (`cdf_auth_schema.sql`), tested on MySQL 8
- [x] `roles` (citizen, council, mp, ddo, contractor) with dashboard path
- [x] `constituencies` (10 seeded)
- [x] `users` (hashed password, status, lockout fields)
- [x] Profile tables: `citizen_profiles`, `council_profiles`, `mp_profiles`, `ddo_profiles`, `contractor_profiles`
- [x] `password_resets`, `remember_tokens`, `auth_log`

**Remaining (project data, needed by items 2, 4 and 5)**
- [ ] `proposals` / `projects` (title, area, sector, budget, VDC owner, status, decision and reason)
- [ ] `allocations` (amount, source, DDO, reference, date, scope)
- [ ] `votes` (citizen, project, date; one vote per citizen per project)
- [ ] `feedback` (citizen, project, rating, comment)
- [ ] `assignments` (project, contractor, assigned by DDO, dates, status)
- [ ] `progress_updates` (assignment, percentage, note, photo, date)
- [ ] `notifications` (recipient, type, message, sent time, read or sent status)
- [ ] Move the dashboards from localStorage to read and write these tables

---

## 2. Email notifications

- [ ] Choose a mail method (PHPMailer with SMTP is recommended)
- [ ] Mail settings in `config/` (SMTP host, port, username, password, from address)
- [ ] Reusable `send_notification()` function
- [ ] Log every notification in the `notifications` table
- [ ] **Registration:** welcome email, and "waiting for approval" email for staff
- [ ] **Account approved:** email when staff access is approved
- [ ] **Proposal decisions:** notify the VDC when an MP approves or rejects, including the reason
- [ ] **Funds allocated:** notify the MP and VDC
- [ ] **Contractor assigned:** notify the contractor (see item 4)
- [ ] **Progress updates:** notify the DDO when a contractor posts progress
- [ ] **Project completed:** notify MP, VDC and DDO (see item 4)
- [ ] **Forgot password:** reset link email (the `password_resets` table is ready)
- [ ] Test with a real mailbox (for example Mailtrap or Gmail SMTP)

---

## 3. Register and login for all users

**Done**
- [x] `api/register.php`: validation, role-specific fields, duplicate email, staff number and registration number checks
- [x] `api/login.php`: bcrypt check, 5-attempt lockout for 15 minutes, role check, redirect to the right dashboard
- [x] `api/csrf.php`, `api/constituencies.php`
- [x] `includes/bootstrap.php`: sessions, CSRF, `require_role()` page guard, "keep me signed in"
- [x] `logout.php`
- [x] `js/auth.js` (staff page) and `js/citizen-login.js` (citizen page)
- [x] Backend tested end to end with a local server and MySQL

**Verify in the project**
- [~] Files placed in the correct folders (`config/`, `includes/`, `api/`, `js/`, `logout.php`)
- [~] Old inline script removed from `auth.php`, replaced with `<script src="js/auth.js"></script>`
- [~] `citizen-login.js` added to `login.php`
- [~] `DB_USER` and `DB_PASS` set in `config/database.php`
- [~] Schema imported into MySQL
- [ ] Add `require_role(...)` to every page in `vdc/`, `mp/` and `ddo/` so dashboards are protected
- [ ] Add a "Sign out" link to each dashboard

**Remaining**
- [ ] Citizen registration page (the API supports it; there is no page yet)
- [ ] Forgot-password flow (needs item 2)
- [ ] Staff approval screen (currently approved by SQL: `UPDATE users SET status='active' WHERE email='...'`)

---

## 4. Contractor dashboard and project delivery

**Flow:** DDO assigns a project to a contractor → contractor updates progress → DDO sees the progress → when finished, others are notified.

**Contractor workspace** (`contractor/`, same style as the other workspaces)
- [ ] `contractor/index.php`: overview with assigned projects, progress and deadlines
- [ ] My projects list
- [ ] Project detail page showing scope, budget, timeline, supervisor
- [ ] Update progress: percentage, note, date, photo upload
- [ ] History of progress updates
- [ ] Mark work as completed and submit a completion report
- [ ] Protect pages with `require_role('contractor')` and add sign out

**DDO side**
- [ ] Replace the free-text "supplier" field with a dropdown of registered, approved contractors
- [ ] Assign a project to a contractor (creates an assignment) with start date, target date and scope
- [ ] View each contractor's progress updates and photos
- [ ] Confirm or reject a completion report
- [ ] Mark the project completed once confirmed

**Notifications for this flow**
- [ ] Contractor notified when assigned
- [ ] DDO notified on each progress update
- [ ] MP, VDC and citizens notified when a project is completed

**Citizen and MP views**
- [ ] Citizen Track page reads real progress updates from the database
- [ ] MP progress page reads the same data

---

## 5. Citizen voting

**Done**
- [x] Vote page layout, vote buttons and live statistics
- [x] Votes are limited to projects in the citizen's constituency
- [x] "Voting closed" for completed projects

**Remaining**
- [ ] Require a signed-in citizen to vote (checked on the server)
- [ ] `votes` table with a unique rule: one vote per citizen per project
- [ ] `api/vote.php` to cast a vote, and an endpoint that returns live counts
- [ ] Replace the localStorage counters with database counts
- [ ] Show "Voted" based on the citizen's account, not the device
- [ ] Citizen votes feed the VDC priorities page and the MP review page
- [ ] Voting open and close dates per project (optional)
- [ ] Test: two different citizens vote, and the same citizen cannot vote twice

---

## Suggested order

1. **Finish authentication in the project** (item 3): confirm login works, then guard the dashboards. Everything else depends on knowing who is signed in.
2. **Project data tables** (item 1): proposals, allocations, votes, assignments, progress updates, notifications.
3. **Citizen voting** (item 5): smallest piece, and it needs the `votes` and `projects` tables.
4. **Contractor workspace and assignment** (item 4): needs the assignment and progress tables.
5. **Email notifications** (item 2): last, because it plugs into the events from steps 3 and 4.

---

## Known issues

- Dashboards and the citizen portal still use browser storage, so data differs between browsers and is lost when the browser is cleared.
- The "Forgot password?" link on the staff sign-in page does nothing yet.
- `contractor/index.php` does not exist, so the contractor redirect shows an error until it is built.
- The citizen login page still says "Email or phone". Phone is no longer used, so the label should say email only.
