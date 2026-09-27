-- Independent replay-nonce column for actionresult.php, separate from
-- app_servers.last_agent_nonce_at (agent.php's own): the agent POSTs to both
-- within the same second, and a shared column would make the second POST look
-- like a replay of the first. Safe to run more than once.
ALTER TABLE `app_servers` ADD COLUMN IF NOT EXISTS `last_action_nonce_at` datetime DEFAULT NULL;
