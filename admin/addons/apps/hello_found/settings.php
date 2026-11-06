<?php
	include('../../../core/inc/api.php');
	$API  = new PerchAPI(1.0, 'hello_found');
	$HTML = $API->get('HTML');   // required by Perch's sidebar layout
	$Lang = $API->get('Lang');
	require __DIR__ . '/_admin.php';
	hf_admin_subnav($CurrentUser);
	$Perch->page_title = $Lang->get('hello.found settings');

	$consts = [
		'HELLOFOUND_STRIPE_SECRET' => 'Stripe secret key', 'HELLOFOUND_STRIPE_WEBHOOK_SECRET' => 'Stripe webhook secret',
		'HELLOFOUND_STRIPE_PRICE_ID' => 'Stripe price ID (£99/month)', 'HELLOFOUND_GSC_CLIENT_ID' => 'Google OAuth client ID',
		'HELLOFOUND_GSC_CLIENT_SECRET' => 'Google OAuth client secret', 'HELLOFOUND_FATHOM_API_KEY' => 'Fathom API key',
		'HELLOFOUND_ENCRYPTION_KEY' => 'Encryption key (64 hex chars)',
		'HELLOFOUND_DFS_LOGIN' => 'DataForSEO API login', 'HELLOFOUND_DFS_PASSWORD' => 'DataForSEO API password',
	];
	$key_ok = defined('HELLOFOUND_ENCRYPTION_KEY') && preg_match('/^[0-9a-f]{64}$/i', HELLOFOUND_ENCRYPTION_KEY);

	// Optional: list the models DataForSEO currently offers per engine (free call).
	$models = null;
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		hf_require_post();
		$models = [];
		foreach (HF_Ai::engines_all() as $k => $e) $models[$k] = HF_Ai::list_models($k);
	}

	include(PERCH_CORE . '/inc/top.php');
?>
<h1>hello.found settings</h1>
<p>Secrets live in <code>admin/addons/apps/hello_found/config.php</code> (gitignored; copy <code>config.sample.php</code>). Values are never shown here.</p>
<table class="d">
	<thead><tr><th>Setting</th><th>Constant</th><th>Status</th></tr></thead>
	<tbody>
	<?php foreach ($consts as $c => $label):
		$ok = defined($c) && constant($c) !== '' && (strpos((string)constant($c), '...') === false);
		if ($c === 'HELLOFOUND_ENCRYPTION_KEY') $ok = $ok && $key_ok; ?>
		<tr><td><?= hf_e($label) ?></td><td><code><?= hf_e($c) ?></code></td><td><?= $ok ? 'Set' : '<b>Missing</b>' ?></td></tr>
	<?php endforeach; ?>
		<tr><td>Notification email</td><td><code>HELLOFOUND_NOTIFY_EMAIL</code></td><td><?= hf_e(HELLOFOUND_NOTIFY_EMAIL) ?></td></tr>
		<tr><td>Max active keywords</td><td><code>HELLOFOUND_MAX_KEYWORDS</code></td><td><?= (int)HELLOFOUND_MAX_KEYWORDS ?></td></tr>
		<tr><td>Perch Members</td><td></td><td><?= HF_Members::available() ? 'Installed' : '<b>Not detected</b>' ?></td></tr>
	</tbody>
</table>
<h2>AI engines and models</h2>
<table class="d">
	<thead><tr><th>Engine</th><th>Model in use</th></tr></thead>
	<tbody>
	<?php foreach (HF_Ai::engines() as $k => $e): ?>
		<tr><td><?= hf_e($e['label']) ?></td><td><code><?= hf_e(HF_Ai::model($k)) ?></code></td></tr>
	<?php endforeach; ?>
	</tbody>
</table>
<form method="post" action=""><?= hf_csrf_field() ?><input type="submit" class="button button-simple" value="List models available from DataForSEO"></form>
<?php if ($models !== null): foreach ($models as $k => [$names, $merr]): ?>
	<p><b><?= hf_e(HF_Ai::engines_all()[$k]['label']) ?>:</b> <?= $merr ? '<em>' . hf_e($merr) . '</em>' : hf_e(implode(', ', $names)) ?></p>
<?php endforeach; ?>
	<p><small>To change a model, add to config.php: <code>define('HELLOFOUND_AI_MODELS', ['chat_gpt' =&gt; '...', 'gemini' =&gt; '...', 'perplexity' =&gt; '...']);</code></small></p>
<?php endif; ?>
<h2>URLs to register</h2>
<table class="d">
	<tr><td>Stripe webhook endpoint</td><td><code><?= hf_e(rtrim(HELLOFOUND_SITE_URL, '/') . hf_admin_url('webhook.php')) ?></code></td></tr>
	<tr><td>Google OAuth redirect URI</td><td><code><?= hf_e(HF_Gsc::redirect_uri()) ?></code></td></tr>
</table>
<p>Webhook events: <code>checkout.session.completed</code>, <code>customer.subscription.updated</code>, <code>customer.subscription.deleted</code>, <code>invoice.payment_succeeded</code>, <code>invoice.payment_failed</code>.</p>
<?php include(PERCH_CORE . '/inc/btm.php');
