# CDEMS Project Progress

**Project:** Constituency Development Fund Management System (CDEMS), Blantyre City Council
**Stack:** PHP, MySQL, Bootstrap 5, JavaScript
**Last updated:** 20 October 2026

**Legend:** `[x]` done · `[~]` built but not yet confirmed working in the project · `[ ]` not started

> **Naming note:** the District Council workspace is called "DC" on screen, but the folder, CSS and JS files are still named `vdc/` (`vdc.css`, `vdc.js`). This is kept on purpose; renaming would touch too many files. `council` is the role slug in the database.

---

## Summary

| # | Item                                                   | Status                                                                                        |
|---|--------------------------------------------------------|-----------------------------------------------------------------------------------------------|
| 1 | MySQL database                                         | Auth tables in use; project tables created by `setup_database.php`, not yet used by the pages |
| 2 | Email notifications                                    | Not started                                                                                   |
| 3 | Register and login for all users                       | Done (see section 3 for the remaining guard and sign-out work)                                |
| 4 | Contractor dashboard, assignment and progress tracking | Pages copied from DDO (still localStorage), not yet real                                      |
| 5 | Citizen voting (votes actually cast and stored)        | Interface done, voting not yet real                                                           |
| 6 | DC project submission (new)                            | In progress: moving from localStorage to the database                                         |

---

## Already done

### Citizen portal (front end)
- [x] Dashboard (`index.php`) with stats, featured projects, "how it works"
- [x] Projects list with search and filters (`projects.php`)
- [x] Vote page layout with live statistics (`vote.php`)
- [x] Feedback page with ratings and comments (`feedback.php`)
- [x] Track page with progress timeline (`track.php`)
- [x] Citizen login and registration page (`login.php`) with Sign in / Create account tabs, constituency picker, and link to the staff page
- [x] Shared styling (`css/style.css`) in the Malawi flag colors
- [x] Shared top bar (`includes/topbar.php`): shows Login / Register / Staff sign-in when signed out, and name, workspace link, and Logout when signed in

### Workspaces (front end)
- [x] **District Council (DC, in `vdc/`):** overview, submit a project, my projects, decisions, priorities, reports
- [x] **MP:** overview, all proposals, review (approve or reject with reason), decisions, funds and DDO details, progress updates, reports
- [x] **DDO:** overview, priority projects, project management (assign supplier, supervisor, timeline, progress), reports, user accounts
- [x] **Contractor (`contractor/`):** overview, priority projects, project page, and reports exist as a copy of the DDO pages (still localStorage, no ownership rules yet)
- [x] Priority ranking based on votes plus readiness

> **Note:** apart from authentication and DDO user accounts, these pages still keep their data in the browser (localStorage), not in the database.

---

## 1. MySQL database

**Done**
- [x] Setup script `setup_database.php`: creates the database and every table, seeds roles and constituencies, safe to run again (delete it from the server after use)
- [x] `roles` (citizen, council, mp, ddo, contractor) with a dashboard path (contractor goes to `contractor/index.php`)
- [x] `constituencies` (10 seeded, codes match the ids in `js/app.js`)
- [x] `users` (hashed password, status, lockout fields)
- [x] Profile tables: `citizen_profiles`, `council_profiles`, `mp_profiles`, `ddo_profiles`, `contractor_profiles`
- [x] `password_resets`, `remember_tokens`, `auth_log`
- [x] Project tables created by the setup script: `projects`, `project_comments`, `allocations`, `votes`, `feedback`, `assignments`, `progress_updates`, `notifications`

**Remaining**
- [ ] Read and write the project tables from the pages (done only for DC project submission once item 6 is finished)
- [ ] Move the MP, DDO, contractor, and citizen pages from localStorage to these tables
- [ ] Seed a few sample projects so the dashboards are not empty

---

## 2. Email notifications

- [ ] Choose a mail method (PHPMailer with SMTP is recommended)
- [ ] Mail settings in `config/` (SMTP host, port, username, password, from address)
- [ ] Reusable `send_notification()` function
- [ ] Log every notification in the `notifications` table (table exists)
- [ ] **Registration:** welcome email, and "waiting for approval" email for staff
- [ ] **Account approved:** email when staff access is approved
- [ ] **Proposal decisions:** notify the DC when an MP approves or rejects, including the reason
- [ ] **Funds allocated:** notify the MP and DC
- [ ] **Contractor assigned:** notify the contractor (see item 4)
- [ ] **Progress updates:** notify the DDO when a contractor posts progress
- [ ] **Project completed:** notify MP, DC, and DDO (see item 4)
- [ ] **Forgot password:** reset link email (the `password_resets` table is ready)
- [ ] Test with a real mailbox (for example, Mailtrap or Gmail SMTP)

---

## 3. Register and login for all users

