-- DATABASE UPGRADE FROM 1.0 to 1.1

ALTER TABLE `app_contacts` ADD `pushover` VARCHAR(255) NOT NULL AFTER `twitter`;
ALTER TABLE `app_alertlog` ADD `pushover` VARCHAR(255) NOT NULL AFTER `twitter`;

INSERT INTO `core_config` (`name`, `value`) VALUES ('pushover_apitoken', '');
