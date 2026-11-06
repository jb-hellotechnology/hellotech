<?php
	// Runs ONE DataForSEO check and answers with JSON (admin only, POST + CSRF).
	// The client page calls this once per keyword / prompt-engine so no single request nears a PHP time limit.
	include('../../../../core/inc/api.php');
	$API = new PerchAPI(1.0, 'hello_found');
	require __DIR__ . '/../_admin.php';

	header('Content-Type: application/json');
	function hf_json($ok, $msg = '') { echo json_encode(['ok' => $ok, 'error' => $ok ? null : $msg]); exit; }

	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hf_csrf_ok()) { http_response_code(400); hf_json(false, 'Session expired - reload the page and try again.'); }
	@set_time_limit(180);

	$id = (int)($_POST['account_id'] ?? 0);
	$domain = HF_Account::find($id) ? HF_Account::domain($id) : null;
	if (!$domain) hf_json(false, 'Client or domain not found.');

	if (($_POST['type'] ?? '') === 'ai') {
		$p = HF_Db::row('SELECT * FROM ' . HF_Db::t('prompts') . ' WHERE id=' . (int)($_POST['prompt_id'] ?? 0) . ' AND account_id=' . $id);
		$engine = (string)($_POST['engine'] ?? '');
		if (!$p || !isset(HF_Ai::engines()[$engine])) hf_json(false, 'Unknown prompt or engine.');
		$err = HF_Ai::check($p, $engine, $domain['domain']);
	} else {
		$kw = HF_Keyword::find((int)($_POST['keyword_id'] ?? 0), $id);
		if (!$kw) hf_json(false, 'Keyword not found.');
		$err = HF_Rank::check($kw, $domain);
	}
	$err ? hf_json(false, $err) : hf_json(true);
