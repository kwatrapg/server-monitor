-- Split the generic "Authentication Failed!" message (code 1200) so a weak new
-- password shows its own status instead of masquerading as a wrong-password /
-- wrong-current-password error. Used by Profile::edit() and resetPassword().
INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 1202, 'danger', 'Password must be at least 12 characters and include upper-case, lower-case and a number.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 1202);
