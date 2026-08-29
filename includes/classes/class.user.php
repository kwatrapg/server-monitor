<?php

class User extends App {

    public static function add($data) {
    	global $database;

    	$email = strtolower($data['email']);
    	$count = $database->count("core_users",["email" => $email]);
    	if ($count == "1") { return "11"; }

    	$password = sha1($data['password']);

    	$lastid = $database->insert("core_users", [
    		"roleid" => $data['roleid'],
    		"name" => $data['name'],
    		"email" => $email,
    		"password" => $password,
            "groups" => serialize($data['groups']),
    		"theme" => "skin-green",
    		"sidebar" => "opened",
    		"layout" => "",
    		"notes" => "",
    		"sessionid" => "",
    		"resetkey" => "",
    		"lang" => getConfigValue("default_lang"),
            "autorefresh" => 0,
    	]);
    		if ($lastid == "0") { return "11"; } else {
    			if(isset($data['notification'])) { if($data['notification'] == true) Notification::newUser($lastid,$data['password']); }
    			logSystem("User Added - ID: " . $lastid);
    			return "10";
    		}
    }

    public static function edit($data) {
    	global $database;
    	$email = strtolower($data['email']);

    	if ($data['password'] == "") {
    		$database->update("core_users", [
    			"roleid" => $data['roleid'],
    			"name" => $data['name'],
    			"email" => $email,
                "groups" => serialize($data['groups']),
    			"theme" => $data['theme'],
    			"sidebar" => $data['sidebar'],
    			"layout" => $data['layout'],
    			"notes" => $data['notes'],
    			"lang" => $data['lang']
    			],["id" => $data['id']]);
    		logSystem("User Edited - ID: " . $data['id']);
    		return "20";
    		}
    	else {
    		$password = sha1($data['password']);
    		$database->update("core_users", [
    			"roleid" => $data['roleid'],
    			"name" => $data['name'],
    			"email" => $email,
    			"password" => $password,
                "groups" => serialize($data['groups']),
    			"theme" => $data['theme'],
    			"sidebar" => $data['sidebar'],
    			"layout" => $data['layout'],
    			"notes" => $data['notes'],
    			"lang" => $data['lang']
    			],["id" => $data['id']]);
    		logSystem("User Edited - ID: " . $data['id']);
    		return "20";
    		}

    }

    public static function delete($id) {
    	global $database;
        $database->delete("core_users", [ "id" => $id ]);
    	logSystem("User Deleted - ID: " . $id);
    	return "30";
    }

}


?>
