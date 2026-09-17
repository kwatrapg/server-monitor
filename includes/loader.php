<?php

##################################
###       LOAD FUNCTIONS       ###
##################################

require($scriptpath . '/includes/functions.php');
require($scriptpath . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ci_common_functions.php');

##################################
###      LOAD CONFIG FILE      ###
##################################

if(file_exists($scriptpath . "/config.php")) { require($scriptpath . '/config.php'); }
else {
    http_response_code(503);
    header('Retry-After: 3600');
    exit('This installation is not configured yet. Run "php bin/install.php" from the CLI to set it up.');
}


##################################
###      REGISTER CLASSES      ###
##################################

spl_autoload_register('vendorClassAutoload');
spl_autoload_register('appClassAutoload');

// composer autoload
require $scriptpath . '/vendor/autoload.php';


##################################
###          APP INIT          ###
##################################

### INITIALIZE DATABSE CLASS ###
$database = new medoo($config);

### SECURITY RESPONSE HEADERS (VAPT F-10) ###
require_once($scriptpath . '/includes/http_headers.php');

### START THE SESSION — hardened cookie params (VAPT F-07 / A-9) ###
$__https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $__https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

### DATE & TIME ###
date_default_timezone_set(getConfigValue("timezone"));
$datetime = date("Y-m-d H:i:s");
$date = date("Y-m-d");


### XSS FILTERING ###
$xss_filtering = getConfigValue("xss_filtering");
if($xss_filtering == "true") {
    $_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $security = new Security();
    $_POST = $security->xss_clean($_POST);
}

### GET PAGE ROUTE (DEFAULTS TO DASHBOARD IF NOT SET) — validated allow-list (VAPT F-01) ###
require_once($scriptpath . '/includes/whitelist.php');
$route = (empty($_GET['route'])) ? "dashboard" : (string) $_GET['route'];
if (!sm_valid_route($route, $scriptpath)) {
    http_response_code(404);
    exit('Not found.');
}
$isPublicRoute = in_array($route, sm_public_routes(), true);

### GET PAGE SECTION (IF ISSET) ###
if (isset($_GET['section'])) $section = $_GET['section']; else $section = "";

### LOAD STATUS MESSAGE FOR DISPLAY AND CLEAR IT ###
if (!empty($_SESSION['statuscode'])) {
    $statuscode = $_SESSION['statuscode'];
    $status = array(); $statusmessage = $database->get("core_statuses", "*", ["code" => $statuscode]);
    clearStatus();
}

### AUTH GATE — every route except the public view routes requires a valid session ###
if (!$isPublicRoute) {
    isSignedIn();

    $liu = $database->get("core_users", "*", ["sessionid" => session_id()]);
    if (empty($liu)) { header("Location:?route=signin"); exit; }

    $perms = unserialize((string) getSingleValue("core_roles", "perms", $liu['roleid']), ['allowed_classes' => false]);
    if (!is_array($perms)) $perms = [];

    $liu_groups = unserialize((string) $liu['groups'], ['allowed_classes' => false]);
    if (!is_array($liu_groups)) $liu_groups = [];
    if (in_array("0", $liu_groups, true)) $liu_groups = getGroupsArray();

    // F-03: accounts flagged for a forced password change may only reach their
    // own profile page (where they can set a new password) until they do so.
    if (!empty($liu['must_change_password'])
        && $route !== 'profile' && $route !== 'signout'
        && !isset($_GET['json']) && !isset($_GET['qa'])) {
        setStatus(1201); // "you must change your password" (see core_statuses / lang)
        header("Location:?route=profile");
        exit;
    }
}

### GOOGLE MAPS ###
$isGoogleMaps = false;
if(getConfigValue("google_maps_api_key") != "") $isGoogleMaps = true;

### OTHER SESSION VARS ###

if(empty($_SESSION['range_type'])) $_SESSION['range_type'] = "auto";

if($_SESSION['range_type'] == "auto") {
    $_SESSION['range_start'] = date("Y-m-d H:i:s", strtotime('-3 hours'));
    $_SESSION['range_end'] = date("Y-m-d H:i:s");
    $_SESSION['range_label'] = "";
    $_SESSION['asset'] = "";
}

// A "Last N Minutes/Hours/Days" preset (range_type=manual with a range_offset)
// is a sliding window, not a one-time snapshot — recompute it against "now" on
// every load, same as the auto range above. A manually picked calendar range
// has no offset (0) and is left as the fixed, absolute window the user picked.
if($_SESSION['range_type'] == "manual" && !empty($_SESSION['range_offset'])) {
    $_SESSION['range_end'] = date("Y-m-d H:i:s");
    $_SESSION['range_start'] = date("Y-m-d H:i:s", strtotime('-' . (int) $_SESSION['range_offset'] . ' seconds'));
}


##################################
###        LOAD LANGUAGE       ###
##################################

// get default app language
$lang = getConfigValue("default_lang");

// overwrite default lang if liu has one defined
if(isset($liu)) {
    if($liu['lang'] != "") $lang = $liu['lang'];
    }

// define language file path
$langfile = $scriptpath . "/lang/" . $lang . ".mo";

// define overriden language file path
$orlangfile = $scriptpath . "/lang/override/" . $lang . ".mo";

// load overriden language file (if exists)
if(file_exists($orlangfile)) {
    $streamer = new FileReader($orlangfile);
    $t = new gettext_reader($streamer);
}
// if overridden lang file does not exist, try to load normal language file (if exists)
else {
    if(file_exists($langfile)) {
        $streamer = new FileReader($langfile);
        $t = new gettext_reader($streamer);
    }
}


##################################
###   LOAD APP CONTROLLERS     ###
##################################

// general controller — handles sign-in / password-reset / sign-out POSTs (always).
require($scriptpath . '/includes/controllers/general.php');

// The mutating and data-source controllers MUST NOT run on the unauthenticated
// view routes (signin/forgot/publicpage). Previously they executed regardless of
// route, which allowed e.g. ?route=publicpage&json=activitylog (VAPT A-1/A-2).
if ($isPublicRoute
    && (isset($_GET['modal']) || isset($_GET['qa']) || isset($_GET['json']) || isset($_POST['action']))) {
    // publicpage renders its own read-only data via data.php keyed by pagekey;
    // no controller parameters are accepted on the public view routes.
    http_response_code(400);
    exit('Bad request.');
}

if (!$isPublicRoute) {

    if (isset($_GET['modal'])) {
        if (!sm_valid_modal((string) $_GET['modal'], $scriptpath)) { http_response_code(400); exit('Bad request.'); }
        require($scriptpath . '/includes/controllers/modals.php');
    }

    if (isset($_GET['qa'])) {
        if (!sm_valid_qa((string) $_GET['qa'])) { http_response_code(400); exit('Bad request.'); }
        require($scriptpath . '/includes/controllers/quickactions.php');
    }

    if (isset($_GET['json'])) {
        if (!sm_valid_json((string) $_GET['json'])) { http_response_code(400); exit('Bad request.'); }
        require($scriptpath . '/includes/controllers/json.php');
    }

    if (isset($_POST['action'])) {
        if (!sm_valid_action((string) $_POST['action'])) { http_response_code(400); exit('Bad request.'); }
        require($scriptpath . '/includes/controllers/actions.php');
    }
}

// data controller — per-route read authorization lives inside it; the publicpage
// branch is read-only and keyed by pagekey.
require($scriptpath . '/includes/controllers/data.php');


?>
