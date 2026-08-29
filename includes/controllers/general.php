<?php

##################################
###       GENERAL ACTIONS      ###
##################################

// AUTHENTICATION — all of these mutate state, so require a valid CSRF token
// bound to the visitor's session (VAPT F-07).
if(isset($_POST['signin'])) {
	csrf_check_or_die();
	signIn($_POST['email'] ?? '', $_POST['password'] ?? '');
}

if(isset($_POST['resetConfirmation'])) {
	csrf_check_or_die();
	resetConfirmation($_POST['email'] ?? '');
}

if(isset($_POST['resetPassword'])) {
	csrf_check_or_die();
	resetPassword($_POST['resetkey'] ?? '', $_POST['password'] ?? '');
}


// SIGN OUT
if ($route == "signout") {
	if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' || csrf_verify()) {
		signOut($liu['id'] ?? null);
	}
	// GET sign-out without a token: bounce to a confirmation-free logout link is
	// avoided; just send them to the dashboard.
	header("Location:?route=dashboard");
	exit;
}
