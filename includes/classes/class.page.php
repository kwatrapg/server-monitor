<?php


class Page extends App {



    // alerts
    public static function add($data) {
        global $database;
        $lastid = $database->insert("app_pages", [
            "groupid" => $data['groupid'],
            "pagekey" => randomString(18),
            "name" => $data['name'],
            "info" => $data['info'],
            "servers" => serialize($data['servers']),
            "websites" => serialize($data['websites']),
            "checks" => serialize($data['checks']),

        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Page Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
        global $database;
        $database->update("app_pages", [
            "groupid" => $data['groupid'],
            "name" => $data['name'],
            "info" => $data['info'],
            "servers" => serialize($data['servers']),
            "websites" => serialize($data['websites']),
            "checks" => serialize($data['checks']),
        ], [ "id" => $data['id'] ]);
        logSystem("Page Edited - ID: " . $data['id']);
        return "20";
    }


    public static function delete($id) {
        global $database;
        $database->delete("app_pages", [ "id" => $id ]);
        logSystem("Page Deleted - ID: " . $id);
        return "30";
    }




}

?>
