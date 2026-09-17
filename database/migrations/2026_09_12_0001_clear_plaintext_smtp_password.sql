-- Prior to this release, emailSettings saved the SMTP password to core_config
-- in plaintext (sendEmail() only ignored it in favour of .env). Now that the
-- password is encrypted at rest with APP_KEY (VAPT F-04 revision), any
-- pre-existing plaintext value is both unusable (it won't decrypt) and a
-- residual secret sitting in the DB/backups. Clear it so admins re-enter it
-- once via Settings > Email, where it will be stored encrypted.
UPDATE `core_config` SET `value` = '' WHERE `name` = 'email_smtp_password' AND `value` <> '';
