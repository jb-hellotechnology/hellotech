<?php
	include('../../../../core/inc/api.php');
	$API = new PerchAPI(1.0, 'hello_found');
	require __DIR__ . '/../_admin.php';
	hf_require_post();

	$id = (int)($_POST['account_id'] ?? 0);
	if (!HF_Account::find($id)) { http_response_code(404); exit; }

	$err = HF_Fathom::create_site_for($id);
	hf_flash($err ?: 'Fathom site created.', $err ? 'error' : 'success');
	PerchUtil::redirect(hf_admin_url('client.php', ['id' => $id, 'tab' => 'fathom']));
