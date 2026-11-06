<?php
/**
 * AI-search citation tracking. Sends each client prompt to ChatGPT / Gemini / Perplexity
 * through DataForSEO's LLM Responses API (web search on) and records whether the client's domain
 * was cited. API answers approximate, but are not identical to, the consumer apps.
 */
class HF_Ai
{
	const DUE_AFTER_DAYS = 6;

	/** engine key => [label, path segment, supports web_search flag, supports country] */
	public static function engines_all()
	{
		return [
			'chat_gpt'   => ['label' => 'ChatGPT',    'path' => 'chat_gpt',   'web_flag' => true,  'country' => true],
			'gemini'     => ['label' => 'Gemini',     'path' => 'gemini',     'web_flag' => true,  'country' => false], // no country option in the API
			'perplexity' => ['label' => 'Perplexity', 'path' => 'perplexity', 'web_flag' => false, 'country' => true],  // search is built in
		];
	}

	public static function engines()
	{
		$all = self::engines_all();
		$want = defined('HELLOFOUND_AI_ENGINES') ? (array)HELLOFOUND_AI_ENGINES : array_keys($all);
		return array_intersect_key($all, array_flip($want));
	}

	/** Model per engine. Override with define('HELLOFOUND_AI_MODELS', ['chat_gpt' => '...', ...]) after listing models in Settings. */
	public static function model($engine)
	{
		$defaults = ['chat_gpt' => 'gpt-4.1', 'gemini' => 'gemini-2.5-flash', 'perplexity' => 'sonar'];
		$cfg = defined('HELLOFOUND_AI_MODELS') ? (array)HELLOFOUND_AI_MODELS : [];
		return $cfg[$engine] ?? $defaults[$engine];
	}

	public static function max_prompts() { return defined('HELLOFOUND_MAX_PROMPTS') ? (int)HELLOFOUND_MAX_PROMPTS : 10; }

	/* ---------- prompts CRUD ---------- */

	public static function prompts($account_id, $active_only = false)
	{
		return HF_Db::rows('SELECT * FROM ' . HF_Db::t('prompts') . ' WHERE account_id=' . (int)$account_id . ($active_only ? ' AND is_active=1' : '') . ' ORDER BY is_active DESC, id ASC');
	}

	public static function active_count($account_id) { return (int)HF_Db::value('SELECT COUNT(*) FROM ' . HF_Db::t('prompts') . ' WHERE is_active=1 AND account_id=' . (int)$account_id); }

	/** @return string|null error */
	public static function add_prompt($account_id, $text, $admin)
	{
		$text = trim(preg_replace('/\s+/', ' ', $text));
		if ($text === '') return 'Prompt is required.';
		if (mb_strlen($text) > 500) return 'Prompts are limited to 500 characters.';
		if (self::active_count($account_id) >= self::max_prompts()) return 'Maximum of ' . self::max_prompts() . ' active prompts reached.';
		HF_Db::insert('prompts', ['account_id' => (int)$account_id, 'prompt' => $text, 'is_active' => 1, 'added_by' => $admin, 'created_at' => HF_Db::now()]);
		return null;
	}

	public static function set_prompt_active($id, $account_id, $active)
	{
		$p = HF_Db::row('SELECT * FROM ' . HF_Db::t('prompts') . ' WHERE id=' . (int)$id . ' AND account_id=' . (int)$account_id);
		if (!$p) return 'Prompt not found.';
		if ($active && !$p['is_active'] && self::active_count($account_id) >= self::max_prompts()) return 'Maximum active prompts reached.';
		HF_Db::update('prompts', ['is_active' => $active ? 1 : 0], (int)$id);
		return null;
	}

	/* ---------- running checks ---------- */

	/** One prompt on one engine. Stores a row. @return string|null error */
	public static function check(array $prompt, $engine, $domain)
	{
		$engines = self::engines_all();
		if (!isset($engines[$engine])) return 'Unknown engine.';
		$e = $engines[$engine];
		$body = ['user_prompt' => $prompt['prompt'], 'model_name' => self::model($engine), 'max_output_tokens' => 700];
		if ($e['web_flag']) $body['web_search'] = true;
		if ($e['country'])  $body['web_search_country_iso_code'] = 'GB';

		[$task, $err] = HF_Dfs::post('/v3/ai_optimization/' . $e['path'] . '/llm_responses/live', $body);
		if ($err) return $e['label'] . ': ' . $err;
		$result = $task['result'][0] ?? [];

		// Answer text and every cited / retrieved URL, wherever this engine puts them.
		$texts = []; $urls = [];
		HF_Dfs::collect($result, 'text', $texts);
		foreach (['direct_url', 'url'] as $k) HF_Dfs::collect(['a' => $result['items'] ?? []], $k, $urls);
		foreach ($result['search_results'] ?? [] as $sr) if (!empty($sr['url'])) $urls[] = $sr['url'];
		$urls = array_values(array_unique(array_filter($urls, 'is_string')));

		$cited_urls = array_values(array_filter($urls, function ($u) use ($domain) { return HF_Dfs::host_matches($u, $domain); }));
		$hosts = [];
		foreach ($urls as $u) { $h = preg_replace('/^www\./', '', strtolower((string)parse_url($u, PHP_URL_HOST))); if ($h !== '' && strpos($h, 'vertexaisearch') === false) $hosts[$h] = true; }

		$answer = mb_strtolower(implode(' ', $texts));
		$label = explode('.', preg_replace('/^www\./', '', strtolower($domain)))[0];
		$mentioned = (strpos($answer, strtolower($domain)) !== false || (strlen($label) >= 6 && strpos($answer, $label) !== false)) ? 1 : 0;

		HF_Db::insert('ai_checks', [
			'account_id' => (int)$prompt['account_id'], 'prompt_id' => (int)$prompt['id'], 'engine' => $engine,
			'model' => substr((string)($result['model_name'] ?? self::model($engine)), 0, 80), 'checked_at' => HF_Db::now(),
			'web_search' => !empty($result['web_search']) ? 1 : 0,
			'cited' => $cited_urls ? 1 : 0, 'mentioned' => $mentioned,
			'cited_urls' => json_encode(array_slice($cited_urls, 0, 10)),
			'sources' => json_encode(array_slice(array_keys($hosts), 0, 20)),
			'cost' => round((float)($task['cost'] ?? 0), 5),
		]);
		return null;
	}

