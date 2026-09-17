<?php
use PHPMailer\PHPMailer\PHPMailer;

// Core security primitives (output encoding, CSRF, CSPRNG, SSRF guard, throttling).
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/whitelist.php';

define('SM_APP_VERSION', '2.0.0');

// ----------------------------------------------------------------------------------------------
// GENERAL FUNCTIONS

/**
 * Random string for security-sensitive tokens (server keys, reset keys, page
 * keys). Now CSPRNG-backed (VAPT F-06); returns lowercase hex.
 */
function randomString($chars=10) {
	return sm_random_token((int) $chars);
}

function currentFileName() { //return current file name
	return basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
}

function baseURL($sub=0) { //return base url for cron jobs

	if(getConfigValue("app_url") != "") return getConfigValue("app_url");

	$requesturi = explode("?",$_SERVER["REQUEST_URI"]);
	$subdir =  $requesturi[0];
	$pageURL = 'http';
	if(isset($_SERVER["HTTPS"])) { if($_SERVER["HTTPS"] == "on") {$pageURL .= "s";} }
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"] . $subdir;
	} else {
	$pageURL .= $_SERVER["SERVER_NAME"] . $subdir;
	}


	return $pageURL;

}

function getGravatar($email,$size) { //get gravatar image for the given email address
	global $database;

	$grav_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) ) . "?d=mm" . "&s=" . $size;

	$avatar = $database->get("core_users", "avatar", [ "email" => strtolower($email) ]);

	if($avatar != "") { return "data:image/jpeg;base64," . base64_encode($avatar); }

	else return $grav_url;
}

function curlReturn($url) { //get url with curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch,CURLOPT_URL, $url);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function rand_color() { //generate random color
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

function ttruncat($text,$numb=30) { //truncate text
	if (strlen($text) > $numb) {
	  	$text = substr($text, 0, $numb);
	  	$text = substr($text,0,strrpos($text," "));
	  	$etc = " ...";
	  	$text = $text.$etc;
	  }
	return $text;
}

function smartDate($timestamp) {



	if($timestamp == "") return __('Never');

	if (strpos($timestamp, ' ') !== false) { $timestamp = strtotime($timestamp); }

	$diff = time() - $timestamp;

	if ($diff <= 0) {
		return __('Now');
	}
	else if ($diff < 60) {
		return _x("%d second ago","%d seconds ago",floor($diff));
	}
	else if ($diff < 60*60) {
		return _x("%d minute ago","%d minutes ago",floor($diff/60));
	}
	else if ($diff < 60*60*24) {
		return _x("%d hour ago","%d hours ago",floor($diff/(60*60)));
	}
	else if ($diff < 60*60*24*30) {
		return _x("%d day ago","%d days ago",floor($diff/(60*60*24)));
	}
	else if ($diff < 60*60*24*30*12) {
		return _x("%d month ago","%d months ago",floor($diff/(60*60*24*30)));
	}
	else {
		return _x("%d year ago","%d years ago",floor($diff/(60*60*24*30*12)));
	}
}

function escapeJavaScriptText($string) {
    return str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string)$string), "\0..\37'\\")));
}


function deleteBetween($beginning, $end, $string) {
	$beginningPos = strpos($string, $beginning);
	$endPos = strpos($string, $end);
	if ($beginningPos === false || $endPos === false) { return $string;	}

	$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

	return str_replace($textToDelete, '', $string);
}


function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
    // $bytes /= pow(1024, $pow);
     $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}


function objectToArray ($object) {
    if(!is_object($object) && !is_array($object))
        return $object;

    return array_map('objectToArray', (array) $object);
}


function is_decimal( $val )
{
    return is_numeric( $val ) && floor( $val ) != $val;
}

// ----------------------------------------------------------------------------------------------
// GENERAL DATABASE FUNCTIONS

function getRowById($table,$id) { //return associative array from one row by id
	global $database;
	$row = $database->get($table, "*", ["id" => $id]);
	return $row;
}

function getSingleValue($table,$column,$id) { //returns single value from table row by id
	global $database;
	$value = $database->get($table, $column, ["id" => $id]);
	return $value;
}

