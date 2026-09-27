-- Server alert actions. Safe to run more than once.
--
-- An action is attached to one server alert rule and fires when that alert opens
-- an incident: either a webhook (POSTed by this app, from the cron) or a shell
-- command. Commands are NOT executed remotely by this app - a run row is queued
-- here and the server's own agent picks it up on its next cycle (actionsconfig.php),
-- runs it locally and reports back over the same HMAC-signed channel custom
-- commands already use (commandresult.php).

CREATE TABLE IF NOT EXISTS `app_servers_alert_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(16) NOT NULL,
  `webhook_url` varchar(2048) NOT NULL DEFAULT '',
  `command` text NOT NULL,
  `timeout_seconds` int(11) NOT NULL DEFAULT 30,
  `status` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`),
  KEY `alertid` (`alertid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- One row per time an action fired. For commands this doubles as the agent's
-- work queue: pending -> dispatched (handed to the agent) -> done / failed.
CREATE TABLE IF NOT EXISTS `app_servers_alert_action_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actionid` int(11) NOT NULL,
  `serverid` int(11) NOT NULL,
  `incidentid` int(11) NOT NULL DEFAULT 0,
  `type` varchar(16) NOT NULL,
  `status` varchar(16) NOT NULL,
  `result_code` int(11) DEFAULT NULL,
  `output` varchar(1000) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `finished` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `actionid` (`actionid`),
  KEY `server_status` (`serverid`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
