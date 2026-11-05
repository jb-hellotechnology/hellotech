<?php
require __DIR__ . '/../_hf.php';

$account = HF_Account::require_active(true);   // lapsed customers must be able to fix billing

// Returning from the Stripe portal: pull the latest state.
if (($_GET['from'] ?? '') === 'portal' && $account['stripe_subscription_id']) {
	$sub = HF_Stripe::subscription($account['stripe_subscription_id']);
	if ($sub) { HF_Account::apply_subscription($account['id'], $sub); $account = HF_Account::find($account['id']); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hf_csrf_ok() && $account['stripe_customer_id']) {
	$url = HF_Stripe::portal_url($account['stripe_customer_id']);
	if ($url) hf_redirect($url);
	$error = 'Could not open the billing portal. Please try again.';
}

$sub = $account['stripe_subscription_id'] ? HF_Stripe::subscription($account['stripe_subscription_id']) : null;
$next = $sub ? HF_Stripe::period_end($sub) : null;
$last4 = $sub ? HF_Stripe::card_last4($sub) : null;

hf_page_start('Subscription');
if (($_GET['status'] ?? '') === 'inactive'): ?>
<div class="hf-notice">Your subscription isn't active, so the dashboard is paused. Update your payment details or restart below.</div>
<?php endif; ?>
<div class="hf-card">
  <h1 class="h2">Your subscription</h1>
  <?php if (!empty($error)): ?><p class="hf-err"><?= hf_e($error) ?></p><?php endif; ?>
  <table class="hf-table">
    <tr><th>Plan</th><td>hello.found · £99/month</td></tr>
    <tr><th>Status</th><td><span class="hf-badge <?= hf_e($account['subscription_status']) ?>"><?= hf_e(str_replace('_', ' ', $account['subscription_status'])) ?></span></td></tr>
    <?php if ($next): ?><tr><th><?= $account['plan_end'] ? 'Ends' : 'Next billing date' ?></th><td><?= hf_e(gmdate('j F Y', $next)) ?></td></tr><?php endif; ?>
    <?php if ($last4): ?><tr><th>Card</th><td>•••• <?= hf_e($last4) ?></td></tr><?php endif; ?>
  </table>
  <div style="margin-top:1.5rem">
  <?php if ($account['stripe_customer_id']): ?>
    <form method="post" action=""><?= hf_csrf_field() ?><button class="btn btn--primary" type="submit">Manage subscription</button></form>
  <?php else: ?>
    <a class="btn btn--primary" href="<?= hf_e(hf_url('signup/')) ?>">Start subscription</a>
  <?php endif; ?>
  </div>
</div>
<?php hf_page_end();