function getTable($table,$columns="*",$sortby="id",$sortway="ASC") { //get entire table
	global $database;
	$table = $database->select($table, $columns, [ "ORDER" => [$sortby => $sortway] ] );
	return $table;
}

function getTableFiltered($table,$filterColumn1,$filterValue1,$filterColumn2="",$filterValue2="",$columns="*",$sortby="id",$sortway="ASC") { //get entire table filtered
	global $database;
	if ($filterColumn2 == "") {
		$table = $database->select($table, $columns, [$filterColumn1 => $filterValue1, "ORDER" => [$sortby => $sortway]]);
	}
	else {
		$table = $database->select($table, $columns, [ "AND" => [$filterColumn1 => $filterValue1, $filterColumn2 => $filterValue2] ], ["ORDER" => [$sortby => $sortway]]);
	}
	return $table;
}

function countTable($table) { //count table rows
	global $database;
	$count = $database->count($table);
	return $count;
}

function countTableFiltered($table,$filterColumn1,$filterValue1,$filterColumn2="",$filterValue2="") { //count table rows with filter
	global $database;
	if ($filterColumn2 == "") { $count = $database->count($table, [$filterColumn1 => $filterValue1]); }
	else { $count = $database->count($table, [ "AND" => [$filterColumn1 => $filterValue1, $filterColumn2 => $filterValue2] ]); }
	return $count;
}

function getConfigValue($name) { //return config value from database
	global $database;
	return $database->get("core_config", "value", ["name" => $name]);
}

function deleteRowById($table,$id) { //detete row(s) by id
	global $database;
    $database->delete($table, [ "id" => $id ]);
}


// ----------------------------------------------------------------------------------------------
// DATE FUNCTIONS


function phpFormat() {
	$format = explode(";",getConfigValue("date_format"));
	return $format[0];
}

function jsFormat() {
	$format = explode(";",getConfigValue("date_format"));
	return $format[1];
}

function dateDisplay($date) {
	$format = explode(";",getConfigValue("date_format"));

	if($date != "") return date($format[0], strtotime($date) );
	else return "";
}

function dateTimeDisplay($date) {
	$format = explode(";",getConfigValue("date_format"));

	if($date != "") return date($format[0]." H:i:s", strtotime($date) );
	else return "";
}

function dateDb($date) {
	$format = explode(";",getConfigValue("date_format"));

	if($date != "")  {
		$dateObj = date_create_from_format($format[0],$date);
		return date_format($dateObj,"Y-m-d");
	}

	else return "";
}

// ----------------------------------------------------------------------------------------------
// NAVIGATION

function reroute($data,$status=0) {
	$location = "Location:?route=" . $data['route'];
	if(isset($data['routeid'])) $location .= "&id=" . $data['routeid'];
	if(isset($data['section'])) $location .= "&section=" . $data['section'];
	setStatus($status);
	header($location);
}

function setStatus($status) {
	if($status != 0 && $status != "") $_SESSION["statuscode"] = $status;
}

function clearStatus() {
	$_SESSION["statuscode"] = "";
}


// ----------------------------------------------------------------------------------------------
// CLASS LOADERS

function vendorClassAutoload($classname) {
	global $scriptpath;
	$file = $scriptpath . '/vendor/classes/class.' . strtolower($classname) . '.php';
	if (file_exists($file)) require($file);
}

function appClassAutoload($classname) {
	global $scriptpath;
	$file = $scriptpath . '/includes/classes/class.' . strtolower($classname) . '.php';
	if (file_exists($file)) require($file);
}


// ----------------------------------------------------------------------------------------------
// TEXT OUTPUT

function __($text) {
	global $t;
	if(isset($t)) return $t->translate($text);
	else return $text;
}

function _e($text) {
	echo __($text);
}

function _x($sg,$pl,$count) {
	global $t;
	if(isset($t)) return sprintf($t->ngettext($sg,$pl,intval($count)), $count);
	else {
		if($count == "1") return sprintf($sg,$count);
		elseif($count > 1) return sprintf($pl,$count);
	}
}

// ----------------------------------------------------------------------------------------------
// AUTHENTICATION FUNCTIONS

