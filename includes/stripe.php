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

    $r = stripe_request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
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

    $projectId = (int) $pay['project_id'];
    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE payments SET status = 'paid', paid_at = NOW(), stripe_payment_intent = ? WHERE id = ?")
            ->execute([(string) ($s['payment_intent'] ?? ''), $pay['id']]);

        $has = $pdo->prepare('SELECT id FROM allocations WHERE project_id = ?');
        $has->execute([$projectId]);
        if (!$has->fetch()) {
            $info = $pdo->prepare(
                'SELECT p.title, q.breakdown FROM projects p
                   LEFT JOIN quotations q ON q.project_id = p.id WHERE p.id = ?'
            );
            $info->execute([$projectId]);
            $row   = $info->fetch() ?: ['title' => '', 'breakdown' => ''];
            $scope = mb_substr('Contract price paid via Stripe for: ' . $row['title'], 0, 255);
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

    stripe_notify_paid($pdo, $projectId, (float) $pay['amount_mk']);
    return ['ok' => true, 'project_id' => $projectId];
}

/** Tells the contractor the funds have been allocated. Never throws. */
function stripe_notify_paid(PDO $pdo, int $projectId, float $amountMk): void
{
    try {
        $st = $pdo->prepare(
            'SELECT p.title, p.reference_no, u.id AS uid, u.full_name, u.email
               FROM quotations q
               JOIN projects p ON p.id = q.project_id
               JOIN users u ON u.id = q.contractor_id
              WHERE q.project_id = ?'
        );
        $st->execute([$projectId]);
        $r = $st->fetch();
        if (!$r) {
            return;
        }
        $amount = 'MK ' . number_format($amountMk, 2);
        $link   = APP_URL . '/contractor/project.php?id=' . $projectId;
        $text   = "Hello {$r['full_name']},\n\nFunds of {$amount} have been allocated for {$r['title']} ({$r['reference_no']}).\nYou can now start work.\n\n{$link}\n";
        $html   = email_layout(
            'Funds allocated',
            '<p style="margin:0 0 4px">Hello ' . mail_esc($r['full_name']) . ',</p>'
            . '<p style="margin:0">Funds have been allocated for your project. You can now start work.</p>'
            . email_details(['Project' => $r['title'], 'Reference' => $r['reference_no'], 'Amount' => $amount])
            . email_button($link, 'Open project')
        );
        send_notification((int) $r['uid'], (string) $r['email'], 'funds_allocated', 'Funds allocated: ' . $r['title'], $text, $html);
    } catch (Throwable $e) {
        error_log('stripe_notify_paid failed: ' . $e->getMessage());
    }
}
