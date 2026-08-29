<?php

$debug = false;

if($debug == false) {
    error_reporting(0);
    ini_set('display_errors', '0');
}

if($debug == true) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', '1');
}

$scriptpath = __DIR__;

// LOAD FUNCTIONS
require($scriptpath . '/includes/functions.php');

// AUTOLOAD CLASSES
spl_autoload_register('vendorClassAutoload');
spl_autoload_register('appClassAutoload');

# LOAD CONFIGURAGION FILE
require($scriptpath . '/config.php');

# INITIALIZE MEDOO
$database = new medoo($config);

# DATE & TIME
date_default_timezone_set(getConfigValue("timezone"));
$datetime = date("Y-m-d H:i:s");


if(isset($_GET['key']) && isset($_GET['status'])) {


    $check = $database->get("app_checks", "*", [ "host" => $_GET['key'] ]);
    if(empty($check)) die("Unknown check.");

    $status = "";

    if($_GET['status'] == "success") $status = "1";
    if($_GET['status'] == "failure") $status = "0";

    if($status != "") {
        $lastid = $database->insert("app_checks_history", [
            "checkid" => $check['id'],
            "timestamp" => $datetime,
            "latency" => 0,
            "statuscode" => $status,
        ]);
    }




}

else die("No data received.");


?>
