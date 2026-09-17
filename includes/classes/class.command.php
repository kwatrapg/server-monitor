<?php

class Command extends App {


    // ----------------------------------------------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------------------------------------------

    public static function addCommand($data) {
        global $database;
        $lastid = $database->insert("app_servers_commands", [
            "serverid" => $data['serverid'],
            "name" => $data['name'],
            "command" => $data['command'],
            "timeout_seconds" => max(1, (int) ($data['timeout_seconds'] ?? 30)),
            "contacts" => serialize($data['contacts'] ?? []),
            "repeats" => $data['repeats'] ?? 0,
            "status" => isset($data['status']) ? $data['status'] : 1,
            "last_checked" => null,
            "last_exit_code" => null,
            "last_output" => "",
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Command Added - ID: " . $lastid); return "10"; }
    }


    public static function editCommand($data) {
        global $database;
        $database->update("app_servers_commands", [
            "serverid" => $data['serverid'],
            "name" => $data['name'],
            "command" => $data['command'],
            "timeout_seconds" => max(1, (int) ($data['timeout_seconds'] ?? 30)),
            "contacts" => serialize($data['contacts'] ?? []),
            "repeats" => $data['repeats'] ?? 0,
            "status" => isset($data['status']) ? $data['status'] : 1,
        ], [ "id" => $data['id'] ]);
        logSystem("Command Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteCommand($id) {
        global $database;
        $database->delete("app_servers_commands", [ "id" => $id ]);
        $database->delete("app_servers_commands_incidents", [ "commandid" => $id ]);
        logSystem("Command Deleted - ID: " . $id);
        return "30";
    }


    public static function markIncident($id) {
        global $database;
        $database->update("app_servers_commands_incidents", [
            "status" => 1,
            "end_time" => date('Y-m-d H:i:s'),
        ], [ "id" => $id ]);
        logSystem("Command Incident Marked Resolved - ID: " . $id);
        return "20";
    }


    public static function editComment($data) {
        global $database;
        $database->update("app_servers_commands_incidents", [
            "comment" => $data['comment'],
            "ignore" => $data['ignore'],
        ], [ "id" => $data['id'] ]);
        logSystem("Command Incident Comment Updated - ID: " . $data['id']);
        return "20";
    }


    // ----------------------------------------------------------------------------------------------
    // AGENT-REPORTED RESULTS
    // ----------------------------------------------------------------------------------------------

    // Called once per result in the agent's report (commandresult.php). A command
    // is both its own check and its own alert - there is no separate rule to
    // evaluate, the exit code IS the condition. $output is already truncated by
    // the caller before this is reached (never trust agent-supplied length).
    public static function reportResult($commandRow, $exitCode, $output) {
        global $database;

        $database->update("app_servers_commands", [
            "last_checked" => date('Y-m-d H:i:s'),
            "last_exit_code" => $exitCode,
            "last_output" => $output,
        ], [ "id" => $commandRow['id'] ]);

        $failed = ($exitCode !== 0);

        if ($failed) {
            if (!$database->has("app_servers_commands_incidents", [ "AND" => [ 'commandid' => $commandRow['id'], 'status[!]' => 1 ] ])) {
                $database->insert("app_servers_commands_incidents", [
                    "serverid" => $commandRow['serverid'],
                    "commandid" => $commandRow['id'],
                    "exit_code" => $exitCode,
                    "output" => $output,
                    "start_time" => date('Y-m-d H:i:s'),
                    "end_time" => "0000-00-00 00:00:00",
                    "repeats" => $commandRow['repeats'],
                    "last_notification" => date('Y-m-d H:i:s'),
                    "comment" => "",
                    "ignore" => 0,
                    "status" => 2,
                ]);
                App::send_alert_notif('open', 'command', $commandRow['id']);
            }
        } else {
            if ($database->has("app_servers_commands_incidents", [ "AND" => [ 'commandid' => $commandRow['id'], 'status[!]' => 1 ] ])) {
                $database->update("app_servers_commands_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'commandid' => $commandRow['id'], 'status[!]' => 1 ] ]);
                App::send_alert_notif('close', 'command', $commandRow['id']);
            }
        }
    }


    public static function sendUnresolvedNotifications() {
        global $database;
        $count = 0;
        $now = strtotime("now");

        $unresolved_incidents = getTableFiltered("app_servers_commands_incidents", "status[!]", "1", "repeats[!]", "0");

        foreach ($unresolved_incidents as $unresolved_incident) {
            $last_notification = strtotime($unresolved_incident['last_notification']);
            $difference = $now - $last_notification;

            $required_difference = 60 * $unresolved_incident['repeats'];

            if ($difference >= $required_difference) {
                $database->update("app_servers_commands_incidents", [ "last_notification" => date('Y-m-d H:i:s') ], ['id' => $unresolved_incident['id']]);
                App::send_alert_notif('unresolved', 'command', $unresolved_incident['commandid']);
                $count++;
            }
        }

        return $count;
    }


}
