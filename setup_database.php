<?php
/**
 * CDF Malawi - database setup
 *
 * Creates the database and every table, then seeds the roles and constituencies.
 * Safe to run more than once: existing tables and rows are left untouched.
 *
 * Run it from the project root:
 *     php setup_database.php
 * or open it in the browser on your own computer:
 *     http://localhost/<your-project>/setup_database.php
 *
 * It reads the connection settings from config/database.php. The MySQL user
 * there needs permission to create databases (on XAMPP/WAMP, "root" does).
 *
 * DELETE THIS FILE once the database exists. Do not leave it on a live server.
 */

$isCli = (PHP_SAPI === 'cli');

// Only run from the command line or from this computer.
if (!$isCli && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Setup can only be run from the command line or from localhost.');
}

require_once __DIR__ . '/config/database.php';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><meta charset="UTF-8"><title>CDF database setup</title>'
       . '<body style="font-family:ui-monospace,Consolas,monospace;max-width:760px;margin:2rem auto;padding:0 1rem"><pre>';
}

function out(string $line = ''): void
{
    global $isCli;
    echo ($isCli ? $line : htmlspecialchars($line, ENT_QUOTES, 'UTF-8')) . PHP_EOL;
    if (!$isCli) {
        flush();
    }
}

/* ------------------------------------------------------------------ */
/* 1. Connect to the server and create the database                    */
/* ------------------------------------------------------------------ */

out('CDF Malawi database setup');
out(str_repeat('=', 40));

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    out('Could not connect to MySQL: ' . $e->getMessage());
    out('Check DB_HOST, DB_USER and DB_PASS in config/database.php.');
    exit(1);
}

$dbName = DB_NAME;
if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
    out('DB_NAME may only contain letters, numbers and underscores.');
    exit(1);
}

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    out("Database `$dbName` is ready.");
    out();
} catch (PDOException $e) {
    out('Could not create the database: ' . $e->getMessage());
    out('The MySQL user needs the CREATE privilege (or create the database by hand first).');
    exit(1);
}

/* ------------------------------------------------------------------ */
/* 2. Tables (in dependency order)                                     */
/* ------------------------------------------------------------------ */

$tables = [];

/* ---------- Authentication ---------- */

