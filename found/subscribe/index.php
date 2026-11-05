<?php
require __DIR__ . '/../_hf.php';

// Step 2: payment. Needs a registered, logged-in customer.
if (!HF_Members::logged_in()) hf_redirect(HELLOFOUND_LOGIN_URL . '?r=' . rawurlencode(hf_url('subscribe/')));
$member_id = HF_Members::current_id();
$account = HF_Account::by_member($member_id);
if (!$account) $account = HF_Account::find(HF_Account::create(['member_id' => $member_id]));
if (HF_Account::is_active($account)) hf_redirect(hf_url($account['onboarding_complete'] ? 'dashboard/' : 'onboarding/'));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!hf_csrf_ok()) $error = 'Your session expired. Please try again.';
	elseif (!defined('HELLOFOUND_STRIPE_SECRET') || !defined('HELLOFOUND_STRIPE_PRICE_ID')) $error = 'Checkout is not configured yet.';
	else {
		// Reuse the Stripe customer if they've been here before, so a retry doesn't create duplicates.
		$m = HF_Members::details($member_id);
		$url = HF_Stripe::checkout_url($m['email'] ?? null, (int)$account['id'], $account['stripe_customer_id'] ?: null);
		if ($url) hf_redirect($url);
		$error = 'Sorry, we could not start checkout. Please try again in a moment.';
	}
}

hf_page_start('Set up your subscription');
?>
<div class="hf-card">
  <span class="eyebrow">Step 2 of 2</span>
  <h1 class="h2">Set up your subscription</h1>
  <?php if (($_GET['status'] ?? '') === 'cancelled'): ?><div class="hf-notice">Payment wasn't completed. You haven't been charged.</div><?php endif; ?>
  <p>£99/month, cancel any month. You'll pay securely on Stripe and come straight back here.</p>
  <?php if ($error): ?><p class="hf-err" role="alert"><?= hf_e($error) ?></p><?php endif; ?>
  <form method="post" action=""><?= hf_csrf_field() ?><button class="btn btn--primary" type="submit">Continue to Stripe</button></form>
</div>
<?php hf_page_end();
