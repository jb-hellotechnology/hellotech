<?php
class HF_Fathom
{
	const API = 'https://api.usefathom.com/v1';

	private static function call($method, $path, $params = null)
	{
		$headers = ['Authorization: Bearer ' . HELLOFOUND_FATHOM_API_KEY, 'Accept: application/json'];
		$body = null; $url = self::API . $path;
		if ($method === 'GET' && $params) $url .= '?' . http_build_query($params);
		if ($method !== 'GET' && $params !== null) { $headers[] = 'Content-Type: application/json'; $body = json_encode($params); }
		$res = HF_Http::request($method, $url, $headers, $body);
		if ($res['status'] >= 400) hf_log('Fathom ' . $method . ' ' . $path . ' -> ' . $res['status']);
		return ($res['status'] >= 200 && $res['status'] < 300) ? $res['json'] : null;
	}

	/** Create a site and store ID + snippet. @return string|null error */
	public static function create_site_for($account_id)
	{
		$domain = HF_Account::domain($account_id);
		if (!$domain) return 'Customer has not entered a domain yet.';
		if ($domain['fathom_site_id']) return 'Fathom site already exists.';

		$j = self::call('POST', '/sites', ['name' => 'hello.found - ' . $domain['domain']]);
		if (!$j || empty($j['id'])) return 'Fathom API call failed (check API key and server log).';

		$id = (string)$j['id'];
		// The API may not return a ready-made snippet; fall back to the standard one.
		$snippet = $j['tracking_code'] ?? '<script src="https://cdn.usefathom.com/script.js" data-site="' . $id . '" defer></script>';
		HF_Db::update('domains', ['fathom_site_id' => $id, 'fathom_tracking_code' => $snippet], (int)$domain['id']);
		return null;
	}

	/** Last-30-day aggregates, cached 1 hour on disk. @return array|null */
	public static function stats($site_id)
	{
		if (!preg_match('/^[A-Za-z0-9]+$/', (string)$site_id)) return null;
		$cache = sys_get_temp_dir() . '/hf_fathom_' . $site_id . '.json';
		if (is_file($cache) && time() - filemtime($cache) < 3600) {
			$c = json_decode(file_get_contents($cache), true);
			if ($c) return $c;
		}
		$j = self::call('GET', '/aggregations', [
			'entity' => 'pageview', 'entity_id' => $site_id, 'aggregates' => 'visits,pageviews,bounce_rate,avg_duration',
			'date_from' => gmdate('Y-m-d', strtotime('-30 days')), 'date_to' => gmdate('Y-m-d'),
		]);
		if (!$j || !isset($j[0])) return null;
		@file_put_contents($cache, json_encode($j[0]), LOCK_EX);
		return $j[0];
	}
}
