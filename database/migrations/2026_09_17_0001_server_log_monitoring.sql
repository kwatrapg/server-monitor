-- Server Log Monitoring module (control plane only). Safe to run more than once.
--
-- Raw log lines are NEVER stored here - they live in Loki (or another LogStore
-- backend, see includes/classes/class.logstore.php). These tables hold only the
-- small, bounded control plane: what to collect, the alert rules, the resulting
-- incidents, and shipper health.

-- ----------------------------------------------------------------------------------------------
-- LOG SOURCES (what to collect per server, and under which policy)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_servers_logsources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `path_glob` varchar(512) NOT NULL,
  `mode` varchar(16) NOT NULL DEFAULT 'errors_only',
  `include_regex` text NOT NULL,
  `exclude_regex` text NOT NULL,
  `multiline_pattern` varchar(255) NOT NULL DEFAULT '',
  `rate_limit` int(11) NOT NULL DEFAULT 1000,
  `sample_rate` int(11) NOT NULL DEFAULT 1,
  `labels` text NOT NULL,
  `status` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ----------------------------------------------------------------------------------------------
-- LOG ALERT RULES
-- ----------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_servers_logs_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `sourceid` int(11) NOT NULL DEFAULT 0,
  `type` varchar(25) NOT NULL,
  `pattern` text NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `window_minutes` int(11) NOT NULL DEFAULT 5,
  `occurrences` int(10) NOT NULL DEFAULT 1,
  `contacts` text NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `last_evaluated` datetime DEFAULT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ----------------------------------------------------------------------------------------------
-- LOG INCIDENTS (same semantics as app_servers_incidents so UI + notifications carry over)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_servers_logs_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `sample_line` text NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `last_notification` datetime NOT NULL,
  `comment` text NOT NULL,
  `ignore` tinyint(1) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ----------------------------------------------------------------------------------------------
-- SHIPPER HEALTH (UPDATEd in place, never accumulated: ~500 rows at 100 servers x 5 sources)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_servers_logs_agentstate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `sourceid` int(11) NOT NULL,
  `last_seen` datetime DEFAULT NULL,
  `lines_shipped` bigint(20) NOT NULL DEFAULT 0,
  `lines_dropped` bigint(20) NOT NULL DEFAULT 0,
  `bytes_shipped` bigint(20) NOT NULL DEFAULT 0,
  `last_error` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `server_source` (`serverid`,`sourceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ----------------------------------------------------------------------------------------------
-- ROLLUPS (pre-aggregated hourly counts; not yet read by the app - reserved for Phase 3)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_servers_logs_rollup` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `sourceid` int(11) NOT NULL,
  `bucket` datetime NOT NULL,
  `level` varchar(16) NOT NULL,
  `lines` bigint(20) NOT NULL DEFAULT 0,
  `bytes` bigint(20) NOT NULL DEFAULT 0,
  `dropped` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bucket_stream` (`serverid`,`sourceid`,`bucket`,`level`),
  KEY `bucket` (`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ----------------------------------------------------------------------------------------------
-- RETENTION POLICIES (reserved for Phase 3 - not yet read by the app)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_logs_retention_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `selector` varchar(512) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 10,
  `period_days` int(11) NOT NULL DEFAULT 30,
  `status` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ----------------------------------------------------------------------------------------------
-- BACKUP LEDGER (reserved for Phase 3 - not yet read by the app)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_logs_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `started` datetime NOT NULL,
  `finished` datetime DEFAULT NULL,
  `kind` varchar(16) NOT NULL,
  `destination` varchar(512) NOT NULL,
  `bytes` bigint(20) NOT NULL DEFAULT 0,
  `chunk_count` bigint(20) NOT NULL DEFAULT 0,
  `manifest_sha256` varchar(64) NOT NULL DEFAULT '',
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `error` text NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `started` (`started`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- ----------------------------------------------------------------------------------------------
-- PER-SERVER PUSH CREDENTIAL
-- ----------------------------------------------------------------------------------------------

ALTER TABLE `app_servers` ADD COLUMN IF NOT EXISTS `logs_token` varchar(64) NOT NULL DEFAULT '';

-- ----------------------------------------------------------------------------------------------
-- CONFIGURATION
-- ----------------------------------------------------------------------------------------------

INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('log_backend', 'loki');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('loki_url', 'http://127.0.0.1:3100');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('loki_read_token', '');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('loki_push_url', '');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('log_live_poll_seconds', '3');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('serverlog_retention_days', '30');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('serverlog_error_retention_days', '90');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('log_rollup_retention_days', '30');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('logbackup_enabled', 'false');
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES ('logbackup_destination', '');
