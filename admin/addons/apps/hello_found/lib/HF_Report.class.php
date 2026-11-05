<?php
class HF_Report
{
	public static function find($id, $account_id) { return HF_Db::row('SELECT * FROM ' . HF_Db::t('reports') . ' WHERE id=' . (int)$id . ' AND account_id=' . (int)$account_id . ' LIMIT 1'); }

	public static function for_account($account_id, $published_only = false)
	{
		return HF_Db::rows('SELECT * FROM ' . HF_Db::t('reports') . ' WHERE account_id=' . (int)$account_id
			. ($published_only ? " AND status='published'" : '') . ' ORDER BY report_month DESC');
	}

	public static function latest_published($account_id)
	{
		$r = HF_Db::row('SELECT * FROM ' . HF_Db::t('reports') . ' WHERE account_id=' . (int)$account_id . " AND status='published' ORDER BY report_month DESC LIMIT 1");
		if ($r) $r['metrics'] = json_decode($r['metrics_snapshot'] ?? '', true) ?: null;
		return $r;
	}

	/** Snapshot of the numbers as they stand right now. */
	public static function snapshot($account_id)
	{
		$site = HF_GscData::site_overview($account_id);
		return [
			'captured_at' => HF_Db::now(),
			'site' => $site ? [
				'clicks' => (int)$site['current']['clicks'], 'impressions' => (int)$site['current']['impressions'],
				'ctr' => (float)$site['current']['ctr'], 'avg_position' => (float)$site['current']['avg_position'],
				'prior_clicks' => $site['prior'] ? (int)$site['prior']['clicks'] : null,
				'prior_impressions' => $site['prior'] ? (int)$site['prior']['impressions'] : null,
				'window_end' => $site['current']['date_end'],
			] : null,
			'keywords' => HF_GscData::keyword_table($account_id),
		];
	}

	/** Create (or return existing) draft for a month. @return int report id */
	public static function create_draft($account_id, $month_first = null)
	{
		$month = $month_first ?: gmdate('Y-m-01');
		$existing = HF_Db::row('SELECT id FROM ' . HF_Db::t('reports') . ' WHERE account_id=' . (int)$account_id . ' AND report_month=' . HF_Db::q($month));
		if ($existing) return (int)$existing['id'];
		return (int)HF_Db::insert('reports', [
			'account_id' => (int)$account_id, 'report_month' => $month, 'status' => 'draft',
			'summary_text' => '', 'metrics_snapshot' => json_encode(self::snapshot($account_id)), 'created_at' => HF_Db::now(),
		]);
	}

	public static function save_draft($id, $account_id, $summary, $refresh_metrics = false)
	{
		$r = self::find($id, $account_id);
		if (!$r || $r['status'] !== 'draft') return false;
		$data = ['summary_text' => $summary];
		if ($refresh_metrics) $data['metrics_snapshot'] = json_encode(self::snapshot($account_id));
		HF_Db::update('reports', $data, (int)$id);
		return true;
	}

	/** Freeze current numbers into the report and make it visible to the customer. */
	public static function publish($id, $account_id)
	{
		$r = self::find($id, $account_id);
		if (!$r || $r['status'] !== 'draft') return false;
		HF_Db::update('reports', [
			'status' => 'published', 'published_at' => HF_Db::now(), 'metrics_snapshot' => json_encode(self::snapshot($account_id)),
		], (int)$id);
		return true;
	}
}
