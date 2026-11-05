<?php
/**
 * Optional CLI alternative to Perch's scheduler:
 *   0 4 * * * php /path/to/admin/addons/apps/hello_found/cron/pull-gsc-data.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'hellotechnology.co.uk';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'hellotechnology.co.uk';
include(__DIR__ . '/../../../../runtime.php');
require_once __DIR__ . '/../bootstrap.php';

foreach (HF_Gsc::pull_all() as $id => $msg) echo date('c') . " account $id: $msg\n";
