<?php

class Group extends App {


    public static function add($data) {
    	global $database;
    	$lastid = $database->insert("app_groups", [
    		"name" => $data['name']
    	]);
    	if ($lastid == "0") { return "11"; } else { logSystem("Group Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
    	global $database;
    	$database->update("app_groups", [
    		"name" => $data['name']
    	], [ "id" => $data['id'] ]);
    	logSystem("Group Edited - ID: " . $data['id']);
    	return "20";
    }


    public static function delete($id) {
    	global $database;
        $database->delete("app_groups", [ "id" => $id ]);
    	logSystem("Group Deleted - ID: " . $id);
    	return "30";
    }


}

?>
