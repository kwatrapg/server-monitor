-- Status message for logo/favicon upload validation failures on the General
-- settings tab (invalid image type or file too large).
INSERT INTO `core_statuses` (`code`, `type`, `message`)
SELECT 41, 'danger', 'Invalid image. Please upload a PNG or JPG file within the size limit.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `core_statuses` WHERE `code` = 41);