function signIn($email,$password) { //login and set session
	global $database;
	$email = strtolower(trim((string) $email));

	// F-08: throttle by IP + account. Uniform "failed" response when locked out.
	list($allowed, ) = sm_throttle_check('login', $email);
	if (!$allowed) {
		logSystem("User Login blocked (rate limited) - EMAIL: " . $email);
		setStatus(1200);
		header("Location:?route=signin");
		exit;
	}

	$user  = $database->get("core_users", "*", ["email" => $email]);
	$valid = $user && sm_password_matches($password, $user['password']);

	if (!$valid) {
		sm_throttle_hit('login', $email);
		logSystem("User Login Failure - EMAIL: " . $email);
		setStatus(1200);
		header("Location:?route=signin");
		exit;
	}

	sm_throttle_clear('login', $email);

	// F-05: transparently upgrade legacy sha1 (or weak) hashes on successful login.
	if (sm_password_needs_upgrade($user['password'])) {
		$database->update("core_users", ["password" => sm_password_hash($password)], ["id" => $user['id']]);
	}

	// F-07: defeat session fixation — new session id bound to the account.
	session_regenerate_id(true);
	$database->update("core_users", [
		"sessionid"     => session_id(),
		"last_login_at" => date('Y-m-d H:i:s'),
	], ["id" => $user['id']]);

	logSystem("User Logged In - ID: " . $user['id']);
	header("Location:?route=dashboard");
	exit;
}

function resetConfirmation($email) { //set password resetkey and send confirmation email for password reset
	global $database;
	$email = strtolower(trim((string) $email));

	// F-08: same response whether or not the address exists (no user enumeration).
	list($allowed, ) = sm_throttle_check('reset', $email);
	if ($allowed) {
		$people = $database->get("core_users", "*", ["email" => $email]);
		if ($people) {
			$token = sm_random_token(48);
			$database->update("core_users", [
				"resetkey"         => hash('sha256', $token),               // F-06: store only a hash
				"resetkey_expires" => date('Y-m-d H:i:s', time() + 1800),   // F-06: 30 min TTL
			], ["id" => $people['id']]);
			$resetlink = rtrim(baseURL(), '/') . "/?route=forgot&resetkey=" . $token;
			Notification::passwordReset($people['id'], $resetlink);
		}
		sm_throttle_hit('reset', $email);
	}

	setStatus(1300);
	header("Location:?route=forgot");
	exit;
}

function resetPassword($resetkey,$password) { //reset password
	global $database;
	$hashed = hash('sha256', (string) $resetkey);
	$people = $database->get("core_users", "*", ["resetkey" => $hashed]);

	$valid = $people
		&& !empty($people['resetkey_expires'])
		&& strtotime($people['resetkey_expires']) >= time();

	if (!$valid) { setStatus(1500); header("Location:?route=forgot"); exit; }

	if (($policyError = sm_password_policy_error($password)) !== null) {
		setStatus(1202);
		header("Location:?route=forgot&resetkey=" . urlencode((string) $resetkey));
		exit;
	}

	$database->update("core_users", [
		"password"             => sm_password_hash($password),
		"resetkey"             => "",
		"resetkey_expires"     => null,
		"must_change_password" => 0,
		"sessionid"            => "",   // F-06/F-07: invalidate every existing session
	], ["id" => $people['id']]);

	logSystem("User Password Reset - ID: " . $people['id']);
	setStatus(1600);
	header("Location:?route=signin");   // was "login" (a route that does not exist)
	exit;
}

function signOut($id) { //unset user/admin session
	global $database;
	if (!empty($id)) $database->update("core_users", ["sessionid" => ""], ["id" => $id]);
	$_SESSION = [];
	session_regenerate_id(true);
	logSystem("User Signed Out - ID: " . $id);
	header("Location:?route=signin");
	exit;
}

function isSignedIn() { //check if someone is logged in, if not redirect to login page
	global $database;
	$sessionid = session_id();
	if (!is_string($sessionid) || strlen($sessionid) < 16) { header("Location:?route=signin"); exit; }
	$people = $database->count("core_users", ["sessionid" => $sessionid]);
	if($people != 1) { header("Location:?route=signin"); exit; }
}


