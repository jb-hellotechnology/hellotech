<?php
/** All hello.found tables. Safe to run repeatedly (IF NOT EXISTS). */
class HF_Schema
{
	private static $done = false;

	public static function statements()
	{
		$P = PERCH_DB_PREFIX . 'hellofound_';
		$sql = <<<'SQL'
	CREATE TABLE IF NOT EXISTS `{P}accounts` (
	  `id` int unsigned NOT NULL AUTO_INCREMENT,
	  `member_id` int unsigned NOT NULL,
	  `stripe_customer_id` varchar(50) DEFAULT NULL,
	  `stripe_subscription_id` varchar(50) DEFAULT NULL,
	  `subscription_status` enum('active','trialing','past_due','canceled','incomplete') NOT NULL DEFAULT 'incomplete',
	  `plan_start` datetime DEFAULT NULL,
	  `plan_end` datetime DEFAULT NULL,
	  `onboarding_complete` tinyint(1) NOT NULL DEFAULT 0,
	  `created_at` datetime NOT NULL,
	  `updated_at` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `uq_member` (`member_id`),
	  KEY `idx_customer` (`stripe_customer_id`),
	  KEY `idx_sub` (`stripe_subscription_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}domains` (
	  `id` int unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `domain` varchar(255) NOT NULL,
	  `gsc_site_url` varchar(255) DEFAULT NULL,
	  `fathom_site_id` varchar(20) DEFAULT NULL,
	  `fathom_tracking_code` text,
	  `created_at` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `uq_account` (`account_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}gsc_tokens` (
	  `id` int unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `access_token` text NOT NULL,
	  `refresh_token` text NOT NULL,
	  `token_expires` datetime NOT NULL,
	  `is_valid` tinyint(1) NOT NULL DEFAULT 1,
	  `connected_at` datetime NOT NULL,
	  `connected_by` varchar(100) NOT NULL DEFAULT '',
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `uq_account` (`account_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}keywords` (
	  `id` int unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `keyword` varchar(255) NOT NULL,
	  `target_url` varchar(255) DEFAULT NULL,
	  `category` varchar(100) DEFAULT NULL,
	  `is_active` tinyint(1) NOT NULL DEFAULT 1,
	  `added_by` varchar(100) NOT NULL DEFAULT '',
	  `created_at` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `idx_account` (`account_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}gsc_data` (
	  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `keyword_id` int unsigned DEFAULT NULL,
	  `date_start` date NOT NULL,
	  `date_end` date NOT NULL,
	  `clicks` int NOT NULL DEFAULT 0,
	  `impressions` int NOT NULL DEFAULT 0,
	  `ctr` decimal(5,4) NOT NULL DEFAULT 0,
	  `avg_position` decimal(6,2) NOT NULL DEFAULT 0,
	  `pulled_at` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `idx_lookup` (`account_id`,`keyword_id`,`date_end`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}reports` (
	  `id` int unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `report_month` date NOT NULL,
	  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
	  `summary_text` text,
	  `metrics_snapshot` mediumtext,
	  `created_at` datetime NOT NULL,
	  `published_at` datetime DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  UNIQUE KEY `uq_month` (`account_id`,`report_month`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}admin_notes` (
	  `id` int unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `note` text NOT NULL,
	  `created_by` varchar(100) NOT NULL DEFAULT '',
	  `created_at` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `idx_account` (`account_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
	CREATE TABLE IF NOT EXISTS `{P}rank_checks` (
	  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `keyword_id` int unsigned NOT NULL,
	  `checked_on` date NOT NULL,
	  `position` smallint unsigned DEFAULT NULL,
	  `url` varchar(500) DEFAULT NULL,
	  `ai_overview_present` tinyint(1) NOT NULL DEFAULT 0,
	  `ai_overview_cited` tinyint(1) NOT NULL DEFAULT 0,
	  `checked_at` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `idx_lookup` (`account_id`,`keyword_id`,`checked_on`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}prompts` (
	  `id` int unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `prompt` varchar(500) NOT NULL,
	  `is_active` tinyint(1) NOT NULL DEFAULT 1,
	  `added_by` varchar(100) NOT NULL DEFAULT '',
	  `created_at` datetime NOT NULL,
	  PRIMARY KEY (`id`),
	  KEY `idx_account` (`account_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

	CREATE TABLE IF NOT EXISTS `{P}ai_checks` (
	  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
	  `account_id` int unsigned NOT NULL,
	  `prompt_id` int unsigned NOT NULL,
	  `engine` varchar(20) NOT NULL,
	  `model` varchar(80) NOT NULL DEFAULT '',
	  `checked_at` datetime NOT NULL,
	  `web_search` tinyint(1) NOT NULL DEFAULT 0,
	  `cited` tinyint(1) NOT NULL DEFAULT 0,
	  `mentioned` tinyint(1) NOT NULL DEFAULT 0,
	  `cited_urls` text,
	  `sources` text,
	  `cost` decimal(8,5) NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`),
	  KEY `idx_lookup` (`account_id`,`engine`,`checked_at`),
	  KEY `idx_prompt` (`prompt_id`,`engine`,`checked_at`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SQL;
		$out = [];
		foreach (explode(';', str_replace('{P}', $P, $sql)) as $s) { $s = trim($s); if ($s !== '') $out[] = $s; }
		return $out;
	}

	/** Create any missing tables. One cheap SHOW TABLES per request once the newest table exists. */
	public static function ensure()
	{
		if (self::$done) return;
		self::$done = true;
		$sentinel = PERCH_DB_PREFIX . 'hellofound_ai_checks';
		if (HF_Db::value('SHOW TABLES LIKE ' . HF_Db::q($sentinel))) return;
		foreach (self::statements() as $s) HF_Db::exec($s);
	}
}
