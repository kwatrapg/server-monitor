<?php

##################################
###       QUICK ACTIONS        ###
##################################


switch($_GET['qa']) {



	case "setAutorefresh":
        Profile::setAutorefresh($liu['id'],$_GET['autorefresh']);
        header("Location:?route=".$_GET['reroute']."&id=".$_GET['routeid']."&section=".$_GET['section']);
    break;

	case "removeAvatar":
        Profile::removeAvatar($liu['id']);
        header("Location:?route=profile");
    break;



    case "download":
        $file = getRowById("files",$_GET['id']);
        $targetfile = $scriptpath . "/uploads/" . $file['file'];
		$disposition = "attachment"; if(isset($_GET['inline'])) $disposition = "inline";
        if (file_exists($targetfile)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: '.$disposition.'; filename="'.$file['file'].'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($targetfile));
            readfile($targetfile);
            exit;
            }
        else _e("File does not exist.");
	break;


} // end switch



?>
