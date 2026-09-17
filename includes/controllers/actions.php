<?php

##################################
###           ACTIONS          ###
##################################

// Every state-changing action requires a valid CSRF token + same-origin request
// (VAPT F-07). The action id itself was already allow-list checked in loader.php.
csrf_check_or_die();

// A user may only ever edit their OWN profile (VAPT A-3). Ignore any posted id.
if (($_POST['action'] ?? '') === 'editProfile') {
	$_POST['id'] = $liu['id'];
}

switch($_POST['action']) {


	// date range
	case "setRange":
		App::setRange($_POST);
	break;

	case "resetRange":
		App::resetRange();
	break;


	// servers
	case "addServer":
		isAuthorized("addServer"); $status = Server::add($_POST);
	break;

	case "editServer":
		isAuthorized("editServer"); $status = Server::edit($_POST);
	break;

	case "deleteServer":
		isAuthorized("deleteServer"); $status = Server::delete($_POST['id']);
	break;

	// server alerts
	case "addServerAlert":
		isAuthorized("editServer"); $status = Server::addAlert($_POST);
	break;

	case "editServerAlert":
		isAuthorized("editServer"); $status = Server::editAlert($_POST);
	break;

	case "deleteServerAlert":
		isAuthorized("editServer"); $status = Server::deleteAlert($_POST['id']);
	break;

	case "markServerIncident":
		isAuthorized("editServer"); $status = Server::markIncident($_POST['id']);
	break;

    case "editServerIncidentComment":
		isAuthorized("editServer"); $status = Server::editComment($_POST);
	break;



	// websites
	case "addWebsite":
		isAuthorized("addWebsite"); $status = Website::add($_POST);
	break;

	case "editWebsite":
		isAuthorized("editWebsite"); $status = Website::edit($_POST);
	break;

	case "deleteWebsite":
		isAuthorized("deleteWebsite"); $status = Website::delete($_POST['id']);
	break;

	// website alerts
	case "addWebsiteAlert":
		isAuthorized("editWebsite"); $status = Website::addAlert($_POST);
	break;

	case "editWebsiteAlert":
		isAuthorized("editWebsite"); $status = Website::editAlert($_POST);
	break;

	case "deleteWebsiteAlert":
		isAuthorized("editWebsite"); $status = Website::deleteAlert($_POST['id']);
	break;

	case "markWebsiteIncident":
		isAuthorized("editWebsite"); $status = Website::markIncident($_POST['id']);
	break;

    case "editWebsiteIncidentComment":
		isAuthorized("editWebsite"); $status = Website::editComment($_POST);
	break;


	// checks
	case "addCheck":
		isAuthorized("addCheck"); $status = Check::add($_POST);
	break;

	case "editCheck":
		isAuthorized("editCheck"); $status = Check::edit($_POST);
	break;

	case "deleteCheck":
		isAuthorized("deleteCheck"); $status = Check::delete($_POST['id']);
	break;

	// check alerts
	case "addCheckAlert":
		isAuthorized("editCheck"); $status = Check::addAlert($_POST);
	break;

	case "editCheckAlert":
		isAuthorized("editCheck"); $status = Check::editAlert($_POST);
	break;

	case "deleteCheckAlert":
		isAuthorized("editCheck"); $status = Check::deleteAlert($_POST['id']);
	break;

	case "markCheckIncident":
		isAuthorized("editCheck"); $status = Check::markIncident($_POST['id']);
	break;

    case "editCheckIncidentComment":
		isAuthorized("editCheck"); $status = Check::editComment($_POST);
	break;


	// domains
	case "addDomain":
		isAuthorized("addDomain"); $status = Domain::add($_POST);
	break;

	case "editDomain":
		isAuthorized("editDomain"); $status = Domain::edit($_POST);
	break;

	case "deleteDomain":
		isAuthorized("deleteDomain"); $status = Domain::delete($_POST['id']);
	break;

	// domain alerts
	case "addDomainAlert":
		isAuthorized("editDomain"); $status = Domain::addAlert($_POST);
	break;

	case "editDomainAlert":
		isAuthorized("editDomain"); $status = Domain::editAlert($_POST);
	break;

	case "deleteDomainAlert":
		isAuthorized("editDomain"); $status = Domain::deleteAlert($_POST['id']);
	break;

	case "markDomainIncident":
		isAuthorized("editDomain"); $status = Domain::markIncident($_POST['id']);
	break;

    case "editDomainIncidentComment":
		isAuthorized("editDomain"); $status = Domain::editComment($_POST);
	break;


	// ssl
	case "addSsl":
		isAuthorized("addSsl"); $status = Ssl::add($_POST);
	break;

	case "editSsl":
		isAuthorized("editSsl"); $status = Ssl::edit($_POST);
	break;

	case "deleteSsl":
		isAuthorized("deleteSsl"); $status = Ssl::delete($_POST['id']);
	break;

	// ssl alerts
	case "addSslAlert":
		isAuthorized("editSsl"); $status = Ssl::addAlert($_POST);
	break;

	case "editSslAlert":
		isAuthorized("editSsl"); $status = Ssl::editAlert($_POST);
	break;

	case "deleteSslAlert":
		isAuthorized("editSsl"); $status = Ssl::deleteAlert($_POST['id']);
	break;

	case "markSslIncident":
		isAuthorized("editSsl"); $status = Ssl::markIncident($_POST['id']);
	break;

    case "editSslIncidentComment":
		isAuthorized("editSsl"); $status = Ssl::editComment($_POST);
	break;


	// alerting - contacts
	case "addContact":
		isAuthorized("addContact"); $status = Contact::add($_POST);
	break;

	case "editContact":
		isAuthorized("editContact"); $status = Contact::edit($_POST);
	break;

	case "deleteContact":
		isAuthorized("deleteContact"); $status = Contact::delete($_POST['id']);
	break;



	// pages
	case "addPage":
		isAuthorized("addPage"); $status = Page::add($_POST);
	break;

	case "editPage":
		isAuthorized("editPage"); $status = Page::edit($_POST);
	break;

	case "deletePage":
		isAuthorized("deletePage"); $status = Page::delete($_POST['id']);
	break;





	// users
	case "addUser":
		isAuthorized("addUser"); $status = User::add($_POST);
	break;

	case "editUser":
		isAuthorized("editUser"); $status = User::edit($_POST);
	break;

	case "deleteUser":
		isAuthorized("deleteUser"); $status = User::delete($_POST['id']);
	break;


	// roles
	case "addRole":
		isAuthorized("addRole"); $status = Role::add($_POST);
	break;

	case "editRole":
		isAuthorized("editRole"); $status = Role::edit($_POST);
	break;

	case "deleteRole":
		isAuthorized("deleteRole"); $status = Role::delete($_POST['id']);
    break;


	// users
	case "addGroup":
		isAuthorized("addGroup"); $status = Group::add($_POST);
	break;

	case "editGroup":
		isAuthorized("editGroup"); $status = Group::edit($_POST);
	break;

	case "deleteGroup":
		isAuthorized("deleteGroup"); $status = Group::delete($_POST['id']);
	break;


	// languages
	case "addLanguage":
		isAuthorized("manageSettings"); $status = Settings::addLanguage($_POST);
	break;

	case "deleteLanguage":
		isAuthorized("manageSettings"); $status = Settings::deleteLanguage($_POST['id']);
	break;



	// profile
	case "editProfile":
		$status = Profile::edit($_POST,$_FILES);
	break;


	//settings
	case "generalSettings":
		isAuthorized("manageSettings");
		Settings::update("app_name", $_POST['app_name']);
		Settings::update("app_url", rtrim($_POST['app_url'], '/') . '/');
		Settings::update("company_name", $_POST['company_name']);
		Settings::update("company_details", $_POST['company_details']);
		Settings::update("log_retention", $_POST['log_retention']);
		Settings::update("table_records", $_POST['table_records']);
		Settings::update("google_maps_api_key", $_POST['google_maps_api_key']);

		if (isset($_POST['xss_filtering'])) $xss_filtering = "true"; else $xss_filtering = "false";
		Settings::update("xss_filtering", $xss_filtering);

		$status = 40;

		if (!empty($_FILES['logo']['tmp_name'])) {
			$status = Settings::uploadLogo($_FILES['logo']);
		}
		if (!empty($_FILES['favicon']['tmp_name'])) {
			$faviconStatus = Settings::uploadFavicon($_FILES['favicon']);
			if ($faviconStatus !== 40) $status = $faviconStatus;
		}
	break;

	case "monitoringSettings":
		isAuthorized("manageSettings");

		Settings::update("check_timeout", $_POST['check_timeout']);
		Settings::update("history_retention", $_POST['history_retention']);
		Settings::update("default_contacts", serialize($_POST['default_contacts']));

		$status = 40;
	break;

	case "localisationSettings":
		isAuthorized("manageSettings");
		Settings::update("week_start", $_POST['week_start']);
		Settings::update("default_lang", $_POST['default_lang']);
		Settings::update("timezone", $_POST['timezone']);
		Settings::update("date_format", $_POST['date_format']);
		$status = 40;
	break;

	case "emailSettings":
		isAuthorized("manageSettings");
		Settings::update("email_from_address", $_POST['email_from_address']);
		Settings::update("email_from_name", $_POST['email_from_name']);
		Settings::update("email_smtp_host", $_POST['email_smtp_host']);
		Settings::update("email_smtp_port", $_POST['email_smtp_port']);
		Settings::update("email_smtp_username", $_POST['email_smtp_username']);
		// Blank means "leave unchanged" — the field is never pre-filled with the
		// current secret (see settings.php), so an empty submit isn't "clear it".
		if (trim((string) $_POST['email_smtp_password']) !== '') {
			Settings::update("email_smtp_password", sm_encrypt_secret($_POST['email_smtp_password']));
		}
		Settings::update("email_smtp_security", $_POST['email_smtp_security']);
		if (isset($_POST['email_smtp_auth'])) $email_smtp_auth = "true"; else $email_smtp_auth = "false";
		Settings::update("email_smtp_auth", $email_smtp_auth);
		if (isset($_POST['email_smtp_enable'])) $email_smtp_enable = "true"; else $email_smtp_enable = "false";
		Settings::update("email_smtp_enable", $email_smtp_enable);
		Settings::update("email_smtp_domain", $_POST['email_smtp_domain']);
		$status = 40;
	break;

	case "testEmailSettings":
		isAuthorized("manageSettings");
		header('Content-Type: application/json');
		$testTo = trim((string) ($_POST['email_test_to'] ?? ''));
		if ($testTo === '' || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(["success" => false, "error" => __('Please enter a valid email address to send the test to.')]);
			exit;
		}
		echo json_encode(Settings::testEmail($testTo, $liu['id']));
		exit;

	case "smsSettings":
		isAuthorized("manageSettings");
		Settings::update("sms_provider", $_POST['sms_provider']);
		Settings::update("sms_user", $_POST['sms_user']);
		Settings::update("sms_password", $_POST['sms_password']);
		Settings::update("sms_api_id", $_POST['sms_api_id']);
		Settings::update("sms_from", $_POST['sms_from']);
		$status = 40;
	break;

	case "twitterSettings":
		isAuthorized("manageSettings");
		Settings::update("twitter_apikey", $_POST['twitter_apikey']);
		Settings::update("twitter_apisecret", $_POST['twitter_apisecret']);
		Settings::update("twitter_token", $_POST['twitter_token']);
		Settings::update("twitter_tokensecret", $_POST['twitter_tokensecret']);
		$status = 40;
	break;

	case "pushoverSettings":
		isAuthorized("manageSettings");
		Settings::update("pushover_apitoken", $_POST['pushover_apitoken']);
		$status = 40;
	break;

	case "licenseSettings":
		isAuthorized("manageSettings");
		License::setKey($_POST['license_key'] ?? '');
		$result = License::status(true);
		$status = $result['valid'] ? 42 : 43;
	break;

	case "editNotification":
		isAuthorized("manageSettings"); $status = Settings::editNotification($_POST);
    break;

	// server log sources - group-scoped on top of the permission check, since
	// a source's access boundary is its server's group, not a group of its own
	case "addLogSource":
		isAuthorized("manageLogSources");
		$server = getRowById("app_servers", $_POST['serverid']);
		checkGroupRedirect($server['groupid']);
		$status = Log::addSource($_POST);
	break;

	case "editLogSource":
		isAuthorized("manageLogSources");
		$existing = getRowById("app_servers_logsources", $_POST['id']);
		$existingServer = getRowById("app_servers", $existing['serverid']);
		checkGroupRedirect($existingServer['groupid']);
		$newServer = getRowById("app_servers", $_POST['serverid']);
		checkGroupRedirect($newServer['groupid']);
		$status = Log::editSource($_POST);
	break;

	case "deleteLogSource":
		isAuthorized("manageLogSources");
		$existing = getRowById("app_servers_logsources", $_POST['id']);
		$existingServer = getRowById("app_servers", $existing['serverid']);
		checkGroupRedirect($existingServer['groupid']);
		$status = Log::deleteSource($_POST['id']);
	break;

	// server log alerts
	case "addLogAlert":
		isAuthorized("editLogAlert");
		$server = getRowById("app_servers", $_POST['serverid']);
		checkGroupRedirect($server['groupid']);
		$status = Log::addAlert($_POST);
	break;

	case "editLogAlert":
		isAuthorized("editLogAlert");
		$existing = getRowById("app_servers_logs_alerts", $_POST['id']);
		$existingServer = getRowById("app_servers", $existing['serverid']);
		checkGroupRedirect($existingServer['groupid']);
		$newServer = getRowById("app_servers", $_POST['serverid']);
		checkGroupRedirect($newServer['groupid']);
		$status = Log::editAlert($_POST);
	break;

	case "deleteLogAlert":
		isAuthorized("editLogAlert");
		$existing = getRowById("app_servers_logs_alerts", $_POST['id']);
		$existingServer = getRowById("app_servers", $existing['serverid']);
		checkGroupRedirect($existingServer['groupid']);
		$status = Log::deleteAlert($_POST['id']);
	break;

	case "markLogIncident":
		isAuthorized("editLogAlert");
		$existing = getRowById("app_servers_logs_incidents", $_POST['id']);
		$existingServer = getRowById("app_servers", $existing['serverid']);
		checkGroupRedirect($existingServer['groupid']);
		$status = Log::markIncident($_POST['id']);
	break;

	case "editLogIncidentComment":
		isAuthorized("editLogAlert");
		$existing = getRowById("app_servers_logs_incidents", $_POST['id']);
		$existingServer = getRowById("app_servers", $existing['serverid']);
		checkGroupRedirect($existingServer['groupid']);
		$status = Log::editComment($_POST);
	break;

}


reroute($_POST,$status);

?>
