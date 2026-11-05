<?php
/**
 * Shared bootstrap: autoloader + config + small helpers.
 * Included by admin.php (control panel), runtime.php (public pages),
 * webhook.php and the cron script.
 */

if (defined('HELLOFOUND_BOOTSTRAPPED')) return;
define('HELLOFOUND_BOOTSTRAPPED', true);

define('HELLOFOUND_PATH', __DIR__);

spl_autoload_register(function ($class) {
	if (strpos($class, 'HF_') === 0) {
		$file = HELLOFOUND_PATH . '/lib/' . $class . '.class.php';
		if (is_file($file)) { require_once $file; return true; }
	}
	return false;
});

// Secrets live in config.php (gitignored). Copy config.sample.php to config.php.
if (is_file(HELLOFOUND_PATH . '/config.php')) require_once HELLOFOUND_PATH . '/config.php';

// Defaults for anything config.php did not define.
$hf_defaults = [
	'HELLOFOUND_SITE_URL'     => 'https://hellotechnology.co.uk',
	'HELLOFOUND_BASE'         => '/found',          // public path of the customer pages
	'HELLOFOUND_LOGIN_URL'    => '/members/login',   // Perch Members login page
	'HELLOFOUND_RESET_URL'    => '/members/reset',   // Perch Members password-reset page
	'HELLOFOUND_MEMBER_TAG'   => 'hello_found_customers',
	'HELLOFOUND_MAX_KEYWORDS' => 20,
	'HELLOFOUND_NOTIFY_EMAIL' => 'jack@hellotechnology.co.uk',
];
foreach ($hf_defaults as $k => $v) if (!defined($k)) define($k, $v);
unset($hf_defaults);

function hf_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function hf_url($path = '') { return rtrim(HELLOFOUND_BASE, '/') . '/' . ltrim($path, '/'); }

function hf_abs_url($path = '') { return rtrim(HELLOFOUND_SITE_URL, '/') . hf_url($path); }

function hf_redirect($url) {
	header('Location: ' . $url, true, 302);
	exit;
}

/** Session-backed CSRF token (uses Perch's session wrapper). */
function hf_csrf_token() {
	$t = PerchSession::get('hf_csrf');
	if (!$t) { $t = bin2hex(random_bytes(16)); PerchSession::set('hf_csrf', $t); }
	return $t;
}
function hf_csrf_field() { return '<input type="hidden" name="hf_token" value="' . hf_e(hf_csrf_token()) . '">'; }
function hf_csrf_ok() {
	$t = PerchSession::get('hf_csrf');
	return $t && isset($_POST['hf_token']) && hash_equals($t, (string)$_POST['hf_token']);
}

/** Never log secrets: log a message only. */
function hf_log($msg) { error_log('[hello_found] ' . $msg); }
