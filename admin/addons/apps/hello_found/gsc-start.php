<?php
	// Admin clicks "Connect Search Console" -> redirect to Google's consent screen.
	include('../../../core/inc/api.php');
	$API = new PerchAPI(1.0, 'hello_found');
	require __DIR__ . '/_admin.php';
	hf_require_post();

	$id = (int)($_POST['account_id'] ?? 0);
	if (!HF_Account::find($id)) { http_response_code(404); exit; }
	if (!defined('HELLOFOUND_GSC_CLIENT_ID') || !defined('HELLOFOUND_ENCRYPTION_KEY')) {
		hf_flash('Google OAuth client ID / encryption key are not configured (see Settings).', 'error');
		PerchUtil::redirect(hf_admin_url('client.php', ['id' => $id, 'tab' => 'gsc']));
	}
	header('Location: ' . HF_Gsc::auth_url($id));
	exit;
