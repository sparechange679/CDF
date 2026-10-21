<?php
// Email notifications.
//
//   require_once __DIR__ . '/../includes/notifications.php';
//   send_notification($userId, $email, 'type', 'Subject', 'Plain text body', '<p>Optional HTML</p>');
//
// Every email is recorded in the `notifications` table first (queued), then sent, then marked
// sent or failed. Nothing in here throws, so a mail problem never breaks the action that
// triggered it.

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/mail.php';

/* ------------------------------------------------------------------ */
/* PHPMailer loading                                                   */
/* ------------------------------------------------------------------ */

/** Finds PHPMailer from Composer (vendor/) or from a copy in includes/PHPMailer/src/. */
function mailer_available(): bool
{
    if (class_exists(PHPMailer::class, false)) {
        return true;
    }
    $autoload = __DIR__ . '/../vendor/autoload.php';
    $manual   = __DIR__ . '/PHPMailer/src/PHPMailer.php';

    if (is_file($autoload)) {
        require_once $autoload;
    } elseif (is_file($manual)) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once $manual;
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    }
    return class_exists(PHPMailer::class);
}

/** Message of the most recent failure from send_notification() (null if it succeeded). */
function mail_last_error(?string $set = null, bool $write = false): ?string
{
    static $last = null;
    if ($write) {
        $last = $set;
    }
    return $last;
}

/* ------------------------------------------------------------------ */
/* Core sender                                                         */
/* ------------------------------------------------------------------ */

