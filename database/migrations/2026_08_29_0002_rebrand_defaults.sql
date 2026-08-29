-- Sentruo rebrand: default identity for fresh installs. Existing installs keep
-- whatever the admin has set (only the shipped nMon / "Server Monitor" defaults change).
UPDATE `core_config` SET `value` = 'Sentruo'
  WHERE `name` = 'app_name'     AND `value` IN ('<b>n</b>Mon', 'nMon', 'Server Monitor', '');
UPDATE `core_config` SET `value` = 'Sentruo'
  WHERE `name` = 'company_name' AND `value` IN ('nMon Company', 'Server Monitor', '');
UPDATE `core_config` SET `value` = 'Sentruo'
  WHERE `name` = 'email_from_name' AND `value` IN ('nMon', 'Server-Monitor', 'Server Monitor', '');

-- Status message for the forced-password-change redirect (VAPT F-03).
INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 1201, 'warning', 'You must set a new password before continuing.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 1201);
