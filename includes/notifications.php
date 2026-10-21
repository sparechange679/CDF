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
