<?php
	// Prevent running directly:
	if (!defined('PERCH_DB_PREFIX')) exit;

	require_once __DIR__ . '/bootstrap.php';
	foreach (HF_Schema::statements() as $statement) $this->db->execute($statement);

	$API = new PerchAPI(1.0, 'hello_found');
	$UserPrivileges = $API->get('UserPrivileges');
	$UserPrivileges->create_privilege('hello_found', 'Access hello.found clients');
	$UserPrivileges->create_privilege('hello_found.settings', 'View hello.found settings');

	$result = $this->db->get_value('SHOW TABLES LIKE "' . PERCH_DB_PREFIX . 'hellofound_accounts"');
	return $result;
