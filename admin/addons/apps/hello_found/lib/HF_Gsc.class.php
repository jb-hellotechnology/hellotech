<?php
class HF_Gsc
{
	/** Human-readable reason for the most recent failed Google call (never contains tokens). */
	public static $last_error = '';

	const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

	public static function redirect_uri()
	{
		return rtrim(HELLOFOUND_SITE_URL, '/') . PERCH_LOGINPATH . '/addons/apps/hello_found/gsc-callback.php';
	}

	/* ---------- OAuth ---------- */

	private static function state_key() { return hash('sha256', 'gsc-state|' . HELLOFOUND_ENCRYPTION_KEY, true); }

	public static function make_state($account_id)
	{
		$data = (int)$account_id . '.' . time();
		return $data . '.' . hash_hmac('sha256', $data, self::state_key());
	}

	/** @return int|null account id if state is authentic and < 15 min old */
	public static function verify_state($state)
	{
		$p = explode('.', (string)$state);
		if (count($p) !== 3 || !ctype_digit($p[0]) || !ctype_digit($p[1])) return null;
		if (!hash_equals(hash_hmac('sha256', $p[0] . '.' . $p[1], self::state_key()), $p[2])) return null;
		if (time() - (int)$p[1] > 900) return null;
		return (int)$p[0];
	}

