/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `core_statuses` WRITE;
/*!40000 ALTER TABLE `core_statuses` DISABLE KEYS */;
INSERT INTO `core_statuses` (`id`, `code`, `type`, `message`) VALUES (45,10,'success','Item has been added successfully!'),
(46,20,'success','Item has been saved successfully!'),
(47,30,'success','Item has been deleted successfully!'),
(48,11,'danger','Error! Cannot add item.'),
(49,21,'danger','Error! Cannot save item.'),
(50,31,'danger','Error! Cannot delete item.'),
(51,40,'success','Settings updated successfully!'),
(52,1200,'danger','Authentication Failed!'),
(53,1300,'success','Please check your email for a password reset link.'),
(54,1400,'danger','Email address was not found.'),
(55,1500,'danger','Invalid reset key!'),
(56,1600,'success','Success. Please log in with your new password! '),
(57,1,'danger','Unauthorized Access'),
(58,50,'warning','Disabled in demo mode!'),
(59,1201,'warning','You must set a new password before continuing.'),
(60,1202,'danger','Password must be at least 12 characters and include upper-case, lower-case and a number.'),
(61,41,'danger','Invalid image. Please upload a PNG or JPG file within the size limit.');
/*!40000 ALTER TABLE `core_statuses` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `core_notifications` WRITE;
/*!40000 ALTER TABLE `core_notifications` DISABLE KEYS */;
INSERT INTO `core_notifications` (`id`, `name`, `subject`, `message`, `info`) VALUES (1,'New User','New User','<p>Hello {contact},<br><br>Your account has been successfully created.</p><p><br>Email Address: {email}<br>Password: {password}<br><br><br>Best regards,<br>{company}<br></p>',''),
(2,'Password Reset','Password Reset','<p>Hello {contact},<br><br>Please follow the link below to reset your password.<br>{resetlink}<br><br>Best regards,<br>{company}<br></p>',''),
(3,'Incident Alert','{subject}','<p>Hello {contact},</p><p><b>{message}</b></p><p><br>Best regards,<br>{company}<br></p>',''),
(4,'Incident Unresolved','{subject}','<p>Hello {contact},</p><p><b>{message}</b></p><p><br>Best regards,<br>{company}<br></p>','');
/*!40000 ALTER TABLE `core_notifications` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `core_roles` WRITE;
/*!40000 ALTER TABLE `core_roles` DISABLE KEYS */;
INSERT INTO `core_roles` (`id`, `name`, `perms`) VALUES (1,'Super Administrator','a:51:{i:0;s:9:\"addServer\";i:1;s:10:\"editServer\";i:2;s:12:\"deleteServer\";i:3;s:11:\"viewServers\";i:4;s:10:\"addWebsite\";i:5;s:11:\"editWebsite\";i:6;s:13:\"deleteWebsite\";i:7;s:12:\"viewWebsites\";i:8;s:8:\"addCheck\";i:9;s:9:\"editCheck\";i:10;s:11:\"deleteCheck\";i:11;s:10:\"viewChecks\";i:12;s:10:\"addContact\";i:13;s:11:\"editContact\";i:14;s:13:\"deleteContact\";i:15;s:12:\"viewContacts\";i:16;s:8:\"addGroup\";i:17;s:9:\"editGroup\";i:18;s:11:\"deleteGroup\";i:19;s:10:\"viewGroups\";i:20;s:7:\"addPage\";i:21;s:8:\"editPage\";i:22;s:10:\"deletePage\";i:23;s:9:\"viewPages\";i:24;s:7:\"addUser\";i:25;s:8:\"editUser\";i:26;s:10:\"deleteUser\";i:27;s:9:\"viewUsers\";i:28;s:7:\"addRole\";i:29;s:8:\"editRole\";i:30;s:10:\"deleteRole\";i:31;s:9:\"viewRoles\";i:32;s:14:\"manageSettings\";i:33;s:8:\"viewLogs\";i:34;s:13:\"viewAlertLogs\";i:35;s:10:\"viewSystem\";i:36;s:6:\"search\";i:37;s:4:\"Null\";i:38;s:9:\"addDomain\";i:39;s:10:\"editDomain\";i:40;s:12:\"deleteDomain\";i:41;s:11:\"viewDomains\";i:42;s:6:\"addSsl\";i:43;s:7:\"editSsl\";i:44;s:9:\"deleteSsl\";i:45;s:7:\"viewSsl\";i:46;s:14:\"viewServerLogs\";i:47;s:16:\"manageLogSources\";i:48;s:12:\"editLogAlert\";i:49;s:12:\"viewCommands\";i:50;s:14:\"manageCommands\";}'),
(2,'Operator','a:24:{i:0;s:9:\"addServer\";i:1;s:11:\"viewServers\";i:2;s:10:\"addWebsite\";i:3;s:12:\"viewWebsites\";i:4;s:8:\"addCheck\";i:5;s:10:\"viewChecks\";i:6;s:10:\"addContact\";i:7;s:12:\"viewContacts\";i:8;s:8:\"addGroup\";i:9;s:10:\"viewGroups\";i:10;s:7:\"addPage\";i:11;s:9:\"viewPages\";i:12;s:7:\"addUser\";i:13;s:9:\"viewUsers\";i:14;s:9:\"viewRoles\";i:15;s:8:\"viewLogs\";i:16;s:13:\"viewAlertLogs\";i:17;s:10:\"viewSystem\";i:18;s:6:\"search\";i:19;s:4:\"Null\";i:20;s:9:\"addDomain\";i:21;s:11:\"viewDomains\";i:22;s:6:\"addSsl\";i:23;s:7:\"viewSsl\";}');
/*!40000 ALTER TABLE `core_roles` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `core_languages` WRITE;
/*!40000 ALTER TABLE `core_languages` DISABLE KEYS */;
INSERT INTO `core_languages` (`id`, `code`, `name`) VALUES (1,'en','English (System)');
/*!40000 ALTER TABLE `core_languages` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `app_dnsbls` WRITE;
/*!40000 ALTER TABLE `app_dnsbls` DISABLE KEYS */;
INSERT INTO `app_dnsbls` (`id`, `host`) VALUES (11,'b.barracudacentral.org'),
(7,'bl.spamcop.net'),
(12,'cbl.abuseat.org'),
(1,'dnsbl-1.uceprotect.net'),
(2,'dnsbl-2.uceprotect.net'),
(3,'dnsbl-3.uceprotect.net'),
(4,'dnsbl.dronebl.org'),
(5,'dnsbl.sorbs.net'),
(10,'pbl.spamhaus.org'),
(8,'sbl.spamhaus.org'),
(13,'spam.abuse.ch'),
(14,'spam.spamrats.com'),
(9,'xbl.spamhaus.org'),
(6,'zen.spamhaus.org');
/*!40000 ALTER TABLE `app_dnsbls` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `core_config` WRITE;
/*!40000 ALTER TABLE `core_config` DISABLE KEYS */;
INSERT INTO `core_config` (`name`, `value`) VALUES ('app_name','Sentruo'),
('app_url','http://server-monitor.com/'),
('check_timeout','5'),
('company_details',''),
('company_name','Sentruo'),
('date_format','Y-m-d;yyyy-mm-dd'),
('db_version','1.11'),
('default_contacts','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}'),
('default_lang','en'),
('email_from_address',''),
('email_from_name','Sentruo'),
('email_smtp_auth','true'),
('email_smtp_domain',''),
('email_smtp_enable','true'),
('email_smtp_host','smtp.gmail.com'),
('email_smtp_password',''),
('email_smtp_port','587'),
('email_smtp_security','TLS'),
('email_smtp_username',''),
('google_maps_api_key',''),
('history_retention','90'),
('log_retention','90'),
('pushover_apitoken',''),
('sms_api_id',''),
('sms_from',''),
('sms_password',''),
('sms_provider','clickatell'),
('sms_user',''),
('table_records','50'),
('timezone','UTC'),
('twitter_apikey',''),
('twitter_apisecret',''),
('twitter_token',''),
('twitter_tokensecret',''),
('website_timeout','100'),
('week_start','1'),
('xss_filtering','true');
/*!40000 ALTER TABLE `core_config` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

