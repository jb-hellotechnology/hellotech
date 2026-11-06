<?php
class HF_GscData
{
	const WINDOW_DAYS = 28;

	public static function store($account_id, $keyword_id, $start, $end, $clicks, $impr, $position)
	{
		$kw  = $keyword_id === null ? 'keyword_id IS NULL' : 'keyword_id=' . (int)$keyword_id;
		HF_Db::exec('DELETE FROM ' . HF_Db::t('gsc_data') . ' WHERE account_id=' . (int)$account_id . " AND $kw AND date_start=" . HF_Db::q($start) . ' AND date_end=' . HF_Db::q($end));
		$ctr = $impr > 0 ? min(0.9999, $clicks / $impr) : 0;
		HF_Db::insert('gsc_data', [
			'account_id' => (int)$account_id, 'keyword_id' => $keyword_id, 'date_start' => $start, 'date_end' => $end,
			'clicks' => (int)$clicks, 'impressions' => (int)$impr, 'ctr' => round($ctr, 4),
			'avg_position' => round($position, 2), 'pulled_at' => HF_Db::now(),
		]);
	}

	public static function prune($days = 150)
	{
		HF_Db::exec('DELETE FROM ' . HF_Db::t('gsc_data') . ' WHERE date_end < ' . HF_Db::q(gmdate('Y-m-d', strtotime("-$days days"))));
	}

	/** Latest window row for site (keyword null) or a keyword, plus the prior 28-day window. */
	public static function latest_pair($account_id, $keyword_id = null)
	{
		$kw = $keyword_id === null ? 'keyword_id IS NULL' : 'keyword_id=' . (int)$keyword_id;
		$T  = HF_Db::t('gsc_data');
		$cur = HF_Db::row("SELECT * FROM $T WHERE account_id=" . (int)$account_id . " AND $kw ORDER BY date_end DESC, id DESC LIMIT 1");
		if (!$cur) return [null, null];
		$prior_end = gmdate('Y-m-d', strtotime($cur['date_end'] . ' -' . self::WINDOW_DAYS . ' days'));
		$prev = HF_Db::row("SELECT * FROM $T WHERE account_id=" . (int)$account_id . " AND $kw AND date_end=" . HF_Db::q($prior_end) . ' ORDER BY id DESC LIMIT 1');
		return [$cur, $prev];
	}

	/**
	 * One row per active keyword. Position comes from the weekly DataForSEO UK rank check when there is
	 * one ('source' => 'rank'; null position = outside the top N); otherwise from Search Console's average
	 * position ('source' => 'gsc'). Clicks/impressions always come from Search Console.
	 * 'change' > 0 means the keyword moved UP (better).
	 */
	public static function keyword_table($account_id)
	{
		$out = [];
		foreach (HF_Keyword::for_account($account_id, true) as $kw) {
			[$cur, $prev] = self::latest_pair($account_id, (int)$kw['id']);
			[$rc, $rp] = HF_Rank::latest_pair((int)$kw['id']);

			if ($rc) {
				$source = 'rank';
				$pos  = $rc['position'] !== null ? (float)$rc['position'] : null;
				$ppos = $rp && $rp['position'] !== null ? (float)$rp['position'] : null;
				$ranked_before = $rp !== null;
				$change = ($pos !== null && $ppos !== null) ? round($ppos - $pos, 1) : null;
				$url = $rc['url'];
			} else {
				$source = 'gsc';
				$pos  = ($cur && $cur['impressions'] > 0) ? (float)$cur['avg_position'] : null;
				$ppos = ($prev && $prev['impressions'] > 0) ? (float)$prev['avg_position'] : null;
				$change = ($pos !== null && $ppos !== null) ? round($ppos - $pos, 1) : null;
				$url = null;
			}
			$out[] = [
				'keyword' => $kw['keyword'], 'category' => $kw['category'], 'target_url' => $kw['target_url'],
				'source' => $source, 'position' => $pos, 'prior_position' => $ppos, 'change' => $change, 'ranking_url' => $url,
				'checked_on' => $rc['checked_on'] ?? null,
				'ai_overview' => $rc ? ['present' => (int)$rc['ai_overview_present'], 'cited' => (int)$rc['ai_overview_cited']] : null,
				'clicks' => $cur ? (int)$cur['clicks'] : 0, 'impressions' => $cur ? (int)$cur['impressions'] : 0,
			];
		}
		return $out;
	}

	public static function site_overview($account_id)
	{
		[$cur, $prev] = self::latest_pair($account_id, null);
		return $cur ? ['current' => $cur, 'prior' => $prev] : null;
	}
}
