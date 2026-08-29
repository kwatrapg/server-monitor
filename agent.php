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


if(isset($_POST['data'])) {

    $_POST['data'] = urldecode($_POST['data']);

    $server = $database->get("app_servers", "*", [ "serverkey" => Server::extractData("serverkey", $_POST['data']) ]);
    if(empty($server)) die("Unknown server.");

    $lastHistory = $database->get("app_servers_history", "*", ["serverid" => $server['id'], "ORDER" => ["id" => "DESC"]]);

    $lastid = $database->insert("app_servers_history", [
        "serverid" => $server['id'],
        "timestamp" => $datetime,
        "data" => gzcompress($_POST['data'], 9),
    ]);

    Server::cleanHistory($lastHistory['id']);

}

else die("No data received.");


?>
