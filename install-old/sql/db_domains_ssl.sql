-- DATABASE UPGRADE: adds Domains & SSL expiry monitoring module
-- Run this against the existing `monitor` database.

-- ----------------------------------------------------------------------------------------------
-- DOMAINS (WHOIS expiry monitoring)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE `app_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `status` int(1) NOT NULL,
  `geodata` text NOT NULL,
  `on_map` int(1) NOT NULL,
  `lat` varchar(32) NOT NULL,
  `lng` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `app_domains_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `occurrences` int(10) NOT NULL DEFAULT 1,
  `contacts` text NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `domainid` (`domainid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `app_domains_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `last_notification` datetime NOT NULL,
  `comment` text NOT NULL,
  `ignore` tinyint(1) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `domainid` (`domainid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `app_domains_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domainid` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `expiry_date` varchar(20) NOT NULL,
  `registrar` varchar(255) NOT NULL,
  `days_remaining` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `domainid` (`domainid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


-- ----------------------------------------------------------------------------------------------
-- SSL (certificate expiry monitoring)
-- ----------------------------------------------------------------------------------------------

CREATE TABLE `app_ssl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(512) NOT NULL,
  `status` int(1) NOT NULL,
  `geodata` text NOT NULL,
  `on_map` int(1) NOT NULL,
  `lat` varchar(32) NOT NULL,
  `lng` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `app_ssl_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sslid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `occurrences` int(10) NOT NULL DEFAULT 1,
  `contacts` text NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sslid` (`sslid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `app_ssl_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sslid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `last_notification` datetime NOT NULL,
  `comment` text NOT NULL,
  `ignore` tinyint(1) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sslid` (`sslid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `app_ssl_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sslid` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `expiry_date` varchar(20) NOT NULL,
  `issuer` varchar(255) NOT NULL,
  `days_remaining` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sslid` (`sslid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
