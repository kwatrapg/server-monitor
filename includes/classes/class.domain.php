<?php

class Domain extends App {


    public static function add($data) {
        global $database;
        if (!License::canAdd('max_domains')) return "12";
        $lastid = $database->insert("app_domains", [
            "groupid" => $data['groupid'],
            "name" => $data['name'],
            "domain" => $data['domain'],
            "status" => 0,
            "geodata" => "",
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
        ]);

        $database->insert("app_domains_alerts", [
            "domainid" => $lastid,
            "type" => "expiringsoon",
            "comparison" => "<=",
            "comparison_limit" => "30",
            "occurrences" => 1,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_domains_alerts", [
            "domainid" => $lastid,
            "type" => "expired",
            "comparison" => "<=",
            "comparison_limit" => "0",
            "occurrences" => 1,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        if ($lastid == "0") { return "11"; } else { logSystem("Domain Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
        global $database;
        $database->update("app_domains", [
            "groupid" => $data['groupid'],
            "name" => $data['name'],
            "domain" => $data['domain'],
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
        ], [ "id" => $data['id'] ]);
        logSystem("Domain Edited - ID: " . $data['id']);
        return "20";
    }


    public static function delete($id) {
        global $database;
        $database->delete("app_domains", [ "id" => $id ]);
        $database->delete("app_domains_alerts", [ "domainid" => $id ]);
        $database->delete("app_domains_history", [ "domainid" => $id ]);
        $database->delete("app_domains_incidents", [ "domainid" => $id ]);

        logSystem("Domain Deleted - ID: " . $id);
        return "30";
    }


    public static function lastChecked($id) {
        global $database;

        $latestentryid = $database->max("app_domains_history", "id", ["domainid" => $id]);
        $latest = $database->get("app_domains_history", "timestamp", ["id" => $latestentryid]);

        if(!empty($latest)) return $latest;
        else return "";
    }

    public static function latestData($id) {
        global $database;

        $latestentryid = $database->max("app_domains_history", "id", ["domainid" => $id]);
        $latest = $database->get("app_domains_history", "*", ["id" => $latestentryid]);

        return $latest;
    }


    // alerts
    public static function addAlert($data) {
        global $database;
        $lastid = $database->insert("app_domains_alerts", [
            "domainid" => $data['domainid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Domain Alert Added - ID: " . $lastid); return "10"; }
    }


    public static function editAlert($data) {
        global $database;
        $database->update("app_domains_alerts", [
            "domainid" => $data['domainid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ], [ "id" => $data['id'] ]);
        logSystem("Domain Alert Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAlert($id) {
        global $database;
        $database->delete("app_domains_alerts", [ "id" => $id ]);
        logSystem("Domain Alert Deleted - ID: " . $id);
        return "30";
    }


    public static function markIncident($id) {
        global $database;

        $database->update("app_domains_incidents", [
            "status" => 1,
            'end_time' => date('Y-m-d H:i:s')
        ], [ "id" => $id ]);

        $domainid = $database->get("app_domains_incidents", "domainid", ["id" => $id]);

        $general_status = 1;

        if( $database->has("app_domains_incidents", [ "AND" => [ 'domainid'=> $domainid, 'status' => 2 ] ] )) {
            $general_status = 2;
        }
        elseif( $database->has("app_domains_incidents", [ "AND" => [ 'domainid'=> $domainid, 'status' => 3 ] ] )) {
            $general_status = 3;
        }

        $database->update("app_domains", ['status' => $general_status], ['id' => $domainid]);

        logSystem("Domain Incident Marked Resolved - ID: " . $id);
        return "20";
    }


    public static function editComment($data) {
        global $database;

        $database->update("app_domains_incidents", [
            "comment" => $data['comment'],
            "ignore" => $data['ignore']

        ], [ "id" => $data['id'] ]);

        logSystem("Domain Incident Comment Updated - ID: " . $data['id']);
        return "20";
    }


    // ----------------------------------------------------------------------------------------------
    // WHOIS CHECKING

    private static function whoisBinary() {
        if(file_exists('/usr/bin/whois')) return '/usr/bin/whois';
        return 'whois';
    }

    private static function getWhoisOutput($domain) {
        return shell_exec(self::whoisBinary() . " " . escapeshellarg($domain) . " 2>&1");
    }

    private static function parseWhoisInfo($output) {
        $expiry = null;
        $registrar = null;

        $patterns = [
            '/Expiry Date:\s*([0-9T:\- ]+)/i',
            '/Registry Expiry Date:\s*([0-9T:\- ]+)/i',
            '/paid-till:\s*([0-9\.\-]+)/i',
            '/Expiration Date:\s*([0-9T:\- ]+)/i'
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $output, $m)) {
                $ts = strtotime($m[1]);
                if ($ts) { $expiry = date('Y-m-d', $ts); break; }
            }
        }

        if (preg_match('/Registrar:\s*(.+)/i', $output, $r)) {
            $registrar = trim($r[1]);
        }

        return [$expiry, $registrar];
    }


    public static function checkAll() {
        global $database;
        $domains = getTable("app_domains");
        $count = 0;
        $today = new DateTime();

        foreach($domains as $domain) {
            $domainName = trim($domain['domain']);
            if(!$domainName) continue;

            $whois = self::getWhoisOutput($domainName);
            $count++;

            if(!$whois) continue;

            list($expiry, $registrar) = self::parseWhoisInfo($whois);
            if(!$expiry) continue;

            $expiryDT = new DateTime($expiry);
            $days_remaining = (int)$today->diff($expiryDT)->format('%r%a');

            $database->insert("app_domains_history", [
                "domainid" => $domain['id'],
                "timestamp" => date('Y-m-d H:i:s'),
                "expiry_date" => $expiry,
                "registrar" => (string)$registrar,
                "days_remaining" => $days_remaining,
            ]);
        }

        return $count;
    }


    public static function processAll() {
        global $database;
        $domains = getTable("app_domains");
        $count = 0;

        foreach($domains as $domain) {
            $alerts = getTableFiltered("app_domains_alerts","domainid",$domain['id'],"status",1);

            foreach ($alerts as $alert) {
                $occured = 0;
                $incident_level = ($alert['type'] == "expired") ? 3 : 2;

                $history = $database->select("app_domains_history", "*", [ "domainid" => $domain['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                foreach($history as $item) { if( compare($item['days_remaining'], $alert['comparison_limit'], $alert['comparison']) ) $occured++; }

                if($occured >= $alert['occurrences'] && $occured > 0) {
                    if( !$database->has("app_domains_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ] )) {
                        $database->insert("app_domains_incidents", [
                            "domainid" => $domain['id'],
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
                        App::send_alert_notif('open', 'domain', $alert['id']);
                    }
                } else {
                    if( $database->has("app_domains_incidents", [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ] )) {
                        $database->update("app_domains_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ]);
                        App::send_alert_notif('close', 'domain', $alert['id']);
                    }
                }

            } // end alerts processing


            $general_status = 1;
            if(empty($alerts)) $general_status = 0;

            if( $database->has("app_domains_incidents", [ "AND" => [ 'domainid'=> $domain['id'], 'status' => 2 ] ] )) {
                $general_status = 2;
            }
            elseif( $database->has("app_domains_incidents", [ "AND" => [ 'domainid'=> $domain['id'], 'status' => 3 ] ] )) {
                $general_status = 3;
            }

            $database->update("app_domains", ['status' => $general_status], ['id' => $domain['id']]);

            $count++;
        }

        return $count;
    }


    public static function sendUnresolvedNotifications() {
        global $database;
        $count = 0;
        $now = strtotime("now");

        $unresolved_incidents = getTableFiltered("app_domains_incidents","status[!]","1","repeats[!]","0");

        foreach($unresolved_incidents as $unresolved_incident) {
            $last_notification = strtotime($unresolved_incident['last_notification']);
            $difference = $now - $last_notification;

            $required_difference = 60 * $unresolved_incident['repeats'];

            if($difference >= $required_difference) {
                $database->update("app_domains_incidents", [ "last_notification" => date('Y-m-d H:i:s') ], ['id' => $unresolved_incident['id']]);

                App::send_alert_notif('unresolved', 'domain', $unresolved_incident['alertid']);
                $count++;
            }
        }

        return $count;
    }


}

?>
