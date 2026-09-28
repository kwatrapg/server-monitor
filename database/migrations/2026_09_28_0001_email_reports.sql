-- Designed HTML emails + scheduled health report. Safe to run more than once.

-- Settings > Reports. Settings::update() runs UPDATE, not upsert, so every key
-- it can write must pre-exist here.
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES
('report_enabled', 'false'),
('report_interval', '24'),
('report_contacts', 'a:0:{}'),
('report_last_sent', '');

INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 44, 'success', 'Health report sent.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 44);

INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 45, 'danger', 'Health report was not sent. Check Settings > Email and that the selected recipients have an email address.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 45);

-- Incident emails are now a complete designed card (greeting and sign-off
-- included, see MailTemplate::incident()), so the stock wrapper templates become
-- a bare {message}. Only touches templates still at their original default - a
-- customised template is left exactly as the admin wrote it.
UPDATE `core_notifications` SET `message` = '{message}'
WHERE `id` IN (3, 4)
  AND `message` = '<p>Hello {contact},</p><p><b>{message}</b></p><p><br>Best regards,<br>{company}<br></p>';
