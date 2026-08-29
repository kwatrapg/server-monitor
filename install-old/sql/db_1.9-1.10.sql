ALTER TABLE `app_checks_alerts` ADD `repeats` INT(5) NOT NULL DEFAULT '0' AFTER `contacts`;
ALTER TABLE `app_servers_alerts` ADD `repeats` INT(5) NOT NULL DEFAULT '0' AFTER `contacts`;
ALTER TABLE `app_websites_alerts` ADD `repeats` INT(5) NOT NULL DEFAULT '0' AFTER `contacts`;

ALTER TABLE `app_checks_incidents` ADD `repeats` INT(5) NOT NULL DEFAULT '0' AFTER `end_time`;
ALTER TABLE `app_servers_incidents` ADD `repeats` INT(5) NOT NULL DEFAULT '0' AFTER `end_time`;
ALTER TABLE `app_websites_incidents` ADD `repeats` INT(5) NOT NULL DEFAULT '0' AFTER `end_time`;


ALTER TABLE `app_checks_incidents` ADD `last_notification` DATETIME NOT NULL AFTER `repeats`;
ALTER TABLE `app_servers_incidents` ADD `last_notification` DATETIME NOT NULL AFTER `repeats`;
ALTER TABLE `app_websites_incidents` ADD `last_notification` DATETIME NOT NULL AFTER `repeats`;


ALTER TABLE `app_checks_incidents` ADD `comment` TEXT NOT NULL AFTER `status`; 
ALTER TABLE `app_servers_incidents` ADD `comment` TEXT NOT NULL AFTER `status`; 
ALTER TABLE `app_websites_incidents` ADD `comment` TEXT NOT NULL AFTER `status`; 


ALTER TABLE `app_checks_incidents` ADD `ignore` TINYINT(1) NOT NULL DEFAULT '0' AFTER `comment`; 
ALTER TABLE `app_servers_incidents` ADD `ignore` TINYINT(1) NOT NULL DEFAULT '0' AFTER `comment`; 
ALTER TABLE `app_websites_incidents` ADD `ignore` TINYINT(1) NOT NULL DEFAULT '0' AFTER `comment`; 


INSERT INTO `core_notifications` (`id`, `name`, `subject`, `message`, `info`) VALUES (NULL, 'nMon Incident Unresolved', '{subject}', '<p>Hello {contact},</p><p><b>{message}</b></p><p><br>Best regards,<br>{company}<br></p>', '');