$tables['roles'] = <<<'SQL'
CREATE TABLE roles (
    id        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug      VARCHAR(20)  NOT NULL,
    name      VARCHAR(60)  NOT NULL,
    dashboard VARCHAR(120) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['constituencies'] = <<<'SQL'
CREATE TABLE constituencies (
    id       SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code     VARCHAR(40)  NOT NULL,
    name     VARCHAR(120) NOT NULL,
    district VARCHAR(60)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_constituencies_code (code),
    UNIQUE KEY uq_constituencies_name (name),
    KEY idx_constituencies_district (district)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['users'] = <<<'SQL'
CREATE TABLE users (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id               TINYINT UNSIGNED NOT NULL,
    full_name             VARCHAR(120) NOT NULL,
    email                 VARCHAR(190) NOT NULL,
    password_hash         VARCHAR(255) NOT NULL,
    status                ENUM('active','pending','suspended') NOT NULL DEFAULT 'pending',
    failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until          DATETIME NULL,
    last_login_at         DATETIME NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role_id),
    KEY idx_users_status (status),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['citizen_profiles'] = <<<'SQL'
CREATE TABLE citizen_profiles (
    user_id         INT UNSIGNED NOT NULL,
    constituency_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id),
    KEY idx_citizen_constituency (constituency_id),
    CONSTRAINT fk_citizen_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_citizen_constituency FOREIGN KEY (constituency_id) REFERENCES constituencies (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['council_profiles'] = <<<'SQL'
CREATE TABLE council_profiles (
    user_id      INT UNSIGNED NOT NULL,
    council_ward VARCHAR(120) NOT NULL,
    position     VARCHAR(100) NOT NULL,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_council_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['mp_profiles'] = <<<'SQL'
CREATE TABLE mp_profiles (
    user_id              INT UNSIGNED NOT NULL,
    constituency_id      INT UNSIGNED NOT NULL,
    parliamentary_office VARCHAR(120) NOT NULL,
    PRIMARY KEY (user_id),
    KEY idx_mp_constituency (constituency_id),
    CONSTRAINT fk_mp_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_mp_constituency FOREIGN KEY (constituency_id) REFERENCES constituencies (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['ddo_profiles'] = <<<'SQL'
CREATE TABLE ddo_profiles (
    user_id      INT UNSIGNED NOT NULL,
    district     VARCHAR(60) NOT NULL,
    staff_number VARCHAR(40) NOT NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_ddo_staff_number (staff_number),
    CONSTRAINT fk_ddo_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['contractor_profiles'] = <<<'SQL'
CREATE TABLE contractor_profiles (
    user_id             INT UNSIGNED NOT NULL,
    company_name        VARCHAR(150) NOT NULL,
    business_reg_number VARCHAR(60)  NOT NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_contractor_reg (business_reg_number),
    CONSTRAINT fk_contractor_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['password_resets'] = <<<'SQL'
CREATE TABLE password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at    DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_resets_token (token_hash),
    KEY idx_password_resets_user (user_id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['remember_tokens'] = <<<'SQL'
CREATE TABLE remember_tokens (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    selector       CHAR(24) NOT NULL,
    validator_hash CHAR(64) NOT NULL,
    expires_at     DATETIME NOT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_remember_selector (selector),
    KEY idx_remember_user (user_id),
    KEY idx_remember_expires (expires_at),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['auth_log'] = <<<'SQL'
CREATE TABLE auth_log (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NULL,
    email      VARCHAR(190) NOT NULL DEFAULT '',
    event      VARCHAR(30)  NOT NULL,
    ip_address VARCHAR(45)  NOT NULL DEFAULT '',
    user_agent VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auth_log_user (user_id),
    KEY idx_auth_log_email (email),
    KEY idx_auth_log_created (created_at),
    CONSTRAINT fk_authlog_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

/* ---------- Project data ---------- */

$tables['projects'] = <<<'SQL'
CREATE TABLE projects (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_no    VARCHAR(30)  NOT NULL,
    title           VARCHAR(180) NOT NULL,
    area            VARCHAR(150) NOT NULL,
    sector          VARCHAR(40)  NOT NULL,
    budget          DECIMAL(15,2) NOT NULL,
    beneficiaries   INT UNSIGNED NULL,
    description     TEXT NOT NULL,
    constituency_id SMALLINT UNSIGNED NOT NULL,
    submitted_by    INT UNSIGNED NULL,
    status          ENUM('submitted','review','accepted','denied','progress','completed') NOT NULL DEFAULT 'submitted',
    decision_reason TEXT NULL,
    decided_by      INT UNSIGNED NULL,
    decided_at      DATETIME NULL,
    progress        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    voting_open     TINYINT(1) NOT NULL DEFAULT 1,
    voting_opens_at DATE NULL,
    voting_closes_at DATE NULL,
    image_url       VARCHAR(255) NULL,
    document_path   VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_projects_reference (reference_no),
    KEY idx_projects_constituency (constituency_id),
    KEY idx_projects_status (status),
    KEY idx_projects_sector (sector),
    CONSTRAINT fk_projects_constituency FOREIGN KEY (constituency_id) REFERENCES constituencies (id),
    CONSTRAINT fk_projects_submitter FOREIGN KEY (submitted_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_decider FOREIGN KEY (decided_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['project_comments'] = <<<'SQL'
CREATE TABLE project_comments (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_project (project_id),
    CONSTRAINT fk_comments_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['allocations'] = <<<'SQL'
CREATE TABLE allocations (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id   INT UNSIGNED NOT NULL,
    amount       DECIMAL(15,2) NOT NULL,
    source       VARCHAR(120) NOT NULL,
    ddo_user_id  INT UNSIGNED NULL,
    reference    VARCHAR(60)  NOT NULL,
    allocated_on DATE NOT NULL,
    scope        VARCHAR(255) NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_allocations_project (project_id),
    UNIQUE KEY uq_allocations_reference (reference),
    KEY idx_allocations_ddo (ddo_user_id),
    CONSTRAINT fk_allocations_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT fk_allocations_ddo FOREIGN KEY (ddo_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['votes'] = <<<'SQL'
CREATE TABLE votes (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_votes_user_project (user_id, project_id),
    KEY idx_votes_project (project_id),
    CONSTRAINT fk_votes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['feedback'] = <<<'SQL'
CREATE TABLE feedback (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NULL,
    rating     TINYINT UNSIGNED NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_feedback_project (project_id),
    KEY idx_feedback_user (user_id),
    CONSTRAINT fk_feedback_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['assignments'] = <<<'SQL'
CREATE TABLE assignments (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id              INT UNSIGNED NOT NULL,
    contractor_id           INT UNSIGNED NOT NULL,
    assigned_by             INT UNSIGNED NULL,
    supervisor              VARCHAR(120) NULL,
    scope                   TEXT NULL,
    start_date              DATE NULL,
    target_date             DATE NULL,
    status                  ENUM('assigned','active','completed','blocked') NOT NULL DEFAULT 'assigned',
    completion_report       TEXT NULL,
    completion_submitted_at DATETIME NULL,
    completion_decision     ENUM('pending','confirmed','rejected') NULL,
    completion_decided_at   DATETIME NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assignments_project (project_id),
    KEY idx_assignments_contractor (contractor_id),
    KEY idx_assignments_status (status),
    CONSTRAINT fk_assignments_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_contractor FOREIGN KEY (contractor_id) REFERENCES users (id),
    CONSTRAINT fk_assignments_assigner FOREIGN KEY (assigned_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['progress_updates'] = <<<'SQL'
CREATE TABLE progress_updates (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    assignment_id INT UNSIGNED NOT NULL,
    percentage    TINYINT UNSIGNED NOT NULL,
    note          TEXT NOT NULL,
    photo_path    VARCHAR(255) NULL,
    update_date   DATE NOT NULL,
    created_by    INT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_progress_assignment (assignment_id),
    KEY idx_progress_date (update_date),
    CONSTRAINT fk_progress_assignment FOREIGN KEY (assignment_id) REFERENCES assignments (id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

$tables['notifications'] = <<<'SQL'
CREATE TABLE notifications (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NULL,
    email      VARCHAR(190) NOT NULL,
    type       VARCHAR(40)  NOT NULL,
    subject    VARCHAR(200) NOT NULL,
    message    TEXT NOT NULL,
    status     ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    error      VARCHAR(255) NULL,
    sent_at    DATETIME NULL,
    read_at    DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id),
    KEY idx_notifications_status (status),
    KEY idx_notifications_type (type),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

out('Tables');
out(str_repeat('-', 40));

$failed = false;
foreach ($tables as $name => $sql) {
    try {
        $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($name))->fetchColumn() !== false;
        if ($exists) {
            out(sprintf('  %-22s already exists, skipped', $name));
            continue;
        }
        $pdo->exec($sql);
        out(sprintf('  %-22s created', $name));
    } catch (PDOException $e) {
        $failed = true;
        out(sprintf('  %-22s FAILED: %s', $name, $e->getMessage()));
        break;   // later tables depend on earlier ones
    }
}

if ($failed) {
    out();
    out('Setup stopped because of the error above. Fix it and run the script again.');
    exit(1);
}

/* ------------------------------------------------------------------ */
/* 3. Seed data                                                        */
/* ------------------------------------------------------------------ */

out();
out('Seed data');
out(str_repeat('-', 40));

$roles = [
    ['citizen',    'Citizen',                      'projects.php'],
    ['council',    'District Council',             'vdc/index.php'],
    ['mp',         'Member of Parliament',         'mp/index.php'],
    ['ddo',        'District Development Officer', 'ddo/index.php'],
    ['contractor', 'Contractor',                   'contractor/index.php'],
];

// Codes match the constituency ids used by js/app.js so the citizen filter lines up.
$constituencies = [
    ['c-ll-south',   'Lilongwe South Constituency', 'Lilongwe'],
    ['c-ll-centre',  'Lilongwe City Centre',        'Lilongwe'],
    ['c-bt-west',    'Blantyre City West',          'Blantyre'],
    ['c-bt-east',    'Blantyre City East',          'Blantyre'],
    ['c-mz-central', 'Mzuzu Central',               'Mzimba'],
    ['c-zb-central', 'Zomba Central',               'Zomba'],
    ['c-sa-north',   'Salima North',                'Salima'],
    ['c-ka-town',    'Karonga Town',                'Karonga'],
    ['c-dz-south',   'Dedza South',                 'Dedza'],
    ['c-mg-central', 'Mangochi Central',            'Mangochi'],
];

try {
    $stmt = $pdo->prepare('INSERT IGNORE INTO roles (slug, name, dashboard) VALUES (?, ?, ?)');
    $added = 0;
    foreach ($roles as $r) {
        $stmt->execute($r);
        $added += $stmt->rowCount();
    }
    out(sprintf('  roles                  %d added, %d already there', $added, count($roles) - $added));

    $stmt = $pdo->prepare('INSERT IGNORE INTO constituencies (code, name, district) VALUES (?, ?, ?)');
    $added = 0;
    foreach ($constituencies as $c) {
        $stmt->execute($c);
        $added += $stmt->rowCount();
    }
    out(sprintf('  constituencies         %d added, %d already there', $added, count($constituencies) - $added));
} catch (PDOException $e) {
    out('  Seeding failed: ' . $e->getMessage());
    exit(1);
}

/* ------------------------------------------------------------------ */
/* 4. Done                                                             */
/* ------------------------------------------------------------------ */

out();
out('Done. The database is ready.');
out();
out('Next steps:');
out('  1. Delete setup_database.php from the project.');
out('  2. Register a citizen on login.php and a staff user on auth.php.');
out("  3. Approve staff accounts with:  UPDATE users SET status='active' WHERE email='name@example.mw';");

if (!$isCli) {
    echo '</pre></body></html>';
}
