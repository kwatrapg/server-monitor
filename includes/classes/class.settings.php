<?php

class Settings extends App {



    public static function update($name, $value) { //update config value
    	global $database;
    	$database->update("core_config", ["value" => $value], ["name" => $name]);
    }


    // Logo/favicon are written to the public ./assets/ override directory that
    // header.php/signin.php already prefer over the bundled template/assets/ files
    // (file_exists() check), so no template changes are needed. The target filename
    // is always fixed (never derived from the upload) because ./assets/ has PHP
    // execution enabled (unlike ./uploads/, which is fully locked down) - we must
    // never trust a client-supplied filename/extension there.
    private static function saveUploadedImage($file, $maxBytes, array $allowedMimes, array $destFilenames) {
        global $scriptpath;

        if (!is_array($file) || !isset($file['tmp_name'], $file['error'])) return false;
        if ($file['error'] !== UPLOAD_ERR_OK) return false;
        if (!is_uploaded_file($file['tmp_name'])) return false;
        if ((int) $file['size'] <= 0 || (int) $file['size'] > $maxBytes) return false;

        // getimagesize() decodes the actual image header - a strong check against
        // disguised non-image uploads (unlike the client-side accept="" hint).
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !in_array($info['mime'], $allowedMimes, true)) return false;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) finfo_close($finfo);
        if (!in_array($realMime, $allowedMimes, true)) return false;

        $destDir = $scriptpath . '/assets';
        if (!is_dir($destDir) || !is_writable($destDir)) return false;

        $data = file_get_contents($file['tmp_name']);
        if ($data === false) return false;

        foreach ($destFilenames as $name) {
            if (file_put_contents($destDir . '/' . $name, $data) === false) return false;
        }
        return true;
    }

    // Shown on the login page (template/signin.php). Returns a core_statuses code.
    public static function uploadLogo($file) {
        $ok = self::saveUploadedImage($file, 2 * 1024 * 1024, ['image/png', 'image/jpeg'], ['logo.png']);
        if ($ok) { logSystem("Logo updated"); return 40; }
        return 41;
    }

    // Browser tab icon (template/header.php + template/signin.php). Same uploaded
    // image is used for both the small shortcut icon and the larger touch icon,
    // since there is no image-resize library (ext-gd) available in this app.
    public static function uploadFavicon($file) {
        $ok = self::saveUploadedImage($file, 1 * 1024 * 1024, ['image/png'], ['icon.png', 'icon-large.png']);
        if ($ok) { logSystem("Favicon updated"); return 40; }
        return 41;
    }

    public static function removeLogo() {
        global $scriptpath;
        $f = $scriptpath . '/assets/logo.png';
        if (file_exists($f)) unlink($f);
        logSystem("Logo removed");
    }

    public static function removeFavicon() {
        global $scriptpath;
        foreach (['icon.png', 'icon-large.png'] as $name) {
            $f = $scriptpath . '/assets/' . $name;
            if (file_exists($f)) unlink($f);
        }
        logSystem("Favicon removed");
    }


    // Sends a sample test email through the app's normal mail pipeline (sendEmail()),
    // using whatever connection settings sendEmail() would use for real notifications
    // (.env overrides > core_config), so the test reflects reality. Every attempt is
    // logged to core_emaillog by sendEmail() itself, success or failure.
    public static function testEmail($to, $userid = "0") {
        $company = getConfigValue("company_name");
        if ($company === "" || $company === null) $company = "Sentruo";

        $subject = $company . ' - ' . __('Test Email');
        $message = "<p>" . sprintf(__('This is a test email sent from your %s installation to verify your email connection settings.'), $company) . "</p>"
            . "<p>" . __('If you are reading this, your SMTP configuration is working correctly.') . "</p>"
            . "<hr>"
            . "<p style='color:#888;font-size:12px;'>"
            . __('Sent') . ": " . date('Y-m-d H:i:s')
            . "</p>";

        $error = null;
        $sent = sendEmail($to, $subject, $message, $userid, array(), $error);

        if ($sent) {
            return array("success" => true, "message" => sprintf(__('Test email sent to %s.'), $to));
        }
        return array("success" => false, "error" => $error ?: __('Unknown error.'));
    }


    public static function editNotification($data) { //update notification template
    	global $database;
    	$database->update("core_notifications", ["subject" => $data['subject'], "message" => $data['message']], ["id" => $data['id']]);
    	return 40;
    }


    public static function addLanguage($data) {
    	global $database;
    	$lastid = $database->insert("core_languages", [ "code" => $data['code'], "name" => $data['name'] ]);
    	if ($lastid == "0") { return "11"; } else { logSystem("Language Added - ID: " . $lastid); return "10"; }
    }


    public static function deleteLanguage($id) {
    	global $database;
        $database->delete("core_languages", [ "id" => $id ]);
    	logSystem("Language Deleted - ID: " . $id);
    	return "30";
    }




}


?>
