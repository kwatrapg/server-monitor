-- DATABASE UPGRADE FROM 1.7 to 1.8


INSERT INTO `core_config` (`name`, `value`) VALUES ('xss_filtering', 'false');
