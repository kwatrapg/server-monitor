-- Domain and SSL monitoring were missing from the license plan limits
-- (only servers/websites/checks were tracked) — see class.license.php.
INSERT IGNORE INTO `core_config` (`name`, `value`) VALUES
('license_max_domains', ''),
('license_max_ssl', '');
