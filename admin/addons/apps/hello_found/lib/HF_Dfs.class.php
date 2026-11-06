<?php
/**
 * DataForSEO client (HTTP Basic auth with the API login + API password from
 * https://app.dataforseo.com/api-access - not your account login password).
 * Set HELLOFOUND_DFS_BASE to https://sandbox.dataforseo.com to test without spending credit.
 */
class HF_Dfs
{
	public static function configured()
	{
		return defined('HELLOFOUND_DFS_LOGIN') && defined('HELLOFOUND_DFS_PASSWORD')
			&& HELLOFOUND_DFS_LOGIN !== '' && strpos(HELLOFOUND_DFS_LOGIN, '...') === false;
	}

	private static function base() { return defined('HELLOFOUND_DFS_BASE') ? rtrim(HELLOFOUND_DFS_BASE, '/') : 'https://api.dataforseo.com'; }

	private static function auth() { return 'Authorization: Basic ' . base64_encode(HELLOFOUND_DFS_LOGIN . ':' . HELLOFOUND_DFS_PASSWORD); }

	/**
	 * POST a single task. @return array [task|null, error|null]; task = tasks[0] (has 'result', 'cost').
	 */
	public static function post($path, array $task)
	{
		if (!self::configured()) return [null, 'DataForSEO credentials are not configured.'];
		$res = HF_Http::request('POST', self::base() . $path, [self::auth(), 'Content-Type: application/json'], json_encode([$task]), 130);
		return self::unpack($res);
	}

	public static function get($path)
	{
		if (!self::configured()) return [null, 'DataForSEO credentials are not configured.'];
		return self::unpack(HF_Http::request('GET', self::base() . $path, [self::auth()], null, 60));
	}

	private static function unpack(array $res)
	{
		$j = $res['json'];
		if ($res['status'] === 401) return [null, 'DataForSEO rejected the login/password (use the API password from app.dataforseo.com/api-access).'];
		if (!is_array($j)) return [null, 'No usable response from DataForSEO (HTTP ' . $res['status'] . ').'];
		$task = $j['tasks'][0] ?? null;
		if (!$task) return [null, 'DataForSEO: ' . ($j['status_message'] ?? 'empty response')];
		if (($task['status_code'] ?? 0) !== 20000) return [null, 'DataForSEO: ' . ($task['status_message'] ?? 'task failed') . ' (' . ($task['status_code'] ?? '?') . ')'];
		return [$task, null];
	}

	/** True if a URL or host belongs to the client's domain (exact or any subdomain, ignoring www). */
	public static function host_matches($url_or_host, $domain)
	{
		$h = strtolower(trim((string)$url_or_host));
		if (strpos($h, '//') !== false || strpos($h, '/') !== false) $h = (string)parse_url(strpos($h, '//') === false ? '//' . $h : $h, PHP_URL_HOST);
		$h = preg_replace('/^www\./', '', $h);
		$d = preg_replace('/^www\./', '', strtolower($domain));
		return $h !== '' && ($h === $d || substr($h, -strlen($d) - 1) === '.' . $d);
	}

	/** Recursively collect every string value stored under a key named $key anywhere in $node. */
	public static function collect($node, $key, array &$out)
	{
		if (!is_array($node)) return;
		foreach ($node as $k => $v) {
			if ($k === $key && is_string($v)) $out[] = $v;
			if (is_array($v)) self::collect($v, $key, $out);
		}
	}
}
