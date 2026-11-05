<?php
/**
 * Stripe over raw HTTPS (no Composer dependency). Server-side only.
 * Webhook signatures are verified manually per Stripe's documented scheme.
 */
class HF_Stripe
{
	const API = 'https://api.stripe.com/v1';

	public static function api($method, $path, array $params = [])
	{
		$url = self::API . $path;
		$headers = ['Authorization: Bearer ' . HELLOFOUND_STRIPE_SECRET];
		$body = null;
		if ($method === 'GET') { if ($params) $url .= '?' . http_build_query($params); }
		else $body = http_build_query($params);
		$res = HF_Http::request($method, $url, $headers, $body);
		if ($res['status'] >= 400) hf_log('Stripe ' . $method . ' ' . $path . ' -> ' . $res['status'] . ' ' . ($res['json']['error']['message'] ?? ''));
		return $res['status'] >= 200 && $res['status'] < 300 ? $res['json'] : null;
	}

	/** @return string|null Checkout URL */
	public static function checkout_url($email, $account_id = null)
	{
		$params = [
			'mode' => 'subscription',
			'line_items' => [['price' => HELLOFOUND_STRIPE_PRICE_ID, 'quantity' => 1]],
			'success_url' => hf_abs_url('welcome/') . '?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url' => hf_abs_url('signup/'),
		];
		if ($email) $params['customer_email'] = $email;
		if ($account_id) {
			$params['client_reference_id'] = (string)$account_id;
			$params['subscription_data'] = ['metadata' => ['account_id' => (string)$account_id]];
		}
		$s = self::api('POST', '/checkout/sessions', $params);
		return $s['url'] ?? null;
	}

	public static function retrieve_session($id)
	{
		if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $id)) return null;
		return self::api('GET', '/checkout/sessions/' . $id, ['expand' => ['subscription', 'customer']]);
	}

	public static function portal_url($customer_id)
	{
		$s = self::api('POST', '/billing_portal/sessions', ['customer' => $customer_id, 'return_url' => hf_abs_url('account/') . '?from=portal']);
		return $s['url'] ?? null;
	}

	public static function subscription($id)
	{
		return self::api('GET', '/subscriptions/' . rawurlencode($id), ['expand' => ['default_payment_method']]);
	}

	/** current_period_end moved onto items in newer API versions; support both. */
	public static function period_end(array $sub)
	{
		return $sub['current_period_end'] ?? ($sub['items']['data'][0]['current_period_end'] ?? null);
	}

	public static function card_last4(array $sub)
	{
		return $sub['default_payment_method']['card']['last4'] ?? null;
	}

	/** Verify the Stripe-Signature header. @return array|null decoded event, null if invalid */
	public static function verify_webhook($payload, $sig_header, $secret, $tolerance = 300)
	{
		$t = null; $sigs = [];
		foreach (explode(',', (string)$sig_header) as $part) {
			$kv = explode('=', trim($part), 2);
			if (count($kv) !== 2) continue;
			if ($kv[0] === 't') $t = $kv[1];
			if ($kv[0] === 'v1') $sigs[] = $kv[1];
		}
		if (!$t || !$sigs || !ctype_digit($t) || abs(time() - (int)$t) > $tolerance) return null;
		$expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
		foreach ($sigs as $s) if (hash_equals($expected, $s)) return json_decode($payload, true);
		return null;
	}

	public static function handle_event(array $event)
	{
		$obj = $event['data']['object'] ?? [];
		switch ($event['type'] ?? '') {
			case 'checkout.session.completed':
				$full = self::retrieve_session($obj['id'] ?? '');
				if ($full) HF_Account::provision_from_session($full);
				break;

			case 'customer.subscription.updated':
			case 'customer.subscription.deleted':
				$acc = self::account_for_subscription($obj);
				if ($acc) {
					if ($event['type'] === 'customer.subscription.deleted') $obj['status'] = 'canceled';
					HF_Account::apply_subscription($acc['id'], $obj);
				}
				break;

			case 'invoice.payment_succeeded':
			case 'invoice.payment_failed':
				$sub_id = $obj['subscription'] ?? ($obj['parent']['subscription_details']['subscription'] ?? null);
				$acc = $sub_id ? HF_Account::by_subscription($sub_id) : null;
				if (!$acc && !empty($obj['customer'])) $acc = HF_Account::by_customer($obj['customer']);
				if (!$acc) break;
				if ($event['type'] === 'invoice.payment_succeeded') {
					$sub = $sub_id ? self::subscription($sub_id) : null;
					if ($sub) HF_Account::apply_subscription($acc['id'], $sub);
					else HF_Account::update($acc['id'], ['subscription_status' => 'active']);
				} else {
					HF_Account::update($acc['id'], ['subscription_status' => 'past_due']);
					$m = HF_Members::details($acc['member_id']);
					if ($m) HF_Mail::send($m['email'], 'hello.found: payment problem',
						"Hi {$m['name']},\n\nWe couldn't take your latest hello.found payment. Please update your card here:\n"
						. hf_abs_url('account/') . "\n\nJack\nHello Technology");
				}
				break;
		}
	}

	private static function account_for_subscription(array $sub)
	{
		$acc = !empty($sub['id']) ? HF_Account::by_subscription($sub['id']) : null;
		if (!$acc && !empty($sub['metadata']['account_id'])) $acc = HF_Account::find((int)$sub['metadata']['account_id']);
		if (!$acc && !empty($sub['customer'])) $acc = HF_Account::by_customer($sub['customer']);
		return $acc;
	}
}
