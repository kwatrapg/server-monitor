<?php

// Actions attached to a server alert rule, fired when that alert opens an incident.
// A webhook is POSTed directly from here; a command is only queued - the server's
// own agent fetches it (actionsconfig.php), runs it locally and reports the result
// back (actionresult.php). This app never executes anything on another machine.
class ServerAction extends App {


    // Human-readable one-liner for an alert rule, e.g. "CPU Usage % > 90" (same
    // labels as the Alerting tab).
    public static function alertLabel($alert) {
        if (empty($alert)) return __('(deleted alert)');
        $labels = [
            "nodata" => 'No Data', "cpu" => 'CPU Usage %', "cpuio" => 'CPU IO Wait %',
            "load1min" => 'System Load 1 Min', "load5min" => 'System Load 5 Min', "load15min" => 'System Load 15 Min',
            "service" => 'Service/Process Not Running', "ram" => 'RAM Usage %', "ramMB" => 'RAM Usage MB',
            "swap" => 'Swap Usage %', "swapMB" => 'Swap Usage MB', "disk" => 'Disk Usage % (Aggregated)',
            "diskGB" => 'Disk Usage GB (Aggregated)', "mdadmDegraded" => 'MDADM Degraded',
            "connections" => 'Connections', "ssh" => 'SSH Sessions', "ping" => 'Ping Latency',
            "netdl" => 'Network Download Speed MB/s', "netup" => 'Network Upload Speed MB/s',
        ];
        $type = (string) $alert['type'];
        if (isset($labels[$type])) {
            $label = __($labels[$type]);
        } elseif (strpos($type, 'diskGB:') === 0) {
            $label = __('Disk Usage GB:') . " " . substr($type, 7);
        } elseif (strpos($type, 'disk:') === 0) {
            $label = __('Disk Usage %:') . " " . substr($type, 5);
        } else {
            $label = $type;
        }

        if ($type === 'service') return $label . ": " . $alert['comparison_limit'];
        if ($type === 'nodata' || $type === 'mdadmDegraded') return $label;
        return $label . " " . $alert['comparison'] . " " . $alert['comparison_limit'];
    }


    // ----------------------------------------------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------------------------------------------

    private static function fields($data) {
        $type = ($data['type'] ?? '') === 'command' ? 'command' : 'webhook';
        return [
            "alertid" => (int) $data['alertid'],
            "name" => $data['name'],
            "type" => $type,
            "webhook_url" => $type === 'webhook' ? trim((string) ($data['webhook_url'] ?? '')) : '',
            "command" => $type === 'command' ? (string) ($data['command'] ?? '') : '',
            "timeout_seconds" => max(1, (int) ($data['timeout_seconds'] ?? 30)),
            "status" => isset($data['status']) ? (int) $data['status'] : 1,
        ];
    }

    // Returns an error status code, or null when the input is usable.
    private static function validate($fields) {
        if ($fields['type'] === 'webhook') {
            $scheme = strtolower((string) parse_url($fields['webhook_url'], PHP_URL_SCHEME));
            if (!in_array($scheme, ['http', 'https'], true)) return "11";
        }
        if ($fields['type'] === 'command' && trim($fields['command']) === '') return "11";
        return null;
    }

    public static function addAction($data) {
        global $database;
        $fields = self::fields($data);
        if ($error = self::validate($fields)) return $error;
        $fields['serverid'] = (int) $data['serverid'];
        $lastid = $database->insert("app_servers_alert_actions", $fields);
        if ($lastid == "0") { return "11"; } else { logSystem("Server Alert Action Added - ID: " . $lastid); return "10"; }
    }


    public static function editAction($data) {
        global $database;
        $fields = self::fields($data);
        if ($error = self::validate($fields)) return $error;
        $database->update("app_servers_alert_actions", $fields, [ "id" => $data['id'] ]);
        logSystem("Server Alert Action Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAction($id) {
        global $database;
        $database->delete("app_servers_alert_actions", [ "id" => $id ]);
        $database->delete("app_servers_alert_action_runs", [ "actionid" => $id ]);
        logSystem("Server Alert Action Deleted - ID: " . $id);
        return "30";
    }


    // ----------------------------------------------------------------------------------------------
    // FIRING
    // ----------------------------------------------------------------------------------------------

    // Called by Server::processAll() right after an incident is opened for $alert.
    public static function trigger($server, $alert, $incidentId) {
        global $database;

        $actions = $database->select("app_servers_alert_actions", "*", [
            "AND" => [ "alertid" => $alert['id'], "status" => 1 ],
        ]);

        foreach ($actions as $action) {
            $runId = $database->insert("app_servers_alert_action_runs", [
                "actionid" => $action['id'],
                "serverid" => $server['id'],
                "incidentid" => (int) $incidentId,
                "type" => $action['type'],
                "status" => "pending",
                "output" => "",
                "created" => date('Y-m-d H:i:s'),
            ]);

            // commands stay pending until the agent collects them
            if ($action['type'] !== 'webhook') continue;

            $payload = json_encode([
                "event" => "alert.open",
                "action" => $action['name'],
                "server" => [ "id" => (int) $server['id'], "name" => $server['name'] ],
                "alert" => [
                    "id" => (int) $alert['id'],
                    "type" => $alert['type'],
                    "comparison" => $alert['comparison'],
                    "comparison_limit" => $alert['comparison_limit'],
                ],
                "incident_id" => (int) $incidentId,
                "time" => date('c'),
            ]);

            $response = sm_safe_http_post($action['webhook_url'], $payload, 10);
            $ok = $response['error'] === null && $response['status'] >= 200 && $response['status'] < 300;

            $database->update("app_servers_alert_action_runs", [
                "status" => $ok ? "done" : "failed",
                "result_code" => $response['status'],
                "output" => substr($response['error'] ?? $response['body'], 0, 1000),
                "finished" => date('Y-m-d H:i:s'),
            ], [ "id" => $runId ]);
        }
    }


    // ----------------------------------------------------------------------------------------------
    // AGENT SIDE (command actions)
    // ----------------------------------------------------------------------------------------------

    // Hands the agent every pending command run for $serverid and marks them
    // dispatched, so each run is executed at most once even if the report is lost.
    public static function claimPendingRuns($serverid) {
        global $database;

        $runs = $database->select("app_servers_alert_action_runs", ["id", "actionid"], [
            "AND" => [ "serverid" => $serverid, "type" => "command", "status" => "pending" ],
            "ORDER" => [ "id" => "ASC" ],
        ]);

        $claimed = [];
        foreach ($runs as $run) {
            $action = getRowById("app_servers_alert_actions", $run['actionid']);
            // only mark dispatched if this request won the row (guards overlapping agent runs)
            $updated = $database->update("app_servers_alert_action_runs", [ "status" => "dispatched" ],
                [ "AND" => [ "id" => $run['id'], "status" => "pending" ] ]);
            if (empty($action) || $updated !== 1) continue;

            $claimed[] = [
                "run_id" => (int) $run['id'],
                "command" => (string) $action['command'],
                "timeout_seconds" => (int) $action['timeout_seconds'],
            ];
        }
        return $claimed;
    }


    public static function reportRun($serverid, $runId, $exitCode, $output) {
        global $database;
        $database->update("app_servers_alert_action_runs", [
            "status" => $exitCode === 0 ? "done" : "failed",
            "result_code" => $exitCode,
            "output" => $output,
            "finished" => date('Y-m-d H:i:s'),
        ], [ "AND" => [ "id" => (int) $runId, "serverid" => $serverid, "status" => "dispatched" ] ]);
    }


}
