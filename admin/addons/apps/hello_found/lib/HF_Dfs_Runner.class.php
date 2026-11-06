<?php
/** Runs all due DataForSEO checks (ranks + AI) for every active subscriber. Used by the scheduler and cron script. */
class HF_Dfs_Runner
{
	public static function run_all()
	{
		if (!HF_Dfs::configured()) return ['errors' => 0, 'message' => 'DataForSEO not configured; skipped.'];
		@set_time_limit(0);
		$ids = HF_Db::rows('SELECT a.id FROM ' . HF_Db::t('accounts') . ' a JOIN ' . HF_Db::t('domains') . " d ON d.account_id=a.id WHERE a.subscription_status IN ('active','trialing')");
		$ranks = $ai = $errors = 0;
		foreach ($ids as $r) {
			[$n, $e] = HF_Rank::run_account((int)$r['id']);   $ranks += $n; $errors += count($e);
			[$n, $e] = HF_Ai::run_account((int)$r['id']);     $ai += $n;    $errors += count($e);
			foreach (array_merge($e) as $msg) hf_log('dfs check: ' . $msg);
		}
		return ['errors' => $errors, 'message' => "Rank checks: $ranks, AI checks: $ai, errors: $errors."];
	}
}
