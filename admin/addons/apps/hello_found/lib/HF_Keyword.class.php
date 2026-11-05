<?php
class HF_Keyword
{
	public static function for_account($account_id, $active_only = false)
	{
		return HF_Db::rows('SELECT * FROM ' . HF_Db::t('keywords') . ' WHERE account_id=' . (int)$account_id
			. ($active_only ? ' AND is_active=1' : '') . ' ORDER BY is_active DESC, keyword ASC');
	}

	public static function active_count($account_id)
	{
		return (int)HF_Db::value('SELECT COUNT(*) FROM ' . HF_Db::t('keywords') . ' WHERE is_active=1 AND account_id=' . (int)$account_id);
	}

	public static function find($id, $account_id)
	{
		return HF_Db::row('SELECT * FROM ' . HF_Db::t('keywords') . ' WHERE id=' . (int)$id . ' AND account_id=' . (int)$account_id . ' LIMIT 1');
	}

	/** @return string|null error message, null on success */
	public static function add($account_id, $keyword, $category, $target_url, $admin)
	{
		$keyword = trim(preg_replace('/\s+/', ' ', $keyword));
		if ($keyword === '') return 'Keyword is required.';
		if (self::active_count($account_id) >= (int)HELLOFOUND_MAX_KEYWORDS) return 'Maximum of ' . (int)HELLOFOUND_MAX_KEYWORDS . ' active keywords reached.';
		$exists = HF_Db::value('SELECT id FROM ' . HF_Db::t('keywords') . ' WHERE account_id=' . (int)$account_id . ' AND keyword=' . HF_Db::q($keyword) . ' LIMIT 1');
		if ($exists) return 'That keyword is already tracked.';
		HF_Db::insert('keywords', [
			'account_id' => (int)$account_id, 'keyword' => $keyword,
			'target_url' => $target_url !== '' ? $target_url : null,
			'category'   => $category !== '' ? $category : null,
			'is_active'  => 1, 'added_by' => $admin, 'created_at' => HF_Db::now(),
		]);
		return null;
	}

	public static function update($id, $account_id, $category, $target_url)
	{
		if (!self::find($id, $account_id)) return;
		HF_Db::update('keywords', ['category' => $category !== '' ? $category : null, 'target_url' => $target_url !== '' ? $target_url : null], (int)$id);
	}

	/** @return string|null error */
	public static function set_active($id, $account_id, $active)
	{
		$kw = self::find($id, $account_id);
		if (!$kw) return 'Keyword not found.';
		if ($active && !$kw['is_active'] && self::active_count($account_id) >= (int)HELLOFOUND_MAX_KEYWORDS) return 'Maximum active keywords reached.';
		HF_Db::update('keywords', ['is_active' => $active ? 1 : 0], (int)$id);
		return null;
	}
}
