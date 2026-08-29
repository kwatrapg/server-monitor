<?php

class ContactGroup extends App {


    public static function add($data) {
    	global $database;
    	$lastid = $database->insert("app_contact_groups", [
    		"name" => $data['name'],
            "contacts" => $data['contacts'],
    	]);
    	if ($lastid == "0") { return "11"; } else { logSystem("Contact Group Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
    	global $database;
    	$database->update("app_contact_groups", [
    		"name" => $data['name'],
            "contacts" => $data['contacts'],
    	], [ "id" => $data['id'] ]);
    	logSystem("Contact Group Edited - ID: " . $data['id']);
    	return "20";
    }


    public static function delete($id) {
    	global $database;
        $database->delete("app_contact_groups", [ "id" => $id ]);
    	logSystem("Contact Group Deleted - ID: " . $id);
    	return "30";
    }


}

?>
