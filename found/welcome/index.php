<?php
require __DIR__ . '/../_hf.php';

// The customer is already logged in (they registered first). The webhook normally activates the
// account; we also verify the Checkout Session directly so a slow webhook never leaves them stuck.
if (!HF_Members::logged_in()) hf_redirect(HELLOFOUND_LOGIN_URL . '?r=' . rawurlencode(hf_url('dashboard/')));

$account = HF_Account::by_member(HF_Members::current_id());
if (!$account) hf_redirect(hf_url('subscribe/'));

if (!HF_Account::is_active($account) && isset($_GET['session_id'])) {
	$session = HF_Stripe::retrieve_session((string)$_GET['session_id']);
	// Only accept a session that belongs to THIS account.
	$ref = $session['client_reference_id'] ?? null;
	if ($session && (string)$ref === (string)$account['id']) {
		HF_Account::provision_from_session($session);
		$account = HF_Account::find($account['id']);
	}
}

if (HF_Account::is_active($account)) hf_redirect(hf_url($account['onboarding_complete'] ? 'dashboard/' : 'onboarding/'));

// Still waiting for Stripe: retry a few times, then give up gracefully.
$tries = min(10, (int)($_GET['n'] ?? 0) + 1);
$next = hf_url('welcome/') . '?' . http_build_query(['session_id' => $_GET['session_id'] ?? '', 'n' => $tries]);
hf_page_start('Confirming payment');
if ($tries < 10) echo '<meta http-equiv="refresh" content="3;url=' . hf_e($next) . '">';
?>
<div class="hf-card">
<?php if ($tries < 10): ?>
  <h1 class="h2">Confirming your payment…</h1>
  <p>This usually takes a few seconds. This page will refresh by itself.</p>
<?php else: ?>
  <h1 class="h2">Still confirming</h1>
  <p>We haven't received confirmation from Stripe yet. If you were charged, your account will activate within a few minutes and you'll see it on your dashboard. Otherwise contact <a href="mailto:<?= hf_e(HELLOFOUND_NOTIFY_EMAIL) ?>"><?= hf_e(HELLOFOUND_NOTIFY_EMAIL) ?></a>.</p>
  <p><a class="btn btn--primary" href="<?= hf_e(hf_url('welcome/') . '?session_id=' . rawurlencode($_GET['session_id'] ?? '')) ?>">Check again</a></p>
<?php endif; ?>
</div>
<?php hf_page_end();