	public static function auth_url($account_id)
	{
		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
			'client_id' => HELLOFOUND_GSC_CLIENT_ID, 'redirect_uri' => self::redirect_uri(), 'response_type' => 'code',
			'scope' => self::SCOPE, 'access_type' => 'offline', 'prompt' => 'consent',
			'state' => self::make_state($account_id),
		]);
	}

	/** Exchange auth code and store encrypted tokens. @return string|null error message, null on success */
	public static function connect($account_id, $code, $admin)
	{
		$res = HF_Http::request('POST', 'https://oauth2.googleapis.com/token', [], http_build_query([
			'code' => $code, 'client_id' => HELLOFOUND_GSC_CLIENT_ID, 'client_secret' => HELLOFOUND_GSC_CLIENT_SECRET,
			'redirect_uri' => self::redirect_uri(), 'grant_type' => 'authorization_code',
		]));
		$j = $res['json'];
		if ($res['status'] !== 200 || empty($j['access_token'])) {
			$why = trim(($j['error'] ?? ('HTTP ' . $res['status'])) . (isset($j['error_description']) ? ': ' . $j['error_description'] : ''));
			hf_log('GSC code exchange failed: ' . $why);
			$hint = '';
			if (strpos($why, 'redirect_uri_mismatch') !== false) $hint = ' Add exactly ' . self::redirect_uri() . ' to the OAuth client\'s Authorised redirect URIs.';
			if (strpos($why, 'invalid_client') !== false) $hint = ' Check HELLOFOUND_GSC_CLIENT_ID and HELLOFOUND_GSC_CLIENT_SECRET.';
			return 'Google rejected the sign-in (' . $why . ').' . $hint;
		}

		$existing = HF_Db::row('SELECT * FROM ' . HF_Db::t('gsc_tokens') . ' WHERE account_id=' . (int)$account_id);
		// Google only returns a refresh token on first consent; keep the old one otherwise.
		$refresh = $j['refresh_token'] ?? ($existing ? HF_Crypto::decrypt($existing['refresh_token']) : null);
		if (!$refresh) return 'Google did not return a refresh token. Remove hello.found from the Google account\'s third-party access (myaccount.google.com/permissions) and connect again.';

		$row = [
			'account_id' => (int)$account_id,
			'access_token' => HF_Crypto::encrypt($j['access_token']), 'refresh_token' => HF_Crypto::encrypt($refresh),
			'token_expires' => gmdate('Y-m-d H:i:s', time() + (int)($j['expires_in'] ?? 3600)),
			'is_valid' => 1, 'connected_at' => HF_Db::now(), 'connected_by' => $admin,
		];
		if ($existing) HF_Db::update('gsc_tokens', $row, (int)$existing['id']); else HF_Db::insert('gsc_tokens', $row);

		self::detect_property($account_id);
		return null;
	}

	public static function disconnect($account_id)
	{
		HF_Db::exec('DELETE FROM ' . HF_Db::t('gsc_tokens') . ' WHERE account_id=' . (int)$account_id);
	}

	public static function token_row($account_id) { return HF_Db::row('SELECT * FROM ' . HF_Db::t('gsc_tokens') . ' WHERE account_id=' . (int)$account_id); }

	private static function mark_invalid($account_id, $reason)
	{
		HF_Db::exec('UPDATE ' . HF_Db::t('gsc_tokens') . ' SET is_valid=0 WHERE account_id=' . (int)$account_id);
		HF_Mail::admin('hello.found: GSC connection problem', "Search Console access failed for account #$account_id ($reason). Reconnect it in the admin: "
			. rtrim(HELLOFOUND_SITE_URL, '/') . PERCH_LOGINPATH . "/addons/apps/hello_found/client.php?id=$account_id&tab=gsc");
	}

	/** Valid access token, refreshing when within 5 min of expiry. @return string|null */
	public static function access_token($account_id, $force_refresh = false)
	{
		$row = self::token_row($account_id);
		if (!$row || !$row['is_valid']) return null;
		if (!$force_refresh && strtotime($row['token_expires'] . ' UTC') - time() > 300) return HF_Crypto::decrypt($row['access_token']) ?: null;

		$refresh = HF_Crypto::decrypt($row['refresh_token']);
		if (!$refresh) return null;
		$res = HF_Http::request('POST', 'https://oauth2.googleapis.com/token', [], http_build_query([
			'client_id' => HELLOFOUND_GSC_CLIENT_ID, 'client_secret' => HELLOFOUND_GSC_CLIENT_SECRET,
			'refresh_token' => $refresh, 'grant_type' => 'refresh_token',
		]));
		if ($res['status'] !== 200 || empty($res['json']['access_token'])) {
			if (($res['json']['error'] ?? '') === 'invalid_grant') self::mark_invalid($account_id, 'refresh token revoked');
			return null;
		}
		HF_Db::update('gsc_tokens', [
			'access_token' => HF_Crypto::encrypt($res['json']['access_token']),
			'token_expires' => gmdate('Y-m-d H:i:s', time() + (int)($res['json']['expires_in'] ?? 3600)),
		], (int)$row['id']);
		return $res['json']['access_token'];
	}

	/** Authenticated API call with 401 refresh-retry, 403 invalidation, 429 back-off. */
	public static function call($account_id, $method, $url, $body = null)
	{
		$refreshed = false; $attempt = 0;
		while (true) {
			$token = self::access_token($account_id);
			if (!$token) return null;
			$headers = ['Authorization: Bearer ' . $token, 'Content-Type: application/json'];
			$res = HF_Http::request($method, $url, $headers, $body !== null ? json_encode($body) : null);

			if ($res['status'] >= 400) self::$last_error = 'HTTP ' . $res['status'] . ': ' . ($res['json']['error']['message'] ?? 'no detail');
			if ($res['status'] === 401 && !$refreshed) { $refreshed = true; self::access_token($account_id, true); continue; }
			if ($res['status'] === 403) { self::mark_invalid($account_id, 'HTTP 403 - property not accessible'); return null; }
			if ($res['status'] === 429 && $attempt < 3) { sleep(2 ** (++$attempt)); continue; }
			return ($res['status'] >= 200 && $res['status'] < 300) ? $res['json'] : null;
		}
	}

	/* ---------- Properties ---------- */

	public static function detect_property($account_id)
	{
		$domain = HF_Account::domain($account_id);
		if (!$domain) return null;
		$j = self::call($account_id, 'GET', 'https://www.googleapis.com/webmasters/v3/sites');
		$have = array_column($j['siteEntry'] ?? [], 'siteUrl');
		$d = $domain['domain'];
		foreach (['sc-domain:' . $d, "https://$d/", "https://www.$d/", "http://$d/", "http://www.$d/"] as $candidate) {
			if (in_array($candidate, $have, true)) {
				HF_Db::update('domains', ['gsc_site_url' => $candidate], (int)$domain['id']);
				return $candidate;
			}
		}
		return null;
	}

	/* ---------- Data pull ---------- */

	private static function query($account_id, $site, array $body)
	{
		return self::call($account_id, 'POST', 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($site) . '/searchAnalytics/query', $body);
	}

	/** Pull site aggregate + each active keyword for the latest and prior 28-day windows. @return string status message */
	public static function pull_account($account_id)
	{
		$domain = HF_Account::domain($account_id);
		$site = $domain['gsc_site_url'] ?? null;
		if (!$site) $site = self::detect_property($account_id);
		if (!$site) return 'No matching Search Console property found for this domain.';
		if (!self::access_token($account_id)) return 'Search Console connection is not valid; reconnect it.';

		$n = HF_GscData::WINDOW_DAYS;
		$end_cur = gmdate('Y-m-d', strtotime('-1 day'));
		$windows = [
			[gmdate('Y-m-d', strtotime("-$n days")), $end_cur],
			[gmdate('Y-m-d', strtotime('-' . (2 * $n) . ' days')), gmdate('Y-m-d', strtotime("-$n days -1 day"))],
		];
		$keywords = HF_Keyword::for_account($account_id, true);
		$pulled = 0;

		foreach ($windows as [$start, $end]) {
			// Site-level aggregate
			$j = self::query($account_id, $site, ['startDate' => $start, 'endDate' => $end, 'dimensions' => ['date'], 'rowLimit' => 100]);
			if ($j === null) return 'Search Console request failed (see server log).';
			$clicks = $impr = 0; $pos_w = 0.0;
			foreach ($j['rows'] ?? [] as $r) { $clicks += $r['clicks']; $impr += $r['impressions']; $pos_w += $r['position'] * $r['impressions']; }
			HF_GscData::store($account_id, null, $start, $end, $clicks, $impr, $impr ? $pos_w / $impr : 0);
			$pulled++;

			// Per keyword
			foreach ($keywords as $kw) {
				$j = self::query($account_id, $site, [
					'startDate' => $start, 'endDate' => $end, 'dimensions' => ['query'], 'rowLimit' => 1,
					'dimensionFilterGroups' => [['filters' => [['dimension' => 'query', 'operator' => 'equals', 'expression' => mb_strtolower($kw['keyword'])]]]],
				]);
				if ($j === null) continue;
				$r = $j['rows'][0] ?? null;   // none = no impressions
				HF_GscData::store($account_id, (int)$kw['id'], $start, $end, $r['clicks'] ?? 0, $r['impressions'] ?? 0, $r['position'] ?? 0);
				$pulled++;
			}
		}
		HF_GscData::prune();
		return "Pulled $pulled data sets.";
	}

	public static function pull_all()
	{
		$A = HF_Db::t('accounts'); $T = HF_Db::t('gsc_tokens');
		$ids = HF_Db::rows("SELECT a.id FROM $A a JOIN $T t ON t.account_id=a.id WHERE a.subscription_status IN ('active','trialing') AND t.is_valid=1");
		$out = [];
		foreach ($ids as $r) $out[(int)$r['id']] = self::pull_account((int)$r['id']);
		return $out;
	}
}
