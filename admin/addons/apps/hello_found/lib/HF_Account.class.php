<?php
class HF_Account
{
	const ACTIVE = ['active', 'trialing'];

	public static function find($id) { return HF_Db::row('SELECT * FROM ' . HF_Db::t('accounts') . ' WHERE id=' . (int)$id . ' LIMIT 1'); }
	public static function by_member($member_id) { return HF_Db::row('SELECT * FROM ' . HF_Db::t('accounts') . ' WHERE member_id=' . (int)$member_id . ' LIMIT 1'); }
	public static function by_customer($cus) { return HF_Db::row('SELECT * FROM ' . HF_Db::t('accounts') . ' WHERE stripe_customer_id=' . HF_Db::q($cus) . ' LIMIT 1'); }
	public static function by_subscription($sub) { return HF_Db::row('SELECT * FROM ' . HF_Db::t('accounts') . ' WHERE stripe_subscription_id=' . HF_Db::q($sub) . ' LIMIT 1'); }

	public static function domain($account_id)
	{
		return HF_Db::row('SELECT * FROM ' . HF_Db::t('domains') . ' WHERE account_id=' . (int)$account_id . ' LIMIT 1');
	}

	public static function create(array $data)
	{
		$now = HF_Db::now();
		$data += ['subscription_status' => 'incomplete', 'onboarding_complete' => 0, 'created_at' => $now, 'updated_at' => $now];
		return (int)HF_Db::insert('accounts', $data);
	}

	public static function update($id, array $data)
	{
		$data['updated_at'] = HF_Db::now();
		return HF_Db::update('accounts', $data, (int)$id);
	}

	public static function is_active(array $account) { return in_array($account['subscription_status'], self::ACTIVE, true); }

	/**
	 * Gate for customer pages. Returns the account row or redirects.
	 * $allow_inactive: true for /account/ so lapsed customers can fix billing.
	 */
	public static function require_active($allow_inactive = false)
	{
		if (!HF_Members::logged_in()) hf_redirect(HELLOFOUND_LOGIN_URL . '?return=' . rawurlencode($_SERVER['REQUEST_URI'] ?? hf_url('dashboard/')));
		$account = self::by_member(HF_Members::current_id());
		if (!$account) hf_redirect(hf_url('signup/'));
		if (!$allow_inactive && !self::is_active($account)) hf_redirect(hf_url('account/') . '?status=inactive');
		return $account;
	}

	/** Map a Stripe subscription object onto our enum + dates. */
	public static function apply_subscription($account_id, array $sub)
	{
		$map = ['active' => 'active', 'trialing' => 'trialing', 'past_due' => 'past_due', 'unpaid' => 'past_due',
		        'paused' => 'past_due', 'canceled' => 'canceled', 'incomplete_expired' => 'canceled', 'incomplete' => 'incomplete'];
		$status = $map[$sub['status'] ?? ''] ?? 'incomplete';

		$period_end = HF_Stripe::period_end($sub);
		$data = ['subscription_status' => $status, 'stripe_subscription_id' => $sub['id'] ?? null];
		$data['plan_end'] = ($status === 'canceled' || !empty($sub['cancel_at_period_end']) || !empty($sub['cancel_at']))
			? ($period_end ? gmdate('Y-m-d H:i:s', $period_end) : HF_Db::now())
			: null;
		if (in_array($status, self::ACTIVE, true)) {
			$acc = self::find($account_id);
			if ($acc && !$acc['plan_start']) $data['plan_start'] = HF_Db::now();
		}
		self::update($account_id, $data);
	}

