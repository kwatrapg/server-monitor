-- Sentruo security hardening migration.
-- Idempotent where MariaDB allows (IF NOT EXISTS). Safe to re-run.
-- Covers: F-06 (reset-key expiry), F-08 (auth throttle), A-4 (per-check callback key),
--         F-13 (agent HMAC replay defence), F-03 (force password reset flag).

-- --- F-08: login / password-reset throttling ------------------------------------
CREATE TABLE IF NOT EXISTS `core_auththrottle` (
  `throttle_key` VARCHAR(96) NOT NULL,
  `fail_count`   INT(11)      NOT NULL DEFAULT 0,
  `last_fail`    DATETIME     NOT NULL,
  PRIMARY KEY (`throttle_key`),
  KEY `last_fail` (`last_fail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --- F-06: password-reset key expiry + one-time use ---------------------------
ALTER TABLE `core_users`
  ADD COLUMN IF NOT EXISTS `resetkey_expires` DATETIME NULL DEFAULT NULL AFTER `resetkey`;

-- --- F-03: force a password change on next login for flagged accounts --------
ALTER TABLE `core_users`
  ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `resetkey_expires`;

-- --- F-05: track last successful auth (rehash / audit) ----------------------
ALTER TABLE `core_users`
  ADD COLUMN IF NOT EXISTS `last_login_at` DATETIME NULL DEFAULT NULL AFTER `must_change_password`;

-- --- A-4: per-check callback secret (replaces key == app_checks.host) --------
ALTER TABLE `app_checks`
  ADD COLUMN IF NOT EXISTS `callbackkey` VARCHAR(64) NULL DEFAULT NULL AFTER `host`;

-- --- F-13: agent replay-attack defence -------------------------------------
ALTER TABLE `app_servers`
  ADD COLUMN IF NOT EXISTS `last_agent_nonce_at` DATETIME NULL DEFAULT NULL AFTER `serverkey`;

-- --- F-03/F-04: scrub live secrets & default artefacts out of the DB --------
--   (Values are set from the environment now; keep the rows so the settings
--    screen can show "managed via environment".)
UPDATE `core_config` SET `value` = '' WHERE `name` IN (
  'email_smtp_password', 'sms_password', 'sms_api_id',
  'twitter_apisecret', 'twitter_tokensecret', 'pushover_apitoken'
);

-- Invalidate every stored session and outstanding reset key (F-03).
UPDATE `core_users` SET `sessionid` = '', `resetkey` = '', `resetkey_expires` = NULL;

-- Flag any account still on the shipped default SHA-1 hash of "admin" / "123456".
UPDATE `core_users`
   SET `must_change_password` = 1
 WHERE `password` IN (
   'd033e22ae348aeb5660fc2140aec35850c4da997', -- sha1('admin')
   '7c4a8d09ca3762af61e59520943dc26494f8941b'  -- sha1('123456')
 );

-- Rebrand the seeded notification templates (F-04 / rebrand).
UPDATE `core_notifications` SET `name` = 'Incident Alert'       WHERE `id` = 3 AND `name` = 'nMon Incident Alert';
UPDATE `core_notifications` SET `name` = 'Incident Unresolved'  WHERE `id` = 4 AND `name` = 'nMon Incident Unresolved';
