-- DATABASE UPGRADE FROM 1.8 to 1.9


ALTER TABLE `core_users` ADD `groups` VARCHAR(535) NOT NULL DEFAULT 'a:1:{i:0;s:1:\"0\";}' AFTER `password`;
ALTER TABLE `core_users` CHANGE `groups` `groups` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `app_contacts` ADD `groupid` INT(11) NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `app_pages` ADD `groupid` INT(11) NOT NULL DEFAULT '0' AFTER `id`;
