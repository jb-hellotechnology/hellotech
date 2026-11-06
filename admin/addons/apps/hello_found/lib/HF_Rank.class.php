<?php
/** Weekly Google UK organic position checks via DataForSEO SERP API. */
class HF_Rank
{
	const DUE_AFTER_DAYS = 6;   // run-daily scheduler + 6-day gap = roughly weekly per keyword

	public static function depth() { return defined('HELLOFOUND_SERP_DEPTH') ? (int)HELLOFOUND_SERP_DEPTH : 100; }

	/** Default Google location (DataForSEO location_name). Override per client in the admin, or globally with HELLOFOUND_SERP_LOCATION. */
	public static function default_location() { return defined('HELLOFOUND_SERP_LOCATION') ? HELLOFOUND_SERP_LOCATION : 'United Kingdom'; }

	public static function location_for(array $domain_row) { return !empty($domain_row['serp_location']) ? $domain_row['serp_location'] : self::default_location(); }

	/**
	 * Check one keyword for a client. $domain_row is the hellofound_domains row. Stores a row. @return string|null error
	 * position = organic rank (rank_group) of the client's best result, NULL = not in the top N.
	 * If Google returned no organic results at all, that is reported as an error, never as "not ranking".
	 */
	public static function check(array $kw, array $domain_row)
	{
		$domain = $domain_row['domain'];
		[$task, $err] = HF_Dfs::post('/v3/serp/google/organic/live/advanced', [
			'keyword' => $kw['keyword'], 'location_name' => self::location_for($domain_row), 'language_code' => 'en',
			'depth' => self::depth(), 'device' => 'desktop',
		]);
		if ($err) return $err;

		$items = $task['result'][0]['items'] ?? [];
		$pos = null; $url = null; $ao_present = 0; $ao_cited = 0; $organic = 0; $top = [];
		foreach ($items as $it) {
			$type = $it['type'] ?? '';
			if ($type === 'organic') {
				$organic++;
				$d = preg_replace('/^www\./', '', strtolower($it['domain'] ?? (string)parse_url($it['url'] ?? '', PHP_URL_HOST)));
				if (count($top) < 5) $top[] = $d;
				if ($pos === null && HF_Dfs::host_matches($d, $domain)) { $pos = (int)($it['rank_group'] ?? 0) ?: null; $url = $it['url'] ?? null; }
			}
			if ($type === 'ai_overview') {
				$ao_present = 1;
				$refs = [];
				HF_Dfs::collect($it, 'url', $refs); HF_Dfs::collect($it, 'domain', $refs);
				foreach ($refs as $r) if (HF_Dfs::host_matches($r, $domain)) { $ao_cited = 1; break; }
			}
		}
		if ($organic === 0) return 'Google returned no organic results for "' . $kw['keyword'] . '" in ' . self::location_for($domain_row) . ' (check the keyword and location).';

		HF_Db::insert('rank_checks', [
			'account_id' => (int)$kw['account_id'], 'keyword_id' => (int)$kw['id'], 'checked_on' => gmdate('Y-m-d'),
			'position' => $pos, 'url' => $url !== null ? substr($url, 0, 500) : null,
			'ai_overview_present' => $ao_present, 'ai_overview_cited' => $ao_cited,
			'organic_count' => $organic, 'top_domains' => substr(implode(', ', $top), 0, 500), 'checked_at' => HF_Db::now(),
		]);
		return null;
	}

	/** Check active keywords with no check in the last DUE_AFTER_DAYS (or all, if $force). @return array [checked, errors[]] */
	public static function run_account($account_id, $force = false)
	{
		$domain = HF_Account::domain($account_id);
		if (!$domain) return [0, ['No domain for this account.']];
		$checked = 0; $errors = [];
		foreach (HF_Keyword::for_account($account_id, true) as $kw) {
			if (!$force && self::checked_recently((int)$kw['id'])) continue;
			$e = self::check($kw, $domain);
			if ($e) { $errors[] = $kw['keyword'] . ': ' . $e; if (strpos($e, 'credentials') !== false || strpos($e, 'rejected') !== false) break; }
			else $checked++;
		}
		return [$checked, $errors];
	}

	private static function checked_recently($keyword_id)
	{
		$d = gmdate('Y-m-d', strtotime('-' . self::DUE_AFTER_DAYS . ' days'));
		return (bool)HF_Db::value('SELECT id FROM ' . HF_Db::t('rank_checks') . ' WHERE keyword_id=' . (int)$keyword_id . ' AND checked_on > ' . HF_Db::q($d) . ' LIMIT 1');
	}

	/** Latest check and the one before it (at least 3 days older) for a keyword. @return array [cur|null, prev|null] */
	public static function latest_pair($keyword_id)
	{
		$T = HF_Db::t('rank_checks');
		$cur = HF_Db::row("SELECT * FROM $T WHERE keyword_id=" . (int)$keyword_id . ' ORDER BY checked_on DESC, id DESC LIMIT 1');
		if (!$cur) return [null, null];
		$cut = gmdate('Y-m-d', strtotime($cur['checked_on'] . ' -3 days'));
		$prev = HF_Db::row("SELECT * FROM $T WHERE keyword_id=" . (int)$keyword_id . ' AND checked_on <= ' . HF_Db::q($cut) . ' ORDER BY checked_on DESC, id DESC LIMIT 1');
		return [$cur, $prev];
	}
}
