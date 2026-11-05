<?php
	// Google redirects here. Requires an admin session; state is HMAC-signed and time-limited.
	include('../../../core/inc/api.php');
	$API = new PerchAPI(1.0, 'hello_found');
	require __DIR__ . '/_admin.php';

	$account_id = HF_Gsc::verify_state($_GET['state'] ?? '');
	if (!$account_id || !HF_Account::find($account_id)) { http_response_code(400); exit('Invalid or expired state. Start the connection again from the client page.'); }

	$back = ['id' => $account_id, 'tab' => 'gsc'];
	if (!empty($_GET['error']) || empty($_GET['code'])) {
		hf_flash('Google access was not granted.', 'error');
		PerchUtil::redirect(hf_admin_url('client.php', $back));
	}

	if (HF_Gsc::connect($account_id, (string)$_GET['code'], hf_admin_name())) {
		$domain = HF_Account::domain($account_id);
		hf_flash($domain && $domain['gsc_site_url']
			? 'Search Console connected: ' . $domain['gsc_site_url']
			: 'Connected, but no matching property was found for this domain. Check the Google account has access to it.', $domain && $domain['gsc_site_url'] ? 'success' : 'error');
	} else {
		hf_flash('Could not complete the Google connection. Check the OAuth settings and try again.', 'error');
	}
	PerchUtil::redirect(hf_admin_url('client.php', $back));