function send_notification(?int $userId, string $email, string $type, string $subject, string $text, ?string $html = null): bool
{
    mail_last_error(null, true);

    // 1. Record it first, so there is a trace even if sending fails.
    $rowId = null;
    try {
        db()->prepare(
            'INSERT INTO notifications (user_id, email, type, subject, message) VALUES (?, ?, ?, ?, ?)'
        )->execute([$userId, substr($email, 0, 190), substr($type, 0, 40), substr($subject, 0, 200), $text]);
        $rowId = (int) db()->lastInsertId();
    } catch (Throwable $e) {
        error_log('notification log failed: ' . $e->getMessage());
    }

    $finish = function (bool $ok, ?string $error) use ($rowId): bool {
        mail_last_error($error, true);
        if ($rowId !== null) {
            try {
                if ($ok) {
                    db()->prepare("UPDATE notifications SET status = 'sent', sent_at = NOW(), error = NULL WHERE id = ?")
                        ->execute([$rowId]);
                } else {
                    db()->prepare("UPDATE notifications SET status = 'failed', error = ? WHERE id = ?")
                        ->execute([substr((string) $error, 0, 255), $rowId]);
                }
            } catch (Throwable $e) {
                error_log('notification status update failed: ' . $e->getMessage());
            }
        }
        if (!$ok) {
            error_log('email to ' . $email . ' failed: ' . $error);
        }
        return $ok;
    };

    // 2. Switched off: leave the row as "queued".
    if (!MAIL_ENABLED) {
        mail_last_error('Email sending is switched off (MAIL_ENABLED).', true);
        return false;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $finish(false, 'Invalid recipient address.');
    }
    if (!mailer_available()) {
        return $finish(false, 'PHPMailer is not installed. Run "composer require phpmailer/phpmailer" or copy it to includes/PHPMailer/.');
    }

    // 3. Send.
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host     = MAIL_HOST;
        $mail->Port     = MAIL_PORT;
        $mail->SMTPAuth = MAIL_USERNAME !== '';
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->Timeout  = 10;   // seconds, so a dead mail server cannot hang the page for long
        $mail->CharSet  = 'UTF-8';

        if (MAIL_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (MAIL_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($email);
        $mail->Subject = $subject;
        if ($html !== null) {
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $text;
        } else {
            $mail->isHTML(false);
            $mail->Body = $text;
        }
        $mail->send();
        return $finish(true, null);
    } catch (Throwable $e) {
        return $finish(false, $e->getMessage());
    }
}

/* ------------------------------------------------------------------ */
/* Email layout helpers                                                */
/* ------------------------------------------------------------------ */

function mail_esc(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Wraps content in a simple branded page (flag-coloured stripe, white card). */
function email_layout(string $heading, string $innerHtml): string
{
    return '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden">'
        . '<tr><td style="height:6px;background:#111111"></td></tr>'
        . '<tr><td style="height:6px;background:#c8102e"></td></tr>'
        . '<tr><td style="height:6px;background:#006b3f"></td></tr>'
        . '<tr><td style="padding:28px 28px 8px"><div style="font-size:13px;color:#667078">' . mail_esc(MAIL_FROM_NAME) . '</div>'
        . '<h1 style="font-size:20px;margin:6px 0 0">' . mail_esc($heading) . '</h1></td></tr>'
        . '<tr><td style="padding:12px 28px 28px;font-size:15px;line-height:1.55">' . $innerHtml . '</td></tr>'
        . '<tr><td style="padding:16px 28px;background:#f8f9fa;font-size:12px;color:#667078">'
        . 'This is an automated message from the Constituency Development Fund system. Please do not reply.</td></tr>'
        . '</table></body></html>';
}

/** label => value rows as an HTML table. Empty values are skipped. */
function email_details(array $rows): string
{
    $out = '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:14px 0;border-top:1px solid #e4e9ee">';
    foreach ($rows as $label => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $out .= '<tr><td style="padding:8px 12px 8px 0;border-bottom:1px solid #e4e9ee;color:#667078;width:38%;vertical-align:top">' . mail_esc($label) . '</td>'
              . '<td style="padding:8px 0;border-bottom:1px solid #e4e9ee;font-weight:600">' . nl2br(mail_esc((string) $value)) . '</td></tr>';
    }
    return $out . '</table>';
}

function email_button(string $url, string $label): string
{
    return '<p style="margin:20px 0 0"><a href="' . mail_esc($url) . '" style="background:#c8102e;color:#ffffff;text-decoration:none;'
        . 'padding:11px 22px;border-radius:6px;font-weight:bold;display:inline-block">' . mail_esc($label) . '</a></p>';
}

/* ------------------------------------------------------------------ */
/* Events                                                              */
/* ------------------------------------------------------------------ */

/**
 * DDO assigned a project to a contractor.
 * $d keys (all optional): supervisor, scope, start_date, target_date, assigned_by
 */
function notify_contractor_assigned(PDO $pdo, int $projectId, int $contractorId, array $d = []): void
{
    try {
        $c = $pdo->prepare(
            'SELECT u.id, u.full_name, u.email, kp.company_name
               FROM users u LEFT JOIN contractor_profiles kp ON kp.user_id = u.id
              WHERE u.id = ?'
        );
        $c->execute([$contractorId]);
        $contractor = $c->fetch();

        $p = $pdo->prepare(
            'SELECT p.title, p.reference_no, p.area, p.budget, k.name AS constituency
               FROM projects p JOIN constituencies k ON k.id = p.constituency_id
              WHERE p.id = ?'
        );
        $p->execute([$projectId]);
        $project = $p->fetch();

        if (!$contractor || !$project) {
            return;
        }

        $link    = APP_URL . '/contractor/project.php?id=' . $projectId;
        $budget  = 'MK ' . number_format((float) $project['budget'], 2);
        $rows    = [
            'Project'        => $project['title'],
            'Reference'      => $project['reference_no'],
            'Location'       => $project['area'] . ', ' . $project['constituency'],
            'Budget'         => $budget,
            'Start date'     => $d['start_date'] ?? '',
            'Target date'    => $d['target_date'] ?? '',
            'Supervisor'     => $d['supervisor'] ?? '',
            'Scope of work'  => $d['scope'] ?? '',
            'Assigned by'    => $d['assigned_by'] ?? '',
        ];

        $text = "Hello {$contractor['full_name']},\n\n"
              . "You have been assigned to a Constituency Development Fund project.\n\n";
        foreach ($rows as $label => $value) {
            if ($value !== null && $value !== '') {
                $text .= $label . ': ' . $value . "\n";
            }
        }
        $text .= "\nOpen the project and post progress updates here:\n{$link}\n";

        $html = email_layout(
            'New project assigned to you',
            '<p style="margin:0 0 4px">Hello ' . mail_esc($contractor['full_name']) . ',</p>'
            . '<p style="margin:0">You have been assigned to a Constituency Development Fund project.</p>'
            . email_details($rows)
            . '<p style="margin:0">Please post progress updates as the work moves forward.</p>'
            . email_button($link, 'Open project')
        );

        send_notification(
            (int) $contractor['id'],
            (string) $contractor['email'],
            'contractor_assigned',
            'New project assigned: ' . $project['title'],
            $text,
            $html
        );
    } catch (Throwable $e) {
        error_log('notify_contractor_assigned failed: ' . $e->getMessage());
    }
}

// ---- APPEND THIS TO THE END OF includes/notifications.php ----

/**
 * Quotation emails.
 * $event: requested (-> contractor) | submitted (-> DDO who asked) | accepted / rejected (-> contractor)
 */
function notify_quotation_event(PDO $pdo, int $projectId, string $event): void
{
    try {
        $st = $pdo->prepare(
            'SELECT q.*, p.title, p.reference_no, p.budget,
                    cu.id AS c_id, cu.full_name AS c_name, cu.email AS c_email,
                    du.id AS d_id, du.full_name AS d_name, du.email AS d_email
               FROM quotations q
               JOIN projects p ON p.id = q.project_id
               JOIN users cu ON cu.id = q.contractor_id
          LEFT JOIN users du ON du.id = q.requested_by
              WHERE q.project_id = ?'
        );
        $st->execute([$projectId]);
        $q = $st->fetch();
        if (!$q) {
            return;
        }

        $toDdo = ($event === 'submitted');
        $uid   = (int) ($toDdo ? $q['d_id'] : $q['c_id']);
        $email = (string) ($toDdo ? $q['d_email'] : $q['c_email']);
        $name  = (string) ($toDdo ? $q['d_name'] : $q['c_name']);
        if ($email === '') {
            return;
        }

        $link = $toDdo
            ? APP_URL . '/ddo/quotation.php?project=' . $projectId
            : APP_URL . '/contractor/quotation.php';
        $price = $q['amount'] !== null ? 'MK ' . number_format((float) $q['amount'], 2) : '';

        $copy = [
            'requested' => ['Quotation requested: ',  'Quotation requested',  'The District Development Officer has asked you for a quotation for this project. Please send your price and the time you need.'],
            'submitted' => ['Quotation received: ',   'Quotation received',   $q['c_name'] . ' has sent a quotation for this project.'],
            'accepted'  => ['Quotation accepted: ',   'Quotation accepted',   'Your quotation has been accepted.'],
            'rejected'  => ['Quotation rejected: ',   'Quotation rejected',   'Your quotation was not accepted. Check the note below and send a new one if asked.'],
        ][$event] ?? null;
        if (!$copy) {
            return;
        }

        $rows = [
            'Project'      => $q['title'],
            'Reference'    => $q['reference_no'],
            'Budget'       => 'MK ' . number_format((float) $q['budget'], 2),
            'Price quoted' => $price,
            'Duration'     => $q['duration_days'] ? $q['duration_days'] . ' days' : '',
            'Reply by'     => $event === 'requested' ? (string) $q['due_date'] : '',
            'Note'         => $event === 'requested' ? (string) $q['request_note'] : (string) $q['decision_note'],
        ];

        $text = "Hello {$name},\n\n{$copy[2]}\n\n";
        foreach ($rows as $label => $value) {
            if ($value !== null && $value !== '') {
                $text .= $label . ': ' . $value . "\n";
            }
        }
        $text .= "\nOpen it here:\n{$link}\n";

        $html = email_layout(
            $copy[1],
            '<p style="margin:0 0 4px">Hello ' . mail_esc($name) . ',</p><p style="margin:0">' . mail_esc($copy[2]) . '</p>'
            . email_details($rows) . email_button($link, 'Open quotation')
        );

        send_notification($uid, $email, 'quotation_' . $event, $copy[0] . $q['title'], $text, $html);
    } catch (Throwable $e) {
        error_log('notify_quotation_event failed: ' . $e->getMessage());
    }
}

/* ------------------------------------------------------------------ */
/* Generic helper + remaining events                                   */
/* ------------------------------------------------------------------ */

function notify_simple(?int   $uid, string $email, string $name, string $type, string $subject,
                       string $heading, string $intro, array $rows = [], ?string $link = null, string $btn = 'Open'): bool
{
    $text = "Hello {$name},\n\n{$intro}\n\n";
    foreach ($rows as $k => $v) {
        if ($v !== null && $v !== '') {
            $text .= "$k: $v\n";
        }
    }
    if ($link) {
        $text .= "\n{$link}\n";
    }

    $html = email_layout(
        $heading,
        '<p style="margin:0 0 4px">Hello ' . mail_esc($name) . ',</p><p style="margin:0">' . nl2br(mail_esc($intro)) . '</p>'
        . email_details($rows) . ($link ? email_button($link, $btn) : '')
    );
    return send_notification($uid, $email, $type, $subject, $text, $html);
}

/** Welcome email; staff roles that need approval get the "waiting" version. */
function notify_registration(PDO $pdo, int $userId, string $role, string $name, string $email, bool $pending): void
{
    try {
        $roleName = (string)$pdo->query('SELECT name FROM roles WHERE slug = ' . $pdo->quote($role))->fetchColumn();
        $login = APP_URL . ($role === 'citizen' ? '/login.php' : '/auth.php');

        if ($pending) {
            notify_simple($userId, $email, $name, 'registration_pending', 'Your CDF account is waiting for approval',
                'Account received',
                "Thank you for registering as {$roleName}. Your account must be approved by the District Development Officer before you can sign in. You will get another email as soon as it is approved.",
                ['Role' => $roleName, 'Email' => $email]);
        } else {
            notify_simple($userId, $email, $name, 'registration_welcome', 'Welcome to the CDF Malawi portal',
                'Welcome',
                "Your {$roleName} account has been created and is ready to use.",
                ['Role' => $roleName, 'Email' => $email], $login, 'Sign in');
        }
    } catch (Throwable $e) {
        error_log('notify_registration failed: ' . $e->getMessage());
    }
}

/** $event: approved | reactivated | suspended */
function notify_account_status(PDO $pdo, int $userId, string $event): void
{
    try {
        $st = $pdo->prepare('SELECT u.full_name, u.email, r.slug, r.name AS role_name
                               FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
        $st->execute([$userId]);
        $u = $st->fetch();
        if (!$u) {
            return;
        }

        $login = APP_URL . ($u['slug'] === 'citizen' ? '/login.php' : '/auth.php');
        $copy = [
            'approved' => ['Your CDF account has been approved', 'Account approved',
                "Your {$u['role_name']} account has been approved. You can now sign in.", $login],
            'reactivated' => ['Your CDF account has been restored', 'Account restored',
                'Your account access has been restored. You can sign in again.', $login],
            'suspended' => ['Your CDF account has been suspended', 'Account suspended',
                'Your account has been suspended and you have been signed out. Please contact the district office if you think this is a mistake.', null],
        ][$event] ?? null;
        if (!$copy) {
            return;
        }

        notify_simple($userId, (string)$u['email'], (string)$u['full_name'], 'account_' . $event,
            $copy[0], $copy[1], $copy[2], [], $copy[3], 'Sign in');
    } catch (Throwable $e) {
        error_log('notify_account_status failed: ' . $e->getMessage());
    }
}

/** MP decision -> the council account that submitted the project. */
function notify_project_decision(PDO $pdo, int $projectId, string $status, string $reason, string $comment, string $mpName): void
{
    try {
        $st = $pdo->prepare('SELECT p.title, p.reference_no, u.id AS uid, u.full_name, u.email
                               FROM projects p JOIN users u ON u.id = p.submitted_by WHERE p.id = ?');
        $st->execute([$projectId]);
        $p = $st->fetch();
        if (!$p) {
            return;
        }

        $label = ['accepted' => 'approved', 'denied' => 'rejected', 'review' => 'put under review',
            'progress' => 'moved to implementation', 'submitted' => 'returned to submitted'][$status] ?? $status;

        notify_simple((int)$p['uid'], (string)$p['email'], (string)$p['full_name'], 'proposal_' . $status,
            'Proposal ' . $label . ': ' . $p['title'], 'Proposal ' . $label,
            "The Member of Parliament has {$label} your proposal.",
            ['Project' => $p['title'], 'Reference' => $p['reference_no'], 'Decision' => ucfirst($label),
                'Reason' => $reason, 'Comment' => $comment, 'Decided by' => $mpName],
            APP_URL . '/vdc/projects.php', 'Open my projects');
    } catch (Throwable $e) {
        error_log('notify_project_decision failed: ' . $e->getMessage());
    }
}

/** DDO who assigned the project; falls back to every active DDO. */
function notify_ddo_recipients(PDO $pdo, int $projectId): array
{
    $st = $pdo->prepare("SELECT u.id, u.full_name, u.email FROM assignments a
                           JOIN users u ON u.id = a.assigned_by
                          WHERE a.project_id = ? AND u.status = 'active'");
    $st->execute([$projectId]);
    $rows = $st->fetchAll();
    if (!$rows) {
        $rows = $pdo->query("SELECT u.id, u.full_name, u.email FROM users u JOIN roles r ON r.id = u.role_id
                              WHERE r.slug = 'ddo' AND u.status = 'active'")->fetchAll();
    }
    return $rows;
}

/** Contractor posted a progress update -> DDO. */
function notify_progress_update(PDO $pdo, int $projectId, int $percentage, string $note, string $contractorName, string $date): void
{
    try {
        $t = $pdo->prepare('SELECT title, reference_no FROM projects WHERE id = ?');
        $t->execute([$projectId]);
        $p = $t->fetch();
        if (!$p) {
            return;
        }

        foreach (notify_ddo_recipients($pdo, $projectId) as $d) {
            notify_simple((int)$d['id'], (string)$d['email'], (string)$d['full_name'], 'progress_update',
                'Progress update (' . $percentage . '%): ' . $p['title'], 'New progress update',
                "{$contractorName} has posted a progress update.",
                ['Project' => $p['title'], 'Reference' => $p['reference_no'], 'Progress' => $percentage . '%',
                    'Date' => $date, 'Note' => $note],
                APP_URL . '/ddo/project.php?id=' . $projectId, 'Open project');
        }
    } catch (Throwable $e) {
        error_log('notify_progress_update failed: ' . $e->getMessage());
    }
}

/** Project reached completion -> MP of the constituency, council submitter and DDO. */
function notify_project_completed(PDO $pdo, int $projectId): void
{
    try {
        $st = $pdo->prepare('SELECT title, reference_no, constituency_id, submitted_by FROM projects WHERE id = ?');
        $st->execute([$projectId]);
        $p = $st->fetch();
        if (!$p) {
            return;
        }

        $to = [];   // keyed by email so nobody gets it twice: [email => [id, name, link]]

        $mp = $pdo->prepare("SELECT u.id, u.full_name, u.email FROM mp_profiles m JOIN users u ON u.id = m.user_id
                              WHERE m.constituency_id = ? AND u.status = 'active'");
        $mp->execute([(int)$p['constituency_id']]);
        foreach ($mp->fetchAll() as $u) {
            $to[$u['email']] = [(int)$u['id'], $u['full_name'], APP_URL . '/mp/review.php?id=' . $projectId];
        }
        if ($p['submitted_by']) {
            $c = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND status = 'active'");
            $c->execute([(int)$p['submitted_by']]);
            if ($u = $c->fetch()) {
                $to[$u['email']] = [(int)$u['id'], $u['full_name'], APP_URL . '/vdc/projects.php'];
            }
        }
        foreach (notify_ddo_recipients($pdo, $projectId) as $u) {
            $to[$u['email']] = [(int)$u['id'], $u['full_name'], APP_URL . '/ddo/project.php?id=' . $projectId];
        }

        foreach ($to as $email => [$id, $name, $link]) {
            notify_simple($id, (string)$email, (string)$name, 'project_completed',
                'Project completed: ' . $p['title'], 'Project completed',
                'This Constituency Development Fund project has reached 100% and is marked completed.',
                ['Project' => $p['title'], 'Reference' => $p['reference_no']], $link, 'Open project');
        }
    } catch (Throwable $e) {
        error_log('notify_project_completed failed: ' . $e->getMessage());
    }
}

function notify_password_reset(?int $userId, string $email, string $name, string $link): bool
{
    return notify_simple($userId, $email, $name, 'password_reset', 'Reset your CDF password', 'Password reset',
        'We received a request to reset your password. The link below works for 1 hour. If you did not ask for this, ignore this email and your password stays the same.',
        [], $link, 'Choose a new password');
}