function isAuthorized($action) {
	global $perms;
	if(!in_array($action,$perms)) { setStatus("1"); header("Location:?route=dashboard"); exit; }
}

// check if user has permission to view this group
// returns TRUE OR FALSE
function checkGroup($groupid) {
	global $liu_groups;

	//if(in_array("0", $liu_groups)) return TRUE;

	// in case the item is not in any group we will display it
	if($groupid == 0) return TRUE;

    if(is_null($liu_groups)) return FALSE;

	if(in_array($groupid, $liu_groups))
		return TRUE;
	else return FALSE;
}

// check if user has permission to view this group
// returns TRUE OR REDIRECTS with error starus
function checkGroupRedirect($groupid) {
	global $liu_groups;

	//if(in_array("0", $liu_groups)) return TRUE;

	if(in_array($groupid, $liu_groups))
		return TRUE;
	else {
		setStatus("1"); header("Location:?route=dashboard"); exit;
	}
}

function getGroupsArray() {
	$groups = [];
	$groups_table = getTable("app_groups");

	foreach($groups_table as $item) {
		array_push($groups, $item['id']);
	}

	return $groups;
}

function get_group($table, $itemid) {
	$item_row = getRowById($table, $itemid);
	return $item_row['groupid'];
}


// ----------------------------------------------------------------------------------------------
// APP LOGGING FUNCTIONS

function logSystem($description) { //add to system log
	global $liu;
	if(isset($liu['id'])) $userid = $liu['id']; else $userid = -1;
	global $database;
	$database->insert("core_activitylog", [
		"userid" => $userid,
		"ipaddress" => (PHP_SAPI === 'cli') ? 'cli' : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
		"description" => $description,
		"timestamp" => date('Y-m-d H:i:s')
	]);
}

function logEmail($userid,$to,$subject,$message) { //add to email log
	global $database;
	$database->insert("core_emaillog", [
		"userid" => $userid,
		"to" => $to,
		"subject" => $subject,
		"message" => $message,
		"timestamp" => date('Y-m-d H:i:s')
	]);
}

function logSMS($mobile,$sms) { //add to sms log
	global $database;
	$database->insert("core_smslog", [
		"to" => $mobile,
		"message" => $sms,
		"timestamp" => date('Y-m-d H:i:s')
	]);
}


// ----------------------------------------------------------------------------------------------
// COMMUNICATIONS FUNCTIONS

function sendEmail($to,$subject,$message,$userid="0",$ccs=array(),&$error=null) { //send email
	// SMTP config is managed entirely from Settings > Email (core_config).
	// The password is encrypted at rest with APP_KEY (VAPT F-04 revision) —
	// .env is no longer read for mail settings; only APP_KEY itself stays there.
	$smtpHost = getConfigValue("email_smtp_host");
	$smtpUser = getConfigValue("email_smtp_username");
	$smtpPass = sm_decrypt_secret(getConfigValue("email_smtp_password"));
	$smtpEnabled = ($smtpHost !== '' && $smtpHost !== null)
		|| getConfigValue("email_smtp_enable") == "true";

	$mail = new PHPMailer;
	$mail->CharSet = "UTF-8";
	if ($smtpEnabled) {
		$mail->isSMTP();
		$mail->Host = $smtpHost;
		$mail->SMTPAuth = ($smtpUser !== '' && $smtpUser !== null);
		$mail->Username = $smtpUser;
		$mail->Password = $smtpPass;
		// PHPMailer compares SMTPSecure with strict === against lowercase
		// 'tls'/'ssl'; the Settings UI dropdown stores "TLS"/"SSL".
		$mail->SMTPSecure = strtolower((string) getConfigValue("email_smtp_security"));
		$mail->Port = (int) (getConfigValue("email_smtp_port") ?: 587);
		if (getConfigValue("email_smtp_domain") != "") {
			$mail->AuthType = 'NTLM';
			$mail->Realm = getConfigValue("email_smtp_domain");
		}
	}

	$mail->From = getConfigValue("email_from_address");
	$mail->FromName = getConfigValue("email_from_name");
	$mail->addAddress($to);
	foreach($ccs as $cc) { $mail->AddCC($cc); }
	$mail->Subject = $subject;
	$mail->Body    = $message;
	$mail->IsHTML(true);

	if(!$mail->send()) {
		$error = $mail->ErrorInfo;
		logEmail($userid,$to,$subject,$mail->ErrorInfo);
		return 0; //error
	}
	else {
		logEmail($userid,$to,$subject,$message);
		return 1; //success
	}
}


