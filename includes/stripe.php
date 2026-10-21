<?php
// Stripe Checkout helpers (plain cURL, no Composer package needed).
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/notifications.php';   // also loads config/mail.php (APP_URL)
require_once __DIR__ . '/../config/stripe.php';

/** Calls the Stripe REST API. Returns ['ok'=>bool, 'data'=>array, 'error'=>string]. */
function stripe_request(string $method, string $path, array $params = []): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'The PHP cURL extension is not enabled.'];
    }
    $url = 'https://api.stripe.com/v1' . $path;
    $ch  = curl_init();
    $opt = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
        CURLOPT_TIMEOUT        => 25,
    ];
    if (STRIPE_CA_BUNDLE !== '' && is_file(STRIPE_CA_BUNDLE)) {
        $opt[CURLOPT_CAINFO] = STRIPE_CA_BUNDLE;
    }
    if (strtoupper($method) === 'POST') {
        $opt[CURLOPT_POST]       = true;
        $opt[CURLOPT_POSTFIELDS] = http_build_query($params);
    } elseif ($params) {
        $url .= '?' . http_build_query($params);
    }
    $opt[CURLOPT_URL] = $url;
    curl_setopt_array($ch, $opt);

    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'error' => 'Could not reach Stripe: ' . $err];
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Unexpected response from Stripe.'];
    }
    if ($code >= 400) {
        return ['ok' => false, 'error' => $data['error']['message'] ?? 'Stripe rejected the request.'];
    }
    return ['ok' => true, 'data' => $data];
}

/** Amount to charge in the Stripe currency, from a kwacha amount. */
function stripe_charge_amount(float $mk): float
{
    return STRIPE_CURRENCY === 'mwk' ? $mk : round($mk / STRIPE_MWK_PER_UNIT, 2);
}

/**
 * Called after the DDO comes back from Stripe (or when the quotation page loads while a
 * payment is still pending). If Stripe says the session is paid, the payment is marked paid
 * and the fund allocation row is created. Safe to call more than once.
 */
