<?php

class Notification extends App {


    public static function newUser($peopleid,$password) { //send new user notification
    	global $database;
    	$template = getRowById("core_notifications",1);
    	$people = getRowById("core_users",$peopleid);

    	$search = array('{contact}', '{email}', '{password}', '{company}');
    	$replace = array($people['name'], $people['email'], $password, getConfigValue("company_name"));

    	$subject = str_replace($search, $replace, $template['subject']);
    	$message = str_replace($search, $replace, $template['message']);

    	sendEmail($people['email'],$subject,$message,$people['id']);
    }


    public static function passwordReset($peopleid,$resetlink) { //send password reset link
    	global $database;
    	$template = getRowById("core_notifications",2);
    	$people = getRowById("core_users",$peopleid);

    	$search = array('{contact}', '{resetlink}', '{company}');
    	$replace = array($people['name'], $resetlink, getConfigValue("company_name"));

    	$subject = str_replace($search, $replace, $template['subject']);
    	$message = str_replace($search, $replace, $template['message']);

    	sendEmail($people['email'],$subject,$message,$people['id']);
    }




    public static function incidentAlert($emailaddr,$contactname,$subject,$message) { //send daily status email
    	global $database;
    	$template = getRowById("core_notifications",3);

    	$search = array('{contact}', '{subject}', '{message}', '{company}');
    	$replace = array($contactname, $subject, $message, getConfigValue("company_name"));

    	$email_subject = str_replace($search, $replace, $template['subject']);
    	$email_message = str_replace($search, $replace, $template['message']);

    	sendEmail($emailaddr,$email_subject,$email_message,0);
    }


    public static function incidentUnresolvedAlert($emailaddr,$contactname,$subject,$message) { //send daily status email
        global $database;
        $template = getRowById("core_notifications",4);

        $search = array('{contact}', '{subject}', '{message}', '{company}');
        $replace = array($contactname, $subject, $message, getConfigValue("company_name"));

        $email_subject = str_replace($search, $replace, $template['subject']);
        $email_message = str_replace($search, $replace, $template['message']);

        sendEmail($emailaddr,$email_subject,$email_message,0);
    }




}


?>
