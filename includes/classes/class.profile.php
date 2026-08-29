<?php

class Profile extends App {


    public static function edit($data,$files) {
    	global $database;
    	$email = strtolower($data['email']);

        $count = $database->count("core_users",["AND" => ["id" => $data['id'], "password" => sha1($data['confirmpassword'])]]);

        if($count == 1) {

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
        		$password = sha1($data['password']);
        		$database->update("core_users", [
        			"name" => $data['name'],
        			"email" => $email,
        			"password" => $password,
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
