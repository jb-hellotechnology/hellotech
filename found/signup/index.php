<?php
require __DIR__ . '/../_hf.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!hf_csrf_ok()) {
		$error = 'Your session expired. Please try again.';
	} elseif (!defined('HELLOFOUND_STRIPE_SECRET') || !defined('HELLOFOUND_STRIPE_PRICE_ID')) {
		$error = 'Checkout is not configured yet.';
	} else {
		$email = null; $account_id = null;
		if (HF_Members::logged_in()) {
			$member_id = HF_Members::current_id();
			$account = HF_Account::by_member($member_id);
			if ($account && HF_Account::is_active($account)) hf_redirect(hf_url('dashboard/'));
			$account_id = $account ? (int)$account['id'] : HF_Account::create(['member_id' => $member_id]);
			$m = HF_Members::details($member_id);
			$email = $m['email'] ?? null;
		}
		$url = HF_Stripe::checkout_url($email, $account_id);
		if ($url) hf_redirect($url);
		$error = 'Sorry, we could not start checkout. Please try again in a moment.';
	}
}

hf_page_start('Start your subscription');
?>
<div class="hf-card">
  <span class="eyebrow">hello.found</span>
  <h1 class="h2">SEO &amp; AI search, managed for you</h1>
  <p>£99/month. Google Business Profile, optimised content, Search Console monitoring, schema markup, AI search optimisation and a plain-English monthly report. Cancel any month.</p>
  <?php if ($error): ?><p class="hf-err"><?= hf_e($error) ?></p><?php endif; ?>
  <form method="post" action="">
    <?= hf_csrf_field() ?>
    <button class="btn btn--primary" type="submit">Start subscription</button>
  </form>
  <?php if (!HF_Members::logged_in()): ?>
    <p style="margin-top:1rem">Already a Hello Technology client? <a href="<?= hf_e(HELLOFOUND_LOGIN_URL . '?return=' . rawurlencode(hf_url('signup/'))) ?>">Log in first</a>.</p>
  <?php endif; ?>
</div>
<?php hf_page_end();