function sendSMS($mobile,$sms) { //send sms
	$provider = getConfigValue("sms_provider");
	$user = getConfigValue("sms_user");
	$password = getConfigValue("sms_password");
	$api_id = getConfigValue("sms_api_id");
	$from = getConfigValue("sms_from");

	if ($provider == "smsglobal") {
		$url = 'https://api.smsglobal.com/http-api.php' . '?action=sendsms' . '&user=' . $user . '&password=' . $password . '&from=' . $from . '&to=' . $mobile . '&text=' . urlencode($sms);
		$returnedData = file_get_contents($url);
	}
	if ($provider == "clickatell") {
		$url = 'https://api.clickatell.com/http/sendmsg?user=' . $user . '&password=' . $password . '&api_id=' . $api_id . '&to=' . $mobile . '&text=' . urlencode($sms);
		$returnedData = file_get_contents($url);
	}

	if ($provider == "1s2u") {
		$sms = urlencode($sms);
		$url = 'https://api.1s2u.io/bulksms?' . "username=$user&password=$password&mno=$mobile&msg=$sms&sid=$from&mt=0&fl=0&ipcl=127.0.0.1";
		$returnedData = file_get_contents($url);
	}

	if ($provider == "messagebird") {
		//$sms = urlencode($sms);

		$MessageBird = new \MessageBird\Client($password);
		$Message = new \MessageBird\Objects\Message();
		$Message->originator = $from;
		$Message->recipients = array($mobile);
		$Message->body = $sms;
		$MessageBird->messages->create($Message);

	}

	if ($provider == "twilio") {

		$account_sid = $user;
		$auth_token = $password;
		$client = new Twilio\Rest\Client($account_sid, $auth_token);

		try {
			$messages = $client->messages->create($mobile,
				array(
					'From' => $from,
					'Body' => $sms,
				)
			);
		} catch(Exception $e) { }



	}

	logSMS($mobile,$sms);
}


// ----------------------------------------------------------------------------------------------
// APP SPECIFIC


// custom compare
function compare($what, $with, $how) {
	$result = false;

	switch($how) {
		case "==":
			if($what == $with) $result = true; else $result = false;
		break;

		case ">=":
			if($what >= $with) $result = true; else $result = false;
		break;

		case "<=":
			if($what <= $with) $result = true; else $result = false;
		break;

		case ">":
			if($what > $with) $result = true; else $result = false;
		break;

		case "<":
			if($what < $with) $result = true; else $result = false;
		break;

		case "!=":
			if($what != $with) $result = true; else $result = false;
		break;
	}

	return $result;
}


// website checker on request done

function website_request_done($content, $url, $websiteid, $expect, $ch, $cookie) {
	global $database;

	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$latency = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
	$has_expected = 1;

	if($expect != "") {
		if (stripos($content, $expect) !== false) {
		    $has_expected = 1;
		}
		else $has_expected = 0;
	}

	if($httpcode == "0") $latency = 0;

	$database->insert("app_websites_history", [
		"websiteid" => $websiteid,
		"timestamp" => date('Y-m-d H:i:s'),
		"latency" => $latency,
		"statuscode" => $httpcode,
		"has_expected" => $has_expected,
	]);

}



// DNS blacklist checker

function dns_bl_lookup($ip) {
	global $database;
	$dnsbls = getTable("app_dnsbls");

    $listed = [];

    if ($ip) {
        $reverse_ip = implode(".", array_reverse(explode(".", $ip)));
        foreach ($dnsbls as $dnsbl) {
            if (checkdnsrr($reverse_ip . "." . $dnsbl['host'] . ".", "A")) {
				array_push($listed, $dnsbl['host']);
            }
        }
    }

    return $listed;
}


?>
