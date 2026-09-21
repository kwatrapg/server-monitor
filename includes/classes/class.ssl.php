<?php

class Ssl extends App {


    public static function add($data) {
        global $database;
        if (!License::canAdd('max_ssl')) return "12";
        $lastid = $database->insert("app_ssl", [
            "groupid" => $data['groupid'],
            "name" => $data['name'],
            "url" => $data['url'],
            "status" => 0,
            "geodata" => "",
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
        ]);

        $database->insert("app_ssl_alerts", [
            "sslid" => $lastid,
            "type" => "expiringsoon",
            "comparison" => "<=",
            "comparison_limit" => "30",
            "occurrences" => 1,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_ssl_alerts", [
            "sslid" => $lastid,
            "type" => "expired",
            "comparison" => "<=",
            "comparison_limit" => "0",
            "occurrences" => 1,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        if ($lastid == "0") { return "11"; } else { logSystem("SSL Check Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
        global $database;
        $database->update("app_ssl", [
            "groupid" => $data['groupid'],
            "name" => $data['name'],
            "url" => $data['url'],
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
        ], [ "id" => $data['id'] ]);
        logSystem("SSL Check Edited - ID: " . $data['id']);
        return "20";
    }


    public static function delete($id) {
        global $database;
        $database->delete("app_ssl", [ "id" => $id ]);
        $database->delete("app_ssl_alerts", [ "sslid" => $id ]);
        $database->delete("app_ssl_history", [ "sslid" => $id ]);
        $database->delete("app_ssl_incidents", [ "sslid" => $id ]);

        logSystem("SSL Check Deleted - ID: " . $id);
        return "30";
    }


    public static function lastChecked($id) {
        global $database;

        $latestentryid = $database->max("app_ssl_history", "id", ["sslid" => $id]);
        $latest = $database->get("app_ssl_history", "timestamp", ["id" => $latestentryid]);

        if(!empty($latest)) return $latest;
        else return "";
    }

    public static function latestData($id) {
        global $database;

        $latestentryid = $database->max("app_ssl_history", "id", ["sslid" => $id]);
        $latest = $database->get("app_ssl_history", "*", ["id" => $latestentryid]);

        return $latest;
    }


    // alerts
    public static function addAlert($data) {
        global $database;
        $lastid = $database->insert("app_ssl_alerts", [
            "sslid" => $data['sslid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("SSL Alert Added - ID: " . $lastid); return "10"; }
    }


    public static function editAlert($data) {
        global $database;
        $database->update("app_ssl_alerts", [
            "sslid" => $data['sslid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ], [ "id" => $data['id'] ]);
        logSystem("SSL Alert Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAlert($id) {
        global $database;
        $database->delete("app_ssl_alerts", [ "id" => $id ]);
        logSystem("SSL Alert Deleted - ID: " . $id);
        return "30";
    }


    public static function markIncident($id) {
        global $database;

        $database->update("app_ssl_incidents", [
            "status" => 1,
            'end_time' => date('Y-m-d H:i:s')
        ], [ "id" => $id ]);

        $sslid = $database->get("app_ssl_incidents", "sslid", ["id" => $id]);

        $general_status = 1;

        if( $database->has("app_ssl_incidents", [ "AND" => [ 'sslid'=> $sslid, 'status' => 2 ] ] )) {
            $general_status = 2;
        }
        elseif( $database->has("app_ssl_incidents", [ "AND" => [ 'sslid'=> $sslid, 'status' => 3 ] ] )) {
            $general_status = 3;
        }

        $database->update("app_ssl", ['status' => $general_status], ['id' => $sslid]);

        logSystem("SSL Incident Marked Resolved - ID: " . $id);
        return "20";
    }


    public static function editComment($data) {
        global $database;

        $database->update("app_ssl_incidents", [
            "comment" => $data['comment'],
            "ignore" => $data['ignore']

        ], [ "id" => $data['id'] ]);

        logSystem("SSL Incident Comment Updated - ID: " . $data['id']);
        return "20";
    }


    // ----------------------------------------------------------------------------------------------
    // SSL CERTIFICATE CHECKING

    private static function getSSLCertInfo($url) {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $port = parse_url($url, PHP_URL_PORT) ?: 443;

        // VAPT F-14 / A-6: never open a TLS socket to a private/reserved address.
        try {
            $pinnedIp = HostGuard::assertConnectable($host);
        } catch (\Throwable $ex) {
            logSystem("SSL check skipped (blocked target): " . $ex->getMessage());
            return null;
        }

        $context = stream_context_create(["ssl" => [
            "capture_peer_cert" => true,
            "SNI_enabled"       => true,
            "peer_name"         => $host,
            "verify_peer"       => false, // we only read the cert's expiry/issuer
            "verify_peer_name"  => false,
        ]]);
        $errNo = 0; $errStr = '';
        // Connect to the pinned IP but keep SNI/peer_name as the hostname.
        $fp = @stream_socket_client("ssl://{$pinnedIp}:{$port}", $errNo, $errStr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$fp) return null;

        $params = stream_context_get_params($fp);
        if (empty($params['options']['ssl']['peer_certificate'])) return null;

        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if (!$cert || empty($cert['validTo_time_t'])) return null;

        return [
            'valid_to' => date('Y-m-d', $cert['validTo_time_t']),
            'issuer' => is_array($cert['issuer']) ? implode(', ', array_map(function($k,$v){ return "$k=$v"; }, array_keys($cert['issuer']), $cert['issuer'])) : (string)($cert['issuer'] ?? '')
        ];
    }


    public static function checkAll() {
        global $database;
        $rows = getTable("app_ssl");
        $count = 0;
        $today = new DateTime();

        foreach($rows as $row) {
            $count++;
            $info = self::getSSLCertInfo($row['url']);
            if(!$info || empty($info['valid_to'])) continue;

            $expiryDT = new DateTime($info['valid_to']);
            $days_remaining = (int)$today->diff($expiryDT)->format('%r%a');

            $database->insert("app_ssl_history", [
                "sslid" => $row['id'],
                "timestamp" => date('Y-m-d H:i:s'),
                "expiry_date" => $info['valid_to'],
                "issuer" => (string)$info['issuer'],
                "days_remaining" => $days_remaining,
            ]);
        }

        return $count;
    }


    public static function processAll() {
        global $database;
        $rows = getTable("app_ssl");
        $count = 0;

        foreach($rows as $row) {
            $alerts = getTableFiltered("app_ssl_alerts","sslid",$row['id'],"status",1);

            foreach ($alerts as $alert) {
                $occured = 0;
                $incident_level = ($alert['type'] == "expired") ? 3 : 2;

                $history = $database->select("app_ssl_history", "*", [ "sslid" => $row['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                foreach($history as $item) { if( compare($item['days_remaining'], $alert['comparison_limit'], $alert['comparison']) ) $occured++; }

                if($occured >= $alert['occurrences'] && $occured > 0) {
                    if( !$database->has("app_ssl_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ] )) {
                        $database->insert("app_ssl_incidents", [
                            "sslid" => $row['id'],
                            "alertid" => $alert['id'],
                            "type" => $alert['type'],
                            "comparison" => $alert['comparison'],
                            "comparison_limit" => $alert['comparison_limit'],
                            "start_time" => date('Y-m-d H:i:s'),
                            "end_time" => "0000-00-00 00:00:00",
                            "repeats" => $alert['repeats'],
                            "last_notification" => date('Y-m-d H:i:s'),
                            "status" => $incident_level,
                        ]);
                        App::send_alert_notif('open', 'ssl', $alert['id']);
                    }
                } else {
                    if( $database->has("app_ssl_incidents", [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ] )) {
                        $database->update("app_ssl_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ]);
                        App::send_alert_notif('close', 'ssl', $alert['id']);
                    }
                }

            } // end alerts processing


            $general_status = 1;
            if(empty($alerts)) $general_status = 0;

            if( $database->has("app_ssl_incidents", [ "AND" => [ 'sslid'=> $row['id'], 'status' => 2 ] ] )) {
                $general_status = 2;
            }
            elseif( $database->has("app_ssl_incidents", [ "AND" => [ 'sslid'=> $row['id'], 'status' => 3 ] ] )) {
                $general_status = 3;
            }

            $database->update("app_ssl", ['status' => $general_status], ['id' => $row['id']]);

            $count++;
        }

        return $count;
    }


    public static function sendUnresolvedNotifications() {
        global $database;
        $count = 0;
        $now = strtotime("now");

        $unresolved_incidents = getTableFiltered("app_ssl_incidents","status[!]","1","repeats[!]","0");

        foreach($unresolved_incidents as $unresolved_incident) {
            $last_notification = strtotime($unresolved_incident['last_notification']);
            $difference = $now - $last_notification;

            $required_difference = 60 * $unresolved_incident['repeats'];

            if($difference >= $required_difference) {
                $database->update("app_ssl_incidents", [ "last_notification" => date('Y-m-d H:i:s') ], ['id' => $unresolved_incident['id']]);

                App::send_alert_notif('unresolved', 'ssl', $unresolved_incident['alertid']);
                $count++;
            }
        }

        return $count;
    }


}

?>
