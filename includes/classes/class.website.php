<?php

class Website extends App {


    public static function add($data) {
    	global $database;
    	$lastid = $database->insert("app_websites", [
            "groupid" => $data['groupid'],
    		"name" => $data['name'],
            "url" => $data['url'],
            "expect" => $data['expect'],
            "timeout" => 0,
            "status" => 0,
            "geodata" => "",
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
    	]);

        $database->insert("app_websites_alerts", [
            "websiteid" => $lastid,
            "type" => "responsecode",
            "comparison" => "!=",
            "comparison_limit" => "200",
            "occurrences" => 3,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_websites_alerts", [
            "websiteid" => $lastid,
            "type" => "loadtime",
            "comparison" => ">=",
            "comparison_limit" => "5",
            "occurrences" => 3,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

    	if ($lastid == "0") { return "11"; } else { logSystem("Website Added - ID: " . $lastid); return "10"; }
    }


    public static function edit($data) {
    	global $database;
    	$database->update("app_websites", [
            "groupid" => $data['groupid'],
    		"name" => $data['name'],
            "url" => $data['url'],
            "expect" => $data['expect'],
            "timeout" => 0,
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
    	], [ "id" => $data['id'] ]);
    	logSystem("Website Edited - ID: " . $data['id']);
    	return "20";
    }


    public static function delete($id) {
    	global $database;
        $database->delete("app_websites", [ "id" => $id ]);
        $database->delete("app_websites_alerts", [ "websiteid" => $id ]);
        $database->delete("app_websites_history", [ "websiteid" => $id ]);
        $database->delete("app_websites_incidents", [ "websiteid" => $id ]);

    	logSystem("Website Deleted - ID: " . $id);
    	return "30";
    }


    public static function lastChecked($id) {
        global $database;

        $latestentryid = $database->max("app_websites_history", "id", ["websiteid" => $id]);
        $latest = $database->get("app_websites_history", "timestamp", ["id" => $latestentryid]);

        if(!empty($latest)) return $latest;
        else return "";
    }

    public static function lastLoadTime($id) {
        global $database;

        $latestentryid = $database->max("app_websites_history", "id", ["websiteid" => $id]);
        $latest = $database->get("app_websites_history", "latency", ["id" => $latestentryid]);

        if(!empty($latest)) return $latest . __('s');
        else return "-";
    }

    public static function latestData($id) {
        global $database;

        $latestentryid = $database->max("app_websites_history", "id", ["websiteid" => $id]);
        $latest = $database->get("app_websites_history", "*", ["id" => $latestentryid]);

        return $latest;
    }

    public static function uptime($websiteid,$period) {
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


        $incidents = $database->select("app_websites_incidents","*", [
            "AND" => [
                "websiteid" => $websiteid,
                "type" => "responsecode",
                "comparison" => "!=",
                "comparison_limit" => "200",
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
        $lastid = $database->insert("app_websites_alerts", [
            "websiteid" => $data['websiteid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Website Alert Added - ID: " . $lastid); return "10"; }
    }


    public static function editAlert($data) {
        global $database;
        $database->update("app_websites_alerts", [
            "websiteid" => $data['websiteid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ], [ "id" => $data['id'] ]);
        logSystem("Website Alert Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAlert($id) {
        global $database;
        $database->delete("app_websites_alerts", [ "id" => $id ]);
        logSystem("Website Alert Deleted - ID: " . $id);
        return "30";
    }


    public static function markIncident($id) {
        global $database;

        $database->update("app_websites_incidents", [
            "status" => 1,
            'end_time' => date('Y-m-d H:i:s')
        ], [ "id" => $id ]);

        $websiteid = $database->get("app_websites_incidents", "websiteid", ["id" => $id]);

        $general_status = 1;

        if( $database->has("app_websites_incidents", [ "AND" => [ 'websiteid'=> $websiteid, 'status' => 2 ] ] )) {
            $general_status = 2;
        }
        elseif( $database->has("app_websites_incidents", [ "AND" => [ 'websiteid'=> $websiteid, 'status' => 3 ] ] )) {
            $general_status = 3;
        }

        $database->update("app_websites", ['status' => $general_status], ['id' => $websiteid]);


        logSystem("Website Incident Marked Resolved - ID: " . $id);
        return "20";
    }


    public static function editComment($data) {
        global $database;

        $database->update("app_websites_incidents", [
            "comment" => $data['comment'],
            "ignore" => $data['ignore']
            
        ], [ "id" => $data['id'] ]);


        logSystem("Website Incident Comment Updated - ID: " . $data['id']);
        return "20";
    }



    public static function checkAll() {
        global $database;
        $websites = getTable("app_websites");
        $max_requests = 10;
        $count = 0;

        $curl_options = array(
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13',

        );

        $parallel_curl = new ParallelCurl($max_requests, $curl_options);

        foreach($websites as $website) {
            $parallel_curl->startRequest($website['url'], $website['id'], $website['expect'], 'website_request_done');


            $count++;
        }

        $parallel_curl->finishAllRequests();


        return $count;
    }


    public static function processAll() {
        global $database;
        $websites = getTable("app_websites");
        $count = 0;

        foreach($websites as $website) {
            $alerts = getTableFiltered("app_websites_alerts","websiteid",$website['id'],"status",1);
            $incidents = getTableFiltered("app_websites_incidents","websiteid",$website['id'],"status[!]",1);

            //foreach($incidents as $incident) {

            //}

            foreach ($alerts as $alert) {
                $occured = 0;

                if($alert['type'] == "responsecode") {
                    $history = $database->select("app_websites_history", "*", [ "websiteid" => $website['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( compare($item['statuscode'], $alert['comparison_limit'], $alert['comparison']) ) $occured++; }
                    $incident_level = 3;
                }

                if($alert['type'] == "loadtime") {
                    $history = $database->select("app_websites_history", "*", [ "websiteid" => $website['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( compare($item['latency'], $alert['comparison_limit'], $alert['comparison']) ) $occured++; }
                    $incident_level = 2;
                }

                if($alert['type'] == "searchstringmissing") {
                    $history = $database->select("app_websites_history", "*", [ "websiteid" => $website['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                    foreach($history as $item) { if( $item['has_expected'] == 0 ) $occured++; }
                    $incident_level = 2;
                }

                if($occured >= $alert['occurrences']) {
                    // check if incident is already opened, if not open a new one
                    if( !$database->has("app_websites_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ] )) {
                        $incident_id = $database->insert("app_websites_incidents", [
                            "websiteid" => $website['id'],
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
                        App::send_alert_notif('open', 'website', $alert['id']);
                    }

                } else {
                    if( $database->has("app_websites_incidents", [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ] )) {

                        $database->update("app_websites_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ]);

                        // send notification incident closed
                        App::send_alert_notif('close', 'website', $alert['id']);
                    }
                }

            } // end alerts processing




            $general_status = 1;
            if(empty($alerts)) $general_status = 0; // unknow status if no alerts defined

            if( $database->has("app_websites_incidents", [ "AND" => [ 'websiteid'=> $website['id'], 'status' => 2 ] ] )) {
                $general_status = 2;
            }
            elseif( $database->has("app_websites_incidents", [ "AND" => [ 'websiteid'=> $website['id'], 'status' => 3 ] ] )) {
                $general_status = 3;
            }

            $database->update("app_websites", ['status' => $general_status], ['id' => $website['id']]);

            $count++;
        }

        return $count;

    }


    public static function sendUnresolvedNotifications() {
        global $database;
        $count = 0;
        $now = strtotime("now");

        $unresolved_incidents = getTableFiltered("app_websites_incidents","status[!]","1","repeats[!]","0");

        foreach($unresolved_incidents as $unresolved_incident) {
            $last_notification = strtotime($unresolved_incident['last_notification']);
            $difference = $now - $last_notification;

            $required_difference = 60 * $unresolved_incident['repeats'];

            if($difference >= $required_difference) {
                $database->update("app_websites_incidents", [ "last_notification" => date('Y-m-d H:i:s') ], ['id' => $unresolved_incident['id']]);

                App::send_alert_notif('unresolved', 'website', $unresolved_incident['alertid']);
                $count++;
            }
        }

        return $count;


        // 2022-08-25 10:20:49
        
    }







}

?>
