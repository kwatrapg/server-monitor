-- SaaS license: config keys backing the Settings > License tab and cached
-- verification result (see includes/classes/class.license.php). Settings::update()
-- runs UPDATE, not upsert, so every key it can write must pre-exist here.
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES
('license_key', ''),
('license_fingerprint', ''),
('license_valid', ''),
('license_reason', ''),
('license_plan', ''),
('license_plan_name', ''),
('license_max_servers', ''),
('license_max_websites', ''),
('license_max_checks', ''),
('license_expires_at', ''),
('license_last_checked_at', '');

-- Status messages for the license save/verify actions on the Settings page.
INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 42, 'success', 'License verified successfully!'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 42);

INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 43, 'danger', 'License saved, but verification failed. Double-check the key and try again.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 43);

INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 12, 'danger', 'Your license plan limit has been reached for this resource type. Upgrade your plan to add more.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 12);
