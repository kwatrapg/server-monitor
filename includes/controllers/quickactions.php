<?php

##################################
###       QUICK ACTIONS        ###
##################################

switch($_GET['qa']) {

	case "setAutorefresh":
		csrf_check_or_die();
		Profile::setAutorefresh($liu['id'], (int) ($_GET['autorefresh'] ?? 0));
		$rr  = preg_replace('/[^a-z0-9\/_-]/i', '', (string) ($_GET['reroute'] ?? 'dashboard'));
		$rid = (int) ($_GET['routeid'] ?? 0);
		$sec = preg_replace('/[^a-z0-9_-]/i', '', (string) ($_GET['section'] ?? ''));
		if (!sm_valid_route($rr, $scriptpath)) $rr = 'dashboard';
		header("Location:?route=" . $rr . ($rid ? "&id=" . $rid : "") . ($sec ? "&section=" . $sec : ""));
	break;

	case "removeAvatar":
		csrf_check_or_die();
		Profile::removeAvatar($liu['id']);
		header("Location:?route=profile");
	break;

	case "removeLogo":
		csrf_check_or_die();
		isAuthorized("manageSettings");
		Settings::removeLogo();
		header("Location:?route=system/settings");
	break;

	case "removeFavicon":
		csrf_check_or_die();
		isAuthorized("manageSettings");
		Settings::removeFavicon();
		header("Location:?route=system/settings");
	break;

} // end switch
