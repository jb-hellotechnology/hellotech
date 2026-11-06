<?php
	// Runs DataForSEO checks for one account right now (admin only, POST + CSRF).
	include('../../../../core/inc/api.php');
	$API = new PerchAPI(1.0, 'hello_found');
	require __DIR__ . '/../_admin.php';
	hf_require_post();

	$id = (int)($_POST['account_id'] ?? 0);
	$type = ($_POST['type'] ?? '') === 'ai' ? 'ai' : 'ranks';
	if (!HF_Account::find($id)) { http_response_code(404); exit; }

	@set_time_limit(290);
	ignore_user_abort(true);

	if ($type === 'ranks') {
		[$n, $errors] = HF_Rank::run_account($id, true);
		$what = "$n keyword" . ($n === 1 ? '' : 's') . ' checked';
		$tab = 'keywords';
	} else {
		[$n, $errors] = HF_Ai::run_account($id, true);
		$what = "$n AI check" . ($n === 1 ? '' : 's') . ' completed';
		$tab = 'prompts';
	}
	$msg = $what . ($errors ? '. Problems: ' . implode(' | ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' (+' . (count($errors) - 3) . ' more)' : '') : '.');
	hf_flash($msg, $errors ? 'error' : 'success');
	PerchUtil::redirect(hf_admin_url('client.php', ['id' => $id, 'tab' => $tab]));