**Done**
- [x] `api/register.php`: validation, role-specific fields, duplicate email, staff number, and registration number checks
- [x] `api/login.php`: bcrypt check, 5-attempt lockout for 15 minutes, role check, pending and suspended messages, redirect to the right dashboard
- [x] `api/csrf.php`, `api/constituencies.php`
- [x] `includes/bootstrap.php`: sessions, CSRF, `require_role()` page guard, "keep me signed in", audit log
- [x] `logout.php` (also clears the citizen constituency kept in the browser)
- [x] `js/auth.js` (staff page `auth.php`) and `js/citizen-login.js` (citizen page `login.php`)
- [x] Backend tested end to end with a local server and MySQL
- [x] Citizen registration page (the "Create account" tab on `login.php`)
- [x] Staff approval screen: `ddo/users.php` with `api/ddo_users.php` (approve, suspend, reactivate; DDO only; an own account protected; remember-me tokens removed on suspend)
- [x] Sign-out link is available on every page through the shared top bar
- [x] "Email or phone" label removed from the citizen login (email only)
- [x] Approval rules: council and contractor accounts wait for the DDO; citizens, MPs, and DDOs are active straight away (`ROLES_REQUIRING_APPROVAL` in `config/database.php`)

**Still to do**
- [ ] Add `require_role(...)` to every page in `vdc/`, `mp/`, `contractor/` and the rest of `ddo/` (only `ddo/users.php` and, soon, the DC project pages are guarded)
- [ ] Forgot-password flow (needs item 2)
- [ ] Confirm the first DDO can be created and can approve the others (the first DDO registers and is active at once)

---

## 4. Contractor dashboard and project delivery

**Flow:** DDO assigns a project to a contractor → contractor updates progress → DDO sees the progress → when finished, others are notified.

**Contractor workspace** (`contractor/`, same style as the other workspaces)
- [x] `contractor/index.php`, project list, project page, and reports pages exist (copied from DDO, localStorage)
- [ ] Show only the projects assigned to the signed-in contractor
- [ ] Update progress: percentage, note, date, photo upload
- [ ] History of progress updates
- [ ] Mark work as completed and submit a completion report
- [ ] Protect pages with `require_role('contractor')`; link the workspace back to the right place (the overview currently links to the MP workspace)

**DDO side**
- [ ] Replace the free-text "supplier" field with a dropdown of registered, approved contractors
- [ ] Assign a project to a contractor (creates an assignment) with start date, target date and scope
- [ ] View each contractor's progress updates and photos
- [ ] Confirm or reject a completion report
- [ ] Mark the project completed once confirmed

**Notifications for this flow**
- [ ] Contractor notified when assigned
- [ ] DDO is notified on each progress update
- [ ] MP, DC, and citizens are notified when a project is completed

**Citizen and MP views**
- [ ] Citizen Track page reads real progress updates from the database
- [ ] MP progress page reads the same data

---

## 5. Citizen voting

**Done**
- [x] Vote page layout, vote buttons, and live statistics
- [x] Votes are limited to projects in the citizen's constituency
- [x] "Voting closed" for completed projects
- [x] `votes` table with one vote per citizen per project (created by the setup script)

**Remaining**
- [ ] Require a signed-in citizen to vote (checked on the server)
- [ ] `api/vote.php` to cast a vote, and an endpoint that returns live counts
- [ ] Replace the localStorage counters with database counts
- [ ] Show "Voted" based on the citizen's account, not the device
- [ ] Citizen votes feed the DC priorities page and the MP review page
- [ ] Voting open and close dates per project (columns exist, optional)
- [ ] Test: two different citizens vote, and the same citizen cannot vote twice

---

## 6. DC project submission (in progress)

- [x] `api/projects.php`: council users create a project (validated, unique reference number, status `submitted`) and list their own
- [x] `vdc/submit.php` posts to the API (with CSRF) and shows the reference number
- [x] `vdc/projects.php` lists the signed-in user's projects from the database
- [x] Constituency picker on the form (the council profile stores a ward, not a constituency)
- [ ] Supporting document upload (the form has the field; the file is not stored yet)
- [ ] DC overview, decisions, priorities, and reports read from the database
- [ ] MP review page reads the same projects and records decisions in the database

---

## Suggested order

1. **Finish DC project submission** (item 6): it creates the first real project rows.
2. **Guard every workspace page** with `require_role(...)` and finish the MP review so decisions are stored.
3. **Citizen voting** (item 5): needs the `projects` rows from step 1.
4. **Contractor workspace and assignment** (item 4): needs approved projects, allocations, and the assignment tables.
5. **Email notifications** (item 2): last, because it plugs into the events from the earlier steps.

---

## Known issues

- Apart from authentication, DDO user accounts and (soon) DC submission, pages still use browser storage, so data differs between browsers and is lost when the browser is cleared.
- The "Forgot password?" link on the staff sign-in page does nothing yet.
- The contractor overview links back to the MP workspace, and the contractor pages are not guarded yet.
- `setup_database.php` must be deleted from any live server.
- `config/database.php` holds a real database user and password; keep it out of version control.