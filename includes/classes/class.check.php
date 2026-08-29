<?php

use \JJG\Ping as Ping;

class Check extends App {

    public static function add($data) {
    	global $database;
    	$lastid = $database->insert("app_checks", [
            "groupid" => $data['groupid'],
    		"name" => $data['name'],
            "common" => $data['common'],
            "type" => $data['type'],
            "port" => $data['port'],
            "timeout" => $data['timeout'],
            "host" => $data['host'],
            "send" => $data['send'],
            "expect" => $data['expect'],
            "status" => 0,
            "geodata" => "",
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
    	]);

        if($data['type'] == "blacklist") {
            $database->insert("app_checks_alerts", [
                "checkid" => $lastid,
                "type" => "blacklisted",
                "comparison" => "==",
                "comparison_limit" => "",
                "occurrences" => 3,
                "contacts" => getConfigValue("default_contacts"),
                "status" => 1,
            ]);
        }

        if($data['type'] == "callback") {
            $database->insert("app_checks_alerts", [
                "checkid" => $lastid,
                "type" => "unsuccessful",
                "comparison" => "==",
                "comparison_limit" => "",
                "occurrences" => 1,
                "contacts" => getConfigValue("default_contacts"),
                "status" => 1,
            ]);
        }

        if($data['type'] == "dns") {
            $database->insert("app_checks_alerts", [
                "checkid" => $lastid,
                "type" => "dnsfailed",
                "comparison" => "==",
                "comparison_limit" => "",
                "occurrences" => 3,
                "contacts" => getConfigValue("default_contacts"),
                "status" => 1,
            ]);
        }

        if($data['type'] == "tcp" or $data['type'] == "udp" or $data['type'] == "icmp") {
            $database->insert("app_checks_alerts", [
                "checkid" => $lastid,
                "type" => "offline",
                "comparison" => "==",
                "comparison_limit" => "",
                "occurrences" => 3,
                "contacts" => getConfigValue("default_contacts"),
                "status" => 1,
            ]);

            $database->insert("app_checks_alerts", [
                "checkid" => $lastid,
                "type" => "responsetime",
                "comparison" => ">=",
                "comparison_limit" => "700",
                "occurrences" => 3,
                "contacts" => getConfigValue("default_contacts"),
                "status" => 1,
            ]);
        }



    	if ($lastid == "0") { return "11"; } else { logSystem("Check Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
    	global $database;
    	$database->update("app_checks", [
            "groupid" => $data['groupid'],
    		"name" => $data['name'],
            //"common" => $data['common'],
            //"type" => $data['type'],
            "port" => $data['port'],
            "timeout" => $data['timeout'],
            "host" => $data['host'],
            "send" => $data['send'],
            "expect" => $data['expect'],
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
    	], [ "id" => $data['id'] ]);
    	logSystem("Check Edited - ID: " . $data['id']);
    	return "20";
    }


    public static function delete($id) {
    	global $database;
        $database->delete("app_checks", [ "id" => $id ]);
        $database->delete("app_checks_alerts", [ "checkid" => $id ]);
        $database->delete("app_checks_history", [ "checkid" => $id ]);
        $database->delete("app_checks_incidents", [ "checkid" => $id ]);
    	logSystem("Check Deleted - ID: " . $id);
    	return "30";
    }


    public static function lastChecked($id) {
        global $database;

        $latestentryid = $database->max("app_checks_history", "id", ["checkid" => $id]);
        $latest = $database->get("app_checks_history", "timestamp", ["id" => $latestentryid]);

        if(!empty($latest)) return $latest;
        else return "";
    }

    public static function lastLoadTime($id) {
        global $database;

        $latestentryid = $database->max("app_checks_history", "id", ["checkid" => $id]);
        $latest = $database->get("app_checks_history", "latency", ["id" => $latestentryid]);

        if(!empty($latest)) return $latest;
        else return "";
    }

    public static function latestData($id) {
        global $database;

        $latestentryid = $database->max("app_checks_history", "id", ["checkid" => $id]);
        $latest = $database->get("app_checks_history", "*", ["id" => $latestentryid]);

        return $latest;
    }



    public static function uptime($checkid,$period) {
        global $database;
        $total_secs_down = 0;

        if($period == "24h") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-24 hours',strtotime($end)));
            $total_secs = 86400;
        }

        elseif($period == "7days") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-7 days',strtotime($end)));
            $total_secs = 604800;
        }

        elseif($period == "30days") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-30 days',strtotime($end)));
            $total_secs = 2592000;
        }

        elseif($period == "12months") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-365 days',strtotime($end)));
            $total_secs = 31536000;
        }

        elseif($period == "selected") {
            $end = $_SESSION['range_end'];
            $start = $_SESSION['range_start'];
            $total_secs = strtotime($end) - strtotime($start);
        }

        else return 0;


        $incidents = $database->select("app_checks_incidents","*", [
            "AND" => [
                "checkid" => $checkid,
                "type" => ["offline", "blacklisted", "dnsfailed"],
                "ignore" => 0

            ]
        ]);

        foreach($incidents as $incident) {
            if($incident['end_time'] == "0000-00-00 00:00:00") $incident['end_time'] = date("Y-m-d H:i:s");

            if(
                // Start date is in first date range
                ($incident['start_time'] >= $start && $incident['start_time'] <= $end)
                ||
                // end date is in first date range
                ($incident['end_time'] >= $start && $incident['end_time'] <= $end)
            ) {

                if($incident['start_time'] <= $start) $incident['start_time'] = $start;
                if($incident['end_time'] >= $end) $incident['end_time'] = $end;

                $difference = strtotime($incident['end_time']) - strtotime($incident['start_time']);


                $total_secs_down = $total_secs_down + $difference;
            }
        }

        if($total_secs_down == 0) return 100;
        if($total_secs == $total_secs_down) return 0;

        $percentage = (($total_secs - $total_secs_down) / $total_secs) * 100;

        return round($percentage, 2);

    }



    // alerts
    public static function addAlert($data) {
        global $database;
        $lastid = $database->insert("app_checks_alerts", [
            "checkid" => $data['checkid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Check Alert Added - ID: " . $lastid); return "10"; }
    }


    public static function editAlert($data) {
        global $database;
        $database->update("app_checks_alerts", [
            "checkid" => $data['checkid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ], [ "id" => $data['id'] ]);
        logSystem("Check Alert Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAlert($id) {
        global $database;
        $database->delete("app_checks_alerts", [ "id" => $id ]);
        logSystem("Check Alert Deleted - ID: " . $id);
        return "30";
    }


    public static function markIncident($id) {
        global $database;

        $database->update("app_checks_incidents", [
            "status" => 1,
            'end_time' => date('Y-m-d H:i:s')
        ], [ "id" => $id ]);

        $checkid = $database->get("app_checks_incidents", "checkid", ["id" => $id]);

        $general_status = 1;

        if( $database->has("app_checks_incidents", [ "AND" => [ 'checkid'=> $checkid, 'status' => 2 ] ] )) {
            $general_status = 2;
        }
        elseif( $database->has("app_checks_incidents", [ "AND" => [ 'checkid'=> $checkid, 'status' => 3 ] ] )) {
            $general_status = 3;
        }

        $database->update("app_checks_incidents", ['status' => $general_status], ['id' => $checkid]);


        logSystem("Check Incident Marked Resolved - ID: " . $id);
        return "20";
    }


    public static function editComment($data) {
        global $database;

        $database->update("app_checks_incidents", [
            "comment" => $data['comment'],
            "ignore" => $data['ignore']
            
        ], [ "id" => $data['id'] ]);


        logSystem("Check Incident Comment Updated - ID: " . $data['id']);
        return "20";
    }


    public static function checkAll() {

        global $database;
        $count = 0;
        $checks = getTable("app_checks");



        foreach($checks as $check) {

            // $ping = new Ping($host, $ttl, $timeout);

            if($check['type'] == "tcp") {
                $ping = new Ping($check['host'], 128, $check['timeout']);
                $ping->setPort($check['port']);

                $latency = $ping->ping('fsockopen');

                if ($latency !== false) { $latency = $latency;  $statuscode = 1; }
                else { $latency = 0;  $statuscode = 0; }

                $database->insert("app_checks_history", [
                    "checkid" => $check['id'],
                    "timestamp" => date('Y-m-d H:i:s'),
                    "latency" => $latency,
                    "statuscode" => $statuscode,
                ]);
            }

            if($check['type'] == "udp") {
                $ping = new Ping($check['host'], 128, $check['timeout']);
                $ping->setPort($check['port']);

                $latency = $ping->ping('fsockopenudp');

                if ($latency !== false) { $latency = $latency;  $statuscode = 1; }
                else { $latency = 0;  $statuscode = 0; }

                $database->insert("app_checks_history", [
                    "checkid" => $check['id'],
                    "timestamp" => date('Y-m-d H:i:s'),
                    "latency" => $latency,
                    "statuscode" => $statuscode,
                ]);
            }

            if($check['type'] == "icmp") {
                $ping = new Ping($check['host'], 128, $check['timeout']);

                if(function_exists('exec')) {
                    $latency = $ping->ping('exec');
                }
                elseif(function_exists('socket_create')) {
                    $latency = $ping->ping('socket');
                }

                if ($latency !== false) { $latency = $latency;  $statuscode = 1; }
                else { $latency = 0;  $statuscode = 0; }

                $database->insert("app_checks_history", [
                    "checkid" => $check['id'],
                    "timestamp" => date('Y-m-d H:i:s'),
                    "latency" => $latency,
                    "statuscode" => $statuscode,
                ]);
            }

            if($check['type'] == "dns") {

                $latency = 0;
                $statuscode = 0;

                try {
                    $r = new Net_DNS2_Resolver(array(
                        'nameservers'   => [gethostbyname($check['send'])],
                        'use_tcp'       => true,
                        'timeout'       => $check['timeout'],
                    ));

                    $resultObject = $r->query($check['host']);

                    if(property_exists($resultObject, "response_time")) {
                        $resultArray = objectToArray($resultObject);
                        $latency = round($resultArray['response_time'], 4);

                        foreach($resultArray['answer'] as $answer) {
                            if (array_key_exists("cname",$answer)) {
                                if($answer['cname'] == $check['expect']) $statuscode = 1;
                            }

                            if (array_key_exists("address",$answer)) {
                                if($answer['address'] == $check['expect']) $statuscode = 1;
                            }
                        }
                    }
                } catch(Net_DNS2_Exception $e) {

                }

                $database->insert("app_checks_history", [
                    "checkid" => $check['id'],
                    "timestamp" => date('Y-m-d H:i:s'),
                    "latency" => $latency,
                    "statuscode" => $statuscode,
                ]);


            }

            if($check['type'] == "blacklist") {
                $result = dns_bl_lookup($check['host']);
                $database->insert("app_checks_history", [
                    "checkid" => $check['id'],
                    "timestamp" => date('Y-m-d H:i:s'),
                    "latency" => 0,
                    "statuscode" => serialize($result),
                ]);
            }


            $count++;
        }


        return $count;
    }



    public static function processAll() {
        global $database;
        $checks = getTable("app_checks");
        $count = 0;

        foreach($checks as $check) {
            $alerts = getTableFiltered("app_checks_alerts","checkid",$check['id'],"status",1);
            $incidents = getTableFiltered("app_checks_incidents","checkid",$check['id'],"status[!]",1);


            foreach ($alerts as $alert) {
                $occured = 0;


                if($alert['type'] == "offline") {
                    $history = $database->select("app_checks_history", "*", [ "checkid" => $check['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( $item['statuscode'] == 0 ) $occured++; }
                    $incident_level = 3;
                }

                if($alert['type'] == "dnsfailed") {
                    $history = $database->select("app_checks_history", "*", [ "checkid" => $check['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( $item['statuscode'] == 0 ) $occured++; }
                    $incident_level = 3;
                }

                if($alert['type'] == "responsetime") {
                    $history = $database->select("app_checks_history", "*", [ "checkid" => $check['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( compare($item['statuscode'], $alert['comparison_limit'], $alert['comparison']) ) $occured++; }
                    $incident_level = 2;
                }


                if($alert['type'] == "blacklisted") {
                    $history = $database->select("app_checks_history", "*", [ "checkid" => $check['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( count(unserialize($item['statuscode'])) > 0 ) $occured++; }
                    $incident_level = 3;
                }

                if($alert['type'] == "unsuccessful") {
                    $history = $database->select("app_checks_history", "*", [ "checkid" => $check['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( $item['statuscode'] == 0 ) $occured++; }
                    $incident_level = 3;
                }



                if($occured >= $alert['occurrences']) {
                    // check if incident is already opened, if not open a new one

                    //$test = $database->has("app_checks_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 331 ] ] );
                    //print_r($test);
                    //echo 'cdfd';
                    //die();

                    if( !$database->has("app_checks_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ] )) {
                        $incident_id = $database->insert("app_checks_incidents", [
                            "checkid" => $check['id'],
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
                        // send notification incident opened
                        App::send_alert_notif('open', 'check', $alert['id']);
                    }

                } else {
                    if( $database->has("app_checks_incidents", [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ] )) {

                        $database->update("app_checks_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ]);

                        // send notification incident closed
                        App::send_alert_notif('close', 'check', $alert['id']);
                    }
                }

            } // end alerts processing



            $general_status = 1;
            if(empty($alerts)) $general_status = 0; // unknow status if no alerts defined

            if( $database->has("app_checks_incidents", [ "AND" => [ 'checkid'=> $check['id'], 'status' => 2 ] ] )) {
                $general_status = 2;
            }
            elseif( $database->has("app_checks_incidents", [ "AND" => [ 'checkid'=> $check['id'], 'status' => 3 ] ] )) {
                $general_status = 3;
            }

            $database->update("app_checks", ['status' => $general_status], ['id' => $check['id']]);

            $count++;
        }

        return $count;

    }


    public static function sendUnresolvedNotifications() {
        global $database;
        $count = 0;
        $now = strtotime("now");

        $unresolved_incidents = getTableFiltered("app_checks_incidents","status[!]","1","repeats[!]","0");

        foreach($unresolved_incidents as $unresolved_incident) {
            $last_notification = strtotime($unresolved_incident['last_notification']);
            $difference = $now - $last_notification;

            $required_difference = 60 * $unresolved_incident['repeats'];

            if($difference >= $required_difference) {

                $database->update("app_checks_incidents", [ "last_notification" => date('Y-m-d H:i:s') ], ['id' => $unresolved_incident['id']]);

                App::send_alert_notif('unresolved', 'check', $unresolved_incident['alertid']);
                $count++;
            }
        }

        return $count;

    }



}

?>
