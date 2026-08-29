<?php

class Contact extends App {


    public static function add($data) {
    	global $database;
    	$lastid = $database->insert("app_contacts", [
            "groupid" => $data['groupid'],
            "status" => $data['status'],
    		"name" => $data['name'],
            "email" => $data['email'],
            "mobilenumber" => $data['mobilenumber'],
            "pushbullet" => $data['pushbullet'],
            "twitter" => $data['twitter'],
            "pushover" => $data['pushover'],
    	]);
    	if ($lastid == "0") { return "11"; } else { logSystem("Contact Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
    	global $database;
    	$database->update("app_contacts", [
            "groupid" => $data['groupid'],
            "status" => $data['status'],
    		"name" => $data['name'],
            "email" => $data['email'],
            "mobilenumber" => $data['mobilenumber'],
            "pushbullet" => $data['pushbullet'],
            "twitter" => $data['twitter'],
            "pushover" => $data['pushover'],
    	], [ "id" => $data['id'] ]);
    	logSystem("Contact Edited - ID: " . $data['id']);
    	return "20";
    }


    public static function delete($id) {
    	global $database;
        $database->delete("app_contacts", [ "id" => $id ]);
    	logSystem("Contact Deleted - ID: " . $id);
    	return "30";
    }


}

?>
