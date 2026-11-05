<?php
require __DIR__ . '/../_hf.php';

// Already registered and logged in? Skip straight to payment / dashboard.
if (HF_Members::logged_in()) {
	$acc = HF_Account::by_member(HF_Members::current_id());
	hf_redirect(hf_url($acc && HF_Account::is_active($acc) ? 'dashboard/' : 'subscribe/'));
}

$errors = []; $v = ['first_name' => '', 'last_name' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	foreach ($v as $k => $_) $v[$k] = trim((string)($_POST[$k] ?? ''));
	$pw = (string)($_POST['password'] ?? '');

	if (!hf_csrf_ok()) $errors[] = 'Your session expired. Please try again.';
	if ($v['first_name'] === '') $errors[] = 'Please enter your first name.';
	if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
	if (strlen($pw) < 8) $errors[] = 'Your password needs at least 8 characters.';
	if ($pw !== (string)($_POST['password2'] ?? '')) $errors[] = 'The two passwords do not match.';
	if (!$errors && HF_Members::find_id_by_email($v['email'])) {
		$errors[] = 'There is already an account with that email address. <a href="' . hf_e(HELLOFOUND_LOGIN_URL . '?r=' . rawurlencode(hf_url('subscribe/'))) . '">Log in instead</a>.';
	}

	if (!$errors) {
		$name = trim($v['first_name'] . ' ' . $v['last_name']);
		$member_id = HF_Members::create($v['email'], $name, $pw);
		if (!$member_id) {
			$errors[] = 'Sorry, we could not create your account. Please try again.';
		} else {
			HF_Account::create(['member_id' => $member_id]);
			HF_Members::log_in($member_id);
			HF_Mail::send($v['email'], 'Welcome to hello.found',
				"Hi {$v['first_name']},\n\nThanks for registering with hello.found. Once your subscription is set up, "
				. "tell us your website address and we'll get everything connected.\n\nLog in any time: "
				. rtrim(HELLOFOUND_SITE_URL, '/') . HELLOFOUND_LOGIN_URL . "\n\nJack\nHello Technology");
			hf_redirect(hf_url('subscribe/'));
		}
	}
}

hf_page_start('Create your account');
?>
<div class="hf-card hf-form">
  <span class="eyebrow">hello.found · £99/month</span>
  <h1 class="h2">Create your account</h1>
  <p>Step 1 of 2. Next you'll set up your subscription securely with Stripe.</p>
  <?php foreach ($errors as $e): ?><p class="hf-err" role="alert"><?= $e /* escaped above or fixed markup */ ?></p><?php endforeach; ?>
  <form method="post" action="" novalidate>
    <?= hf_csrf_field() ?>
    <label for="first_name"><b>First name</b></label>
    <input type="text" id="first_name" name="first_name" value="<?= hf_e($v['first_name']) ?>" required autocomplete="given-name">
    <label for="last_name"><b>Last name</b></label>
    <input type="text" id="last_name" name="last_name" value="<?= hf_e($v['last_name']) ?>" autocomplete="family-name">
    <label for="email"><b>Email</b></label>
    <input type="text" id="email" name="email" value="<?= hf_e($v['email']) ?>" required autocomplete="email" inputmode="email">
    <label for="password"><b>Password</b> (8+ characters)</label>
    <input type="password" id="password" name="password" required autocomplete="new-password">
    <label for="password2"><b>Confirm password</b></label>
    <input type="password" id="password2" name="password2" required autocomplete="new-password">
    <button class="btn btn--primary" type="submit">Continue to payment</button>
  </form>
  <p style="margin-top:1rem">Already registered? <a href="<?= hf_e(HELLOFOUND_LOGIN_URL . '?r=' . rawurlencode(hf_url('subscribe/'))) ?>">Log in</a>.</p>
</div>
<?php hf_page_end();
