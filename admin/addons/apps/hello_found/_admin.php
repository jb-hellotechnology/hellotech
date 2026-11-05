<?php
/** Shared helpers for hello.found control-panel pages. Included after core/inc/api.php. */
require_once __DIR__ . '/bootstrap.php';

if (!$CurrentUser->logged_in() || !$CurrentUser->has_priv('hello_found')) { PerchUtil::redirect(PERCH_LOGINPATH); }

function hf_admin_name() { global $CurrentUser; return (string)($CurrentUser->userUsername() ?: 'admin'); }

function hf_admin_subnav($CurrentUser)
{
	PerchUI::set_subnav([
		['page' => ['hello_found', 'hello_found/client'], 'label' => 'Clients'],
		['page' => ['hello_found/settings'], 'label' => 'Settings'],
	], $CurrentUser);
}

function hf_flash($msg, $type = 'success') { PerchSession::set('hf_flash', json_encode([$type, $msg])); }
function hf_flash_html()
{
	$f = PerchSession::get('hf_flash');
	if (!$f) return '';
	PerchSession::delete('hf_flash');
	[$type, $msg] = json_decode($f, true);
	return '<div class="notification notification-' . ($type === 'success' ? 'success' : 'alert') . '"><div class="notification-message">' . hf_e($msg) . '</div></div>';
}

function hf_admin_url($file, array $q = [])
{
	return PERCH_LOGINPATH . '/addons/apps/hello_found/' . $file . ($q ? '?' . http_build_query($q) : '');
}

/** Require a valid CSRF-protected POST for an action endpoint. */
function hf_require_post()
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hf_csrf_ok()) { http_response_code(400); exit('Bad request'); }
}