function stripe_finalize_session(PDO $pdo, string $sessionId): array
{
    $st = $pdo->prepare('SELECT * FROM payments WHERE stripe_session_id = ?');
    $st->execute([$sessionId]);
    $pay = $st->fetch();
    if (!$pay) {
        return ['ok' => false, 'error' => 'Unknown payment session.'];
    }
    if ($pay['status'] === 'paid') {
        return ['ok' => true, 'already' => true, 'project_id' => (int) $pay['project_id']];
    }

    $r = stripe_request('GET', '/checkout/sessions/' . rawurlencode($sessionId), [
        'expand' => ['payment_intent.latest_charge'],
    ]);
    if (!$r['ok']) {
        return ['ok' => false, 'error' => $r['error']];
    }
    $s = $r['data'];

    if (($s['payment_status'] ?? '') !== 'paid') {
        if (($s['status'] ?? '') === 'expired') {
            $pdo->prepare("UPDATE payments SET status = 'expired' WHERE id = ? AND status = 'pending'")->execute([$pay['id']]);
        }
        return ['ok' => false, 'pending' => true, 'error' => 'Payment has not been completed.'];
    }
    if ((string) ($s['metadata']['project_id'] ?? '') !== (string) $pay['project_id']) {
        return ['ok' => false, 'error' => 'Payment does not match this project.'];
    }

    $pi         = $s['payment_intent'] ?? null;
    $piId       = is_array($pi) ? (string) ($pi['id'] ?? '') : (string) $pi;
    $receiptUrl = (is_array($pi) && is_array($pi['latest_charge'] ?? null))
        ? (string) ($pi['latest_charge']['receipt_url'] ?? '') : '';

    $projectId = (int) $pay['project_id'];
    try {
        $pdo->beginTransaction();

        // Only the first request to get here flips the row; it alone sends the emails.
        $upd = $pdo->prepare("UPDATE payments SET status = 'paid', paid_at = NOW(), stripe_payment_intent = ? WHERE id = ? AND status <> 'paid'");
        $upd->execute([$piId, $pay['id']]);
        if ($upd->rowCount() !== 1) {
            $pdo->rollBack();
            return ['ok' => true, 'already' => true, 'project_id' => $projectId];
        }

        $has = $pdo->prepare('SELECT id FROM allocations WHERE project_id = ?');
        $has->execute([$projectId]);
        if (!$has->fetch()) {
            $info = $pdo->prepare('SELECT title FROM projects WHERE id = ?');
            $info->execute([$projectId]);
            $title = (string) $info->fetchColumn();
            $scope = mb_substr('Contract price paid via Stripe for: ' . $title, 0, 255);
            $ref   = sprintf('ALLOC-%s-%04d-%s', date('Y'), $projectId, strtoupper(bin2hex(random_bytes(2))));

            $pdo->prepare(
                'INSERT INTO allocations (project_id, amount, source, ddo_user_id, reference, allocated_on, scope)
                 VALUES (?, ?, ?, ?, ?, CURDATE(), ?)'
            )->execute([$projectId, $pay['amount_mk'], 'Stripe payment (test mode)', $pay['user_id'], $ref, $scope]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('stripe finalize failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Payment was received but could not be recorded. Contact support.'];
    }

    stripe_send_emails($pdo, $pay, $piId, $receiptUrl);
    return ['ok' => true, 'project_id' => $projectId];
}

/** Receipt to the DDO who paid, "funds allocated" to the contractor. Never throws. */
function stripe_send_emails(PDO $pdo, array $pay, string $piId, string $receiptUrl): void
{
    try {
        $projectId = (int) $pay['project_id'];
        $st = $pdo->prepare(
            'SELECT p.title, p.reference_no,
                    cu.id AS c_id, cu.full_name AS c_name, cu.email AS c_email, kp.company_name,
                    a.reference AS alloc_ref
               FROM projects p
          LEFT JOIN quotations q ON q.project_id = p.id
          LEFT JOIN users cu ON cu.id = q.contractor_id
          LEFT JOIN contractor_profiles kp ON kp.user_id = cu.id
          LEFT JOIN allocations a ON a.project_id = p.id
              WHERE p.id = ?'
        );
        $st->execute([$projectId]);
        $r = $st->fetch();
        if (!$r) {
            return;
        }

        $payer = $pdo->prepare('SELECT id, full_name, email FROM users WHERE id = ?');
        $payer->execute([(int) $pay['user_id']]);
        $ddo = $payer->fetch();

        $amountMk   = 'MK ' . number_format((float) $pay['amount_mk'], 2);
        $charged    = strtoupper($pay['currency']) . ' ' . number_format((float) $pay['charge_amount'], 2);
        $receiptNo  = sprintf('RCT-%s-%05d', date('Y'), (int) $pay['id']);
        $paidOn     = date('d M Y, H:i');
        $contractor = $r['company_name'] ?: $r['c_name'];

        /* ---- Receipt to the DDO ---- */
        if ($ddo) {
            $rows = [
                'Receipt no.'        => $receiptNo,
                'Date paid'          => $paidOn,
                'Project'            => $r['title'],
                'Project reference'  => $r['reference_no'],
                'Paid to (contractor)' => $contractor,
                'Contract amount'    => $amountMk,
                'Amount charged'     => $charged,
                'Fund allocation ref' => $r['alloc_ref'] ?? '',
                'Stripe payment ID'  => $piId,
            ];
            $text = "Hello {$ddo['full_name']},\n\nYour payment was received. Receipt {$receiptNo}\n\n";
            foreach ($rows as $k => $v) {
                if ($v !== '') { $text .= "$k: $v\n"; }
            }
            if ($receiptUrl !== '') { $text .= "\nCard receipt from Stripe:\n$receiptUrl\n"; }

            $html = email_layout(
                'Payment receipt',
                '<p style="margin:0 0 4px">Hello ' . mail_esc($ddo['full_name']) . ',</p>'
                . '<p style="margin:0">Your payment was received and the funds have been allocated to the project.</p>'
                . email_details($rows)
                . ($receiptUrl !== '' ? email_button($receiptUrl, 'View Stripe card receipt') : '')
                . email_button(APP_URL . '/ddo/quotation.php?project=' . $projectId, 'Open project quotation')
            );
            send_notification((int) $ddo['id'], (string) $ddo['email'], 'payment_receipt', 'Payment receipt ' . $receiptNo . ': ' . $r['title'], $text, $html);
        }

        /* ---- Funds allocated notice to the contractor ---- */
        if (!empty($r['c_email'])) {
            $link = APP_URL . '/contractor/project.php?id=' . $projectId;
            $text = "Hello {$r['c_name']},\n\nFunds of {$amountMk} have been allocated for {$r['title']} ({$r['reference_no']}).\nYou can now start work.\n\n{$link}\n";
            $html = email_layout(
                'Funds allocated',
                '<p style="margin:0 0 4px">Hello ' . mail_esc($r['c_name']) . ',</p>'
                . '<p style="margin:0">Funds have been allocated for your project. You can now start work.</p>'
                . email_details(['Project' => $r['title'], 'Reference' => $r['reference_no'], 'Amount' => $amountMk])
                . email_button($link, 'Open project')
            );
            send_notification((int) $r['c_id'], (string) $r['c_email'], 'funds_allocated', 'Funds allocated: ' . $r['title'], $text, $html);
        }
    } catch (Throwable $e) {
        error_log('stripe_send_emails failed: ' . $e->getMessage());
    }
}