<?php

class Profile extends App {


    public static function edit($data,$files) {
    	global $database;
    	$email = strtolower($data['email']);

        $current = $database->get("core_users", "*", ["id" => $data['id']]);

        if ($current && sm_password_matches($data['confirmpassword'] ?? '', $current['password'])) {

            if ( isset($files['avatar']) && $files['avatar']['size'] > 0 ) {
                $avatar = file_get_contents($files['avatar']['tmp_name']);
                $database->update("core_users", [ "avatar" => $avatar ], [ "id" => $data['id'] ]);
            }

        	if ($data['password'] == "") {
        		$database->update("core_users", [
        			"name" => $data['name'],
        			"email" => $email,
        			"theme" => $data['theme'],
        			"sidebar" => $data['sidebar'],
        			"layout" => $data['layout'],
        			"lang" => $data['lang']

        			],["id" => $data['id']]);
        		logSystem("Profile Edited - ID: " . $data['id']);
        		return "20";
        	}
        	else {
        		if (sm_password_policy_error($data['password']) !== null) { return "1200"; }
        		$password = sm_password_hash($data['password']);
        		$database->update("core_users", [
        			"name" => $data['name'],
        			"email" => $email,
        			"password" => $password,
        			"must_change_password" => 0,
        			"theme" => $data['theme'],
        			"sidebar" => $data['sidebar'],
        			"layout" => $data['layout'],
        			"lang" => $data['lang']

        			],["id" => $data['id']]);
        		logSystem("Profile Edited - ID: " . $data['id']);
        		return "20";
        	}

        }

        else {
            return "1200";
        }


    }



    public static function removeAvatar($id) {
    	global $database;
    	$database->update("core_users", [ "avatar" => "" ], [ "id" => $id ]);
    }


    public static function setAutorefresh($id,$autorefresh) {
        global $database;
        $database->update("core_users", ["autorefresh" => $autorefresh], ["id" => $id]);
    }

}


?>