	/** Run prompts x engines not checked in the last DUE_AFTER_DAYS (all if $force). @return array [done, errors[]] */
	public static function run_account($account_id, $force = false)
	{
		$domain = HF_Account::domain($account_id);
		if (!$domain) return [0, ['No domain for this account.']];
		$done = 0; $errors = [];
		foreach (self::prompts($account_id, true) as $p) {
			foreach (array_keys(self::engines()) as $eng) {
				if (!$force && self::checked_recently((int)$p['id'], $eng)) continue;
				$e = self::check($p, $eng, $domain['domain']);
				if ($e) { $errors[] = $e; if (strpos($e, 'credentials') !== false || strpos($e, 'rejected') !== false) return [$done, $errors]; }
				else $done++;
			}
		}
		return [$done, $errors];
	}

	private static function checked_recently($prompt_id, $engine)
	{
		$d = gmdate('Y-m-d H:i:s', strtotime('-' . self::DUE_AFTER_DAYS . ' days'));
		return (bool)HF_Db::value('SELECT id FROM ' . HF_Db::t('ai_checks') . ' WHERE prompt_id=' . (int)$prompt_id . ' AND engine=' . HF_Db::q($engine) . ' AND checked_at > ' . HF_Db::q($d) . ' LIMIT 1');
	}

	public static function planned_calls($account_id) { return self::active_count($account_id) * count(self::engines()); }

	/* ---------- reporting ---------- */

	/**
	 * Per engine: share of prompts (latest check each, last 35 days) where the domain was cited / named.
	 * @return array engine => ['label','prompts','cited','mentioned']
	 */
	public static function summary($account_id)
	{
		$since = gmdate('Y-m-d H:i:s', strtotime('-35 days'));
		$T = HF_Db::t('ai_checks');
		$rows = HF_Db::rows("SELECT c.engine, c.prompt_id, c.cited, c.mentioned FROM $T c
			JOIN (SELECT prompt_id, engine, MAX(id) mid FROM $T WHERE account_id=" . (int)$account_id . " AND checked_at > " . HF_Db::q($since) . " GROUP BY prompt_id, engine) l ON l.mid=c.id
			JOIN " . HF_Db::t('prompts') . ' p ON p.id=c.prompt_id AND p.is_active=1');
		$out = [];
		foreach (self::engines() as $k => $e) $out[$k] = ['label' => $e['label'], 'prompts' => 0, 'cited' => 0, 'mentioned' => 0];
		foreach ($rows as $r) {
			if (!isset($out[$r['engine']])) continue;
			$out[$r['engine']]['prompts']++; $out[$r['engine']]['cited'] += (int)$r['cited']; $out[$r['engine']]['mentioned'] += (int)$r['mentioned'];
		}
		return array_filter($out, function ($o) { return $o['prompts'] > 0; });
	}

	/** Latest result per prompt per engine, for the admin results table. @return array prompt_id => engine => row */
	public static function latest_matrix($account_id)
	{
		$T = HF_Db::t('ai_checks');
		$rows = HF_Db::rows("SELECT c.* FROM $T c JOIN (SELECT prompt_id, engine, MAX(id) mid FROM $T WHERE account_id=" . (int)$account_id . ' GROUP BY prompt_id, engine) l ON l.mid=c.id');
		$m = [];
		foreach ($rows as $r) $m[(int)$r['prompt_id']][$r['engine']] = $r;
		return $m;
	}

	/** Models available from DataForSEO for an engine (free call). @return array [names[], error|null] */
	public static function list_models($engine)
	{
		$engines = self::engines_all();
		if (!isset($engines[$engine])) return [[], 'Unknown engine'];
		[$task, $err] = HF_Dfs::get('/v3/ai_optimization/' . $engines[$engine]['path'] . '/llm_responses/models');
		if ($err) return [[], $err];
		$names = [];
		foreach ($task['result'] ?? [] as $m) if (!empty($m['model_name'])) $names[] = $m['model_name'] . (isset($m['web_search']) && !$m['web_search'] ? ' (no web search)' : '');
		return [$names, null];
	}
}
