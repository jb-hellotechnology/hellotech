<?php
	// Perch's built-in scheduler: pull Search Console data once a day (1440 min).
	PerchScheduledTasks::register_task('hello_found', 'pull_gsc', 1440, 'scheduled_hello_found_pull_gsc');

	function scheduled_hello_found_pull_gsc($last_run)
	{
		require_once __DIR__ . '/bootstrap.php';
		$results = HF_Gsc::pull_all();
		return ['result' => 'OK', 'message' => count($results) ? 'Pulled ' . count($results) . ' account(s).' : 'No accounts to pull.'];
	}