	/**
	 * Idempotently turn a completed Checkout Session into member + account.
	 * Called from the welcome page AND the webhook; safe to call twice.
	 * @return array|null the account row
	 */
	public static function provision_from_session(array $session)
	{
		if (($session['status'] ?? '') !== 'complete' || ($session['payment_status'] ?? '') !== 'paid') return null;

		$cus   = is_array($session['customer'] ?? null) ? $session['customer']['id'] : ($session['customer'] ?? null);
		$sub   = $session['subscription'] ?? null;
		$sub_id = is_array($sub) ? ($sub['id'] ?? null) : $sub;
		$email = $session['customer_details']['email'] ?? ($session['customer_email'] ?? '');
		$name  = $session['customer_details']['name'] ?? '';

		// 1. Existing account? (by client_reference_id, then Stripe IDs)
		$account = null;
		if (!empty($session['client_reference_id'])) $account = self::find((int)$session['client_reference_id']);
		if (!$account && $cus) $account = self::by_customer($cus);
		if (!$account && $sub_id) $account = self::by_subscription($sub_id);

		$created = false;
		if (!$account) {
			if ($email === '') return null;
			$member_id = HF_Members::find_id_by_email($email);
			if (!$member_id) { $member_id = HF_Members::create($email, $name); $created = true; }
			if (!$member_id) return null;
			$account = self::by_member($member_id);
			if (!$account) {
				$id = self::create(['member_id' => $member_id]);
				$account = self::find($id);
			}
		}

		self::update($account['id'], ['stripe_customer_id' => $cus, 'stripe_subscription_id' => $sub_id]);
		if (is_array($sub)) self::apply_subscription($account['id'], $sub);
		else self::update($account['id'], ['subscription_status' => 'active', 'plan_start' => $account['plan_start'] ?: HF_Db::now()]);

		if ($created && $email !== '') {
			HF_Mail::send($email, 'Welcome to hello.found',
				"Hi " . ($name ?: 'there') . ",\n\nThanks for joining hello.found. Your account is ready.\n\n"
				. "Set your password here: " . rtrim(HELLOFOUND_SITE_URL, '/') . HELLOFOUND_RESET_URL . "\n"
				. "Then log in: " . rtrim(HELLOFOUND_SITE_URL, '/') . HELLOFOUND_LOGIN_URL . "\n\n"
				. "First step: tell us your website domain at " . hf_abs_url('onboarding/') . "\n\nJack\nHello Technology");
		}
		return self::find($account['id']);
	}

	/** Strip scheme, path, www., port; lower-case. Returns '' if not a plausible domain. */
	public static function sanitise_domain($input)
	{
		$d = strtolower(trim($input));
		$d = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $d);
		$d = preg_replace('#[/?\#].*$#', '', $d);
		$d = preg_replace('#:\d+$#', '', $d);
		$d = preg_replace('#^www\.#', '', $d);
		return preg_match('/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $d) ? $d : '';
	}

	public static function save_domain($account_id, $domain)
	{
		$existing = self::domain($account_id);
		if ($existing) {
			HF_Db::update('domains', ['domain' => $domain], (int)$existing['id']);
		} else {
			HF_Db::insert('domains', ['account_id' => (int)$account_id, 'domain' => $domain, 'created_at' => HF_Db::now()]);
		}
		self::update($account_id, ['onboarding_complete' => 1]);
	}

	/** Admin list with member details and flags. */
	public static function all_for_admin($status = '')
	{
		$A = HF_Db::t('accounts'); $D = HF_Db::t('domains'); $T = HF_Db::t('gsc_tokens'); $R = HF_Db::t('reports');
		$where = in_array($status, ['active', 'trialing', 'past_due', 'canceled', 'incomplete'], true) ? ' WHERE a.subscription_status=' . HF_Db::q($status) : '';
		$rows = HF_Db::rows("SELECT a.*, d.domain, d.fathom_site_id, (t.id IS NOT NULL) AS gsc_connected,
			(SELECT MAX(report_month) FROM $R r WHERE r.account_id=a.id AND r.status='published') AS last_report
			FROM $A a LEFT JOIN $D d ON d.account_id=a.id LEFT JOIN $T t ON t.account_id=a.id
			$where ORDER BY a.created_at DESC");
		foreach ($rows as &$r) {
			$m = HF_Members::details($r['member_id']);
			$r['name'] = $m['name'] ?? '(unknown)'; $r['email'] = $m['email'] ?? '';
		}
		return $rows;
	}

	public static function notes($account_id) { return HF_Db::rows('SELECT * FROM ' . HF_Db::t('admin_notes') . ' WHERE account_id=' . (int)$account_id . ' ORDER BY created_at DESC'); }
}
