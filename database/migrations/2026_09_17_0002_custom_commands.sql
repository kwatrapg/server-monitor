-- Custom command monitoring. Safe to run more than once.
--
-- A command is both its own check and its own alert: the agent runs it locally
-- (on the monitored server, never over SSH from this app) and reports back the
-- exit code + truncated output over the same HMAC-signed channel agent.php
-- already uses for metrics. Non-zero exit opens an incident and notifies,
-- mirroring every other incident type in this app.

CREATE TABLE IF NOT EXISTS `app_servers_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `command` text NOT NULL,
  `timeout_seconds` int(11) NOT NULL DEFAULT 30,
  `contacts` text NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `status` int(1) NOT NULL DEFAULT 1,
  `last_checked` datetime DEFAULT NULL,
  `last_exit_code` int(11) DEFAULT NULL,
  `last_output` varchar(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- Independent replay-nonce column for commandresult.php, separate from
-- app_servers.last_agent_nonce_at (agent.php's own): the agent may POST to
-- both endpoints within the same second, and a shared column would make the
-- second POST look like a replay of the first.
ALTER TABLE `app_servers` ADD COLUMN IF NOT EXISTS `last_command_nonce_at` datetime DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `app_servers_commands_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `commandid` int(11) NOT NULL,
  `exit_code` int(11) NOT NULL,
  `output` varchar(1000) NOT NULL DEFAULT '',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `last_notification` datetime NOT NULL,
  `comment` text NOT NULL,
  `ignore` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`),
  KEY `commandid` (`commandid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
