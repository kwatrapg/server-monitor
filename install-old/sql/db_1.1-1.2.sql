-- DATABASE UPGRADE FROM 1.1 to 1.2

INSERT INTO `core_config` (`name`, `value`) VALUES ('google_maps_api_key', '');


ALTER TABLE `app_checks` ADD `geodata` TEXT NOT NULL AFTER `status`;
ALTER TABLE `app_websites` ADD `geodata` TEXT NOT NULL AFTER `status`;


ALTER TABLE `app_servers` ADD `on_map` INT(1) NOT NULL AFTER `geodata`;
ALTER TABLE `app_checks` ADD `on_map` INT(1) NOT NULL AFTER `geodata`;
ALTER TABLE `app_websites` ADD `on_map` INT(1) NOT NULL AFTER `geodata`;


ALTER TABLE `app_checks` ADD `lat` VARCHAR(32) NOT NULL AFTER `on_map`, ADD `lng` VARCHAR(32) NOT NULL AFTER `lat`;
ALTER TABLE `app_servers` ADD `lat` VARCHAR(32) NOT NULL AFTER `on_map`, ADD `lng` VARCHAR(32) NOT NULL AFTER `lat`;
ALTER TABLE `app_websites` ADD `lat` VARCHAR(32) NOT NULL AFTER `on_map`, ADD `lng` VARCHAR(32) NOT NULL AFTER `lat`;


UPDATE `app_servers` SET `on_map`=1;
