<?php
require __DIR__ . '/../_hf.php';

$account = HF_Account::require_active();
if ($account['onboarding_complete']) hf_redirect(hf_url('dashboard/'));

$error = ''; $domain_in = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$domain_in = (string)($_POST['domain'] ?? '');
	$domain = HF_Account::sanitise_domain($domain_in);
	if (!hf_csrf_ok()) $error = 'Your session expired. Please try again.';
	elseif ($domain === '') $error = 'Please enter a valid domain, for example example.co.uk.';
	else {
		HF_Account::save_domain($account['id'], $domain);
		$m = HF_Members::details($account['member_id']);
		HF_Mail::admin('hello.found: new customer waiting for activation',
			"{$m['name']} <{$m['email']}> entered the domain $domain.\n\nConnect Search Console, create the Fathom site and add keywords:\n"
			. rtrim(HELLOFOUND_SITE_URL, '/') . PERCH_LOGINPATH . '/addons/apps/hello_found/client.php?id=' . (int)$account['id']);
		$done = true;
	}
}

hf_page_start('Your website');
if (!empty($done)): ?>
<div class="hf-card">
  <h1 class="h2">Thanks, that's all we need</h1>
  <p>We'll connect your Google Search Console and set up analytics. You'll get an email once your account is fully live.</p>
  <p><a class="btn btn--primary" href="<?= hf_e(hf_url('dashboard/')) ?>">Go to your dashboard</a></p>
</div>
<?php else: ?>
<div class="hf-card hf-form">
  <h1 class="h2">What's your website?</h1>
  <?php if ($error): ?><p class="hf-err"><?= hf_e($error) ?></p><?php endif; ?>
  <form method="post" action="">
    <?= hf_csrf_field() ?>
    <label for="domain"><b>Your website domain</b> (e.g. example.co.uk)</label>
    <input type="text" id="domain" name="domain" value="<?= hf_e($domain_in) ?>" required autocomplete="url" inputmode="url">
    <button class="btn btn--primary" type="submit">Continue</button>
  </form>
</div>
<?php endif; hf_page_end();
