<?php
	if ($CurrentUser->logged_in() && $CurrentUser->has_priv('hello_found')) {
		$this->register_app('hello_found', 'hello.found', 10, 'Managed SEO & AI search subscriptions', '1.0');
		$this->require_version('hello_found', '3.1.2');
	}

	require_once __DIR__ . '/bootstrap.php';
