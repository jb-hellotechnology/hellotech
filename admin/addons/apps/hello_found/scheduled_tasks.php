<?php
	// Perch's built-in scheduler runs these daily. DataForSEO checks are "due if not checked in 6 days"
	// per keyword / prompt, so they come out roughly weekly and an interrupted run resumes next day.
	PerchScheduledTasks::register_task('hello_found', 'pull_gsc', 1440, 'scheduled_hello_found_pull_gsc');
	PerchScheduledTasks::register_task('hello_found', 'dfs_checks', 1440, 'scheduled_hello_found_dfs_checks');

	function scheduled_hello_found_pull_gsc($last_run)
	{
		require_once __DIR__ . '/bootstrap.php';
		$results = HF_Gsc::pull_all();
		return ['result' => 'OK', 'message' => count($results) ? 'Pulled ' . count($results) . ' account(s).' : 'No accounts to pull.'];
	}

	function scheduled_hello_found_dfs_checks($last_run)
	{
		require_once __DIR__ . '/bootstrap.php';
		HF_Schema::ensure();
		$r = HF_Dfs_Runner::run_all();
		return ['result' => $r['errors'] ? 'FAILED' : 'OK', 'message' => $r['message']];
	}
