-- DATABASE UPGRADE FROM 1.2 to 1.3


ALTER TABLE `app_servers_history` ADD INDEX(`timestamp`);
ALTER TABLE `app_websites_history` ADD INDEX(`timestamp`);
ALTER TABLE `app_checks_history` ADD INDEX(`timestamp`);
