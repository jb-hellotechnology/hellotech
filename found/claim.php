<?php
/*
 * /found/ lead capture — self-contained.
 * Validates the "Claim your spot" form, pushes the lead into Brevo, and keeps
 * a web-denied CSV failsafe (found/_data/) that only fires if Brevo is
 * unreachable or not configured, so a lead is never silently lost.
 *
 * Config lives in found/config.php (gitignored) — copy found/config.sample.php.
 */

function found_config(): array
{
    $cfg = is_file(__DIR__ . '/config.php') ? (require __DIR__ . '/config.php') : [];
    return array_merge([
        'BREVO_API_KEY' => getenv('BREVO_API_KEY') ?: '',
        'BREVO_LIST_ID' => (int) (getenv('BREVO_LIST_ID') ?: 0),
        'NOTIFY_TO'     => 'hi@hellotechnology.co.uk',
        'NOTIFY_FROM'   => 'website@hellotechnology.co.uk',
    ], is_array($cfg) ? $cfg : []);
}

/** Escape helper for the template. */
function fe($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function found_brevo(string $path, array $payload, string $key): array
{
    if (!function_exists('curl_init')) return [0, 'php-curl not available'];
    $ch = curl_init('https://api.brevo.com/v3' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['api-key: ' . $key, 'accept: application/json', 'content-type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);
    $resp = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);
    $msg  = '';
    if (!in_array($http, [200, 201, 204], true)) {
        $body = $resp ? json_decode($resp, true) : null;
        $msg  = $cerr ?: ($body['message'] ?? ('HTTP ' . $http));
    }
    return [$http, $msg];
}

function found_fallback(array $v, string $why): bool
{
    $dir = __DIR__ . '/_data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    $file = $dir . '/leads.csv';
    $new  = !is_file($file);
    $fh   = @fopen($file, 'a');
    if (!$fh) return false;
    $ok = false;
    if (flock($fh, LOCK_EX)) {
        if ($new) fwrite($fh, "created_at,name,email,business,town,consent,ip,reason\n");
        fputcsv($fh, [gmdate('c'), $v['name'], $v['email'], $v['business'], $v['town'], $v['consent'] ? 'yes' : 'no', $v['ip'], $why]);
        flock($fh, LOCK_UN);
        $ok = true;
    }
    fclose($fh);
    return $ok;
}

/** Store a lead in Brevo (and notify Jack); fall back to CSV on any failure. Returns true if captured. */
function found_store(array $v): bool
{
    $c   = found_config();
    $key = $c['BREVO_API_KEY'];
    $list = (int) $c['BREVO_LIST_ID'];

    if ($key && $list) {
        [$http, $err] = found_brevo('/contacts', [
            'email'         => $v['email'],
            'attributes'    => ['FIRSTNAME' => $v['name'], 'BUSINESS' => $v['business'], 'TOWN' => $v['town']],
            'listIds'       => [$list],
            'updateEnabled' => true,
        ], $key);

        if ($http && !$err) {
            // best-effort notification to Jack
            found_brevo('/smtp/email', [
                'sender'      => ['name' => 'found website', 'email' => $c['NOTIFY_FROM']],
                'to'          => [['email' => $c['NOTIFY_TO']]],
                'replyTo'     => ['email' => $v['email'], 'name' => $v['name']],
                'subject'     => 'New found lead: ' . $v['business'],
                'textContent' => "Name: {$v['name']}\nEmail: {$v['email']}\nBusiness: {$v['business']}\nTown: {$v['town']}\n",
            ], $key);
            return true;
        }
        return found_fallback($v, $err ?: 'brevo-error');
    }

    return found_fallback($v, 'brevo-not-configured');
}

/** Handle a POST to the form. Returns [claimed(bool), errors(array), values(array)]. */
function found_handle_claim(): array
{
    $claimed = false;
    $errors  = [];
    $v = ['name' => '', 'email' => '', 'business' => '', 'town' => ''];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $tooFast = isset($_POST['ts']) && (time() - (int) $_POST['ts']) < 2;
        if (!empty($_POST['website'] ?? '') || $tooFast) {
            $claimed = true;                                   // honeypot / time-trap: silently accept
        } else {
            foreach (['name', 'email', 'business', 'town'] as $f) $v[$f] = trim((string) ($_POST[$f] ?? ''));
            if ($v['name'] === '')     $errors['name'] = 'Please tell me your name.';
            if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
            if ($v['business'] === '') $errors['business'] = "What's your business called?";
            if ($v['town'] === '')     $errors['town'] = 'Which town are you in?';
            if (empty($_POST['consent'] ?? '')) $errors['consent'] = 'Please tick the box so I can get in touch.';
            if (!$errors) {
                $captured = found_store($v + ['consent' => true, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                if ($captured) $claimed = true;
                else $errors['form'] = 'Sorry — something went wrong. Please email hi@hellotechnology.co.uk.';
            }
        }
    }

    return [$claimed, $errors, $v];
}
