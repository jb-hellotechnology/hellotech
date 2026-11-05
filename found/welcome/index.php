<?php
require __DIR__ . '/../_hf.php';

// Never trust the query string: re-fetch the session from Stripe and check it.
$session = isset($_GET['session_id']) ? HF_Stripe::retrieve_session((string)$_GET['session_id']) : null;
$account = $session ? HF_Account::provision_from_session($session) : null;

if ($account) {
	if (!HF_Members::logged_in()) HF_Members::log_in((int)$account['member_id']);
	hf_redirect(hf_url($account['onboarding_complete'] ? 'dashboard/' : 'onboarding/'));
}

hf_page_start('Welcome');
?>
<div class="hf-card">
  <h1 class="h2">We couldn't confirm your payment yet</h1>
  <p>If you were charged, your account will be created automatically within a minute or two and you'll get an email. If nothing arrives, contact <a href="mailto:<?= hf_e(HELLOFOUND_NOTIFY_EMAIL) ?>"><?= hf_e(HELLOFOUND_NOTIFY_EMAIL) ?></a>.</p>
  <p><a class="btn btn--primary" href="<?= hf_e(hf_url('dashboard/')) ?>">Try the dashboard</a></p>
</div>
<?php hf_page_end();
