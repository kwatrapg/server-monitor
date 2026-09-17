<?php

class Server extends App {


    public static function add($data) {
    	global $database;
    	$lastid = $database->insert("app_servers", [
            "groupid" => $data['groupid'],
            "type" => $data['type'],
    		"name" => $data['name'],
            "serverkey" => randomString(64),
            "logs_token" => randomString(64),
            "status" => 0,
            "geodata" => "",
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
    	]);


        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "nodata",
            "comparison" => "==",
            "comparison_limit" => "",
            "occurrences" => 3,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "cpu",
            "comparison" => ">=",
            "comparison_limit" => "90",
            "occurrences" => 5,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "ram",
            "comparison" => ">=",
            "comparison_limit" => "95",
            "occurrences" => 5,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "disk",
            "comparison" => ">=",
            "comparison_limit" => "80",
            "occurrences" => 3,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);



    	if ($lastid == "0") { return "11"; } else {
            logSystem("Server Added - ID: " . $lastid);

            return "10";
        }


    }


    public static function edit($data) {
    	global $database;
    	$database->update("app_servers", [
            "groupid" => $data['groupid'],
    		"name" => $data['name'],
            "on_map" => $data['on_map'],
            "lat" => $data['lat'],
            "lng" => $data['lng']
    	], [ "id" => $data['id'] ]);
    	logSystem("Server Edited - ID: " . $data['id']);
    	return "20";
    }


    public static function delete($id) {
    	global $database;
        $database->delete("app_servers", [ "id" => $id ]);
        $database->delete("app_servers_alerts", [ "serverid" => $id ]);
        $database->delete("app_servers_history", [ "serverid" => $id ]);
        $database->delete("app_servers_incidents", [ "serverid" => $id ]);
    	logSystem("Server Deleted - ID: " . $id);
    	return "30";
    }



    ###############################
    ###         ALERTS          ###
    ###############################

    public static function addAlert($data) {
        global $database;
        $lastid = $database->insert("app_servers_alerts", [
            "serverid" => $data['serverid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Server Alert Added - ID: " . $lastid); return "10"; }
    }


    public static function editAlert($data) {
        global $database;
        $database->update("app_servers_alerts", [
            "serverid" => $data['serverid'],
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => $data['occurrences'],
            "contacts" => serialize($data['contacts']),
            "status" => $data['status'],
            "repeats" => $data['repeats'],
        ], [ "id" => $data['id'] ]);
        logSystem("Server Alert Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAlert($id) {
        global $database;
        $database->delete("app_servers_alerts", [ "id" => $id ]);
        logSystem("Server Alert Deleted - ID: " . $id);
        return "30";
    }

    public static function markIncident($id) {
        global $database;

        $database->update("app_servers_incidents", [
            "status" => 1,
            'end_time' => date('Y-m-d H:i:s')
        ], [ "id" => $id ]);

        logSystem("Server Incident Marked Resolved - ID: " . $id);
        return "20";
    }



    public static function editComment($data) {
        global $database;

        $database->update("app_servers_incidents", [
            "comment" => $data['comment'],
            "ignore" => $data['ignore']
            
        ], [ "id" => $data['id'] ]);


        logSystem("Server Incident Comment Updated - ID: " . $data['id']);
        return "20";
    }



    public static function uptimePercentage($serverid,$period) {
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


        $incidents = $database->select("app_servers_incidents","*", [
            "AND" => [
                "serverid" => $serverid,
                "type" => "nodata",
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


    public static function cpuPercentage($current=0,$last=0,$divider=1) {
        $diff = $current - $last;

        if($diff == 0) return 0;
        else return round((($diff/100)*100)/$divider, 2);
    }


    public static function cpuAllStats($cpu_info_current,$cpu_info) {
        $cpu_info = explode(";", $cpu_info); array_pop($cpu_info);
        $cpu_info_current = explode(";", $cpu_info_current); array_pop($cpu_info_current);
		$cpucount = count($cpu_info_current); // aggregated + actual number of cores

        $stats = array();

        for ($x = 0; $x < $cpucount; $x++) {

            $cpu_prev = explode(",", $cpu_info[$x]);
            $cpu_curr = explode(",", $cpu_info_current[$x]);

            if($cpu_curr[8] == "") $cpu_curr[8] = 0; // kernels older than 2.6.11
            if($cpu_curr[9] == "") $cpu_curr[9] = 0; // kernels older than 2.6.24
            if($cpu_curr[10] == "") $cpu_curr[10] = 0; // kernels older than 2.6.24

            if($cpu_prev[8] == "") $cpu_prev[8] = 0; // kernels older than 2.6.11
            if($cpu_prev[9] == "") $cpu_prev[9] = 0; // kernels older than 2.6.24
            if($cpu_prev[10] == "") $cpu_prev[10] = 0; // kernels older than 2.6.24


            $dif['user'] = $cpu_curr[1] - $cpu_prev[1];
            $dif['nice'] = $cpu_curr[2] - $cpu_prev[2];
            $dif['system'] = $cpu_curr[3] - $cpu_prev[3];
            $dif['idle'] = $cpu_curr[4] - $cpu_prev[4];
            $dif['iowait'] = $cpu_curr[5] - $cpu_prev[5];
            $dif['irq'] = $cpu_curr[6] - $cpu_prev[6];
            $dif['softirq'] = $cpu_curr[7] - $cpu_prev[7];
            $dif['steal'] = $cpu_curr[8] - $cpu_prev[8];
            $dif['guest'] = $cpu_curr[9] - $cpu_prev[9];
            $dif['guestnice'] = $cpu_curr[10] - $cpu_prev[10];

            $virttime = $dif['guest'] + $dif['guestnice'];
            //$busytime = $dif['user'] + $dif['nice'] + $dif['system'] + $dif['irq'] + $dif['softirq'] + $dif['steal'];
            //$freetime = $dif['idle'] + $dif['iowait'];

            $total = array_sum($dif);
            $realtotal = array_sum($dif) - $virttime;
            if($realtotal == 0) $realtotal = 0.01; // pre division by zero


            $stats['cpu'][$x]['user'] = round( $dif['user'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['nice'] = round( $dif['nice'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['system'] = round( $dif['system'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['idle'] = round( $dif['idle'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['iowait'] = round( $dif['iowait'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['irq'] = round( $dif['irq'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['softirq'] = round( $dif['softirq'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['steal'] = round( $dif['steal'] / $realtotal * 100, 2 );

            $stats['cpu'][$x]['guest'] = round( $dif['guest'] / $total * 100, 2 );
            $stats['cpu'][$x]['guestnice'] = round( $dif['guestnice'] / $total * 100, 2 );

            $stats['cpu'][$x]['usage'] = 100 - $stats['cpu'][$x]['idle'] - $stats['cpu'][$x]['iowait'];

        }

        return $stats;

    }

    public static function quickStats($data, $platform="linux") {
        $qstats = array();

        if($platform == "linux") {
            // disk usage
            $disktotal = 0; $diskused = 0;
            $disks_data = explode(";", Server::extractData('disks', $data, true)); array_pop($disks_data); // delete last
            $disks_count  = count($disks_data);
            for ($x = 0; $x < $disks_count; $x++) {
                $disk_data = explode(",", $disks_data[$x]);
                $disktotal += $disk_data[2];
                $diskused += $disk_data[3];
            }
            if($disktotal == 0) $disktotal = 0.01; // pre division by zero (e.g. corrupt/missing history data)
            $qstats['totaldiskusedp'] = round( ($diskused/$disktotal)*100 );

            //ram usage
            // Cast to float: extractData() returns "" for a missing/corrupt tag (e.g. when
            // gzuncompress() failed on this row's data), and PHP 8 throws a TypeError on
            // "" + "" rather than coercing to 0 like PHP 7 did.
            $qstats['ramtotal'] = (float) Server::extractData('ram_total', $data, true);
            $qstats['ramcaches'] = (float) Server::extractData('ram_caches', $data, true);
            $qstats['rambuffers'] = (float) Server::extractData('ram_buffers', $data, true);

            $qstats['ramfree'] = (float) Server::extractData('ram_free', $data, true) + $qstats['ramcaches'] + $qstats['rambuffers'];
            $qstats['ramused'] = $qstats['ramtotal'] - (float) Server::extractData('ram_free', $data, true);
            $qstats['ramreal'] = $qstats['ramused'] - $qstats['ramcaches'] - $qstats['rambuffers'];


            //cpu
            $cpustats = Server::cpuAllStats(Server::extractData('cpu_info_current', $data), Server::extractData('cpu_info', $data));
            $qstats['cpuused'] = $cpustats['cpu'][0]['usage'];


            // Load
            $i = 1;
            foreach (explode(",", Server::extractData('cpu_load', $data, true)) as $value) {
                if ($i == 1) $qstats['load1'] = $value;
                if ($i == 2) $qstats['load5'] = $value;
                if ($i == 3) $qstats['load15'] = $value;
                $i++;
            }

            // Net
            $totalin = 0; $totalout = 0;
            $all_interfaces = explode(";", Server::extractData('all_interfaces', $data)); array_pop($all_interfaces);
            $all_interfaces_current = explode(";", Server::extractData('all_interfaces_current', $data)); array_pop($all_interfaces_current);
            $interface_count  = count($all_interfaces_current);
            for ($x = 0; $x < $interface_count; $x++) {
                $interface = explode(",", $all_interfaces[$x]); $interface_current = explode(",", $all_interfaces_current[$x]);
                $totalin += $interface_current[1]  - $interface[1];
                $totalout += $interface_current[2]  - $interface[2];
            }
            $qstats['totalin'] = $totalin;
            $qstats['totalout'] = $totalout;

        } elseif ($platform == "windows") {


            // disk usage
            $disktotal = 0; $diskused = 0;
            $filesystems = json_decode( Server::extractData('filesystems', $data, true), true);
            foreach($filesystems as $filesystem) {
                if(isset($filesystem['size'])) {
                    $disktotal += $filesystem['size'];
                    $diskused += $filesystem['used'];
                }
            }
            if($disktotal == 0) $disktotal = 0.01; // pre division by zero (e.g. corrupt/missing history data)
            $qstats['totaldiskusedp'] = round( ($diskused/$disktotal)*100 );

            //ram usage
            $qstats['ramtotal'] = (float) Server::extractData('ram_total', $data, true);
            $qstats['ramcaches'] = 0;
            $qstats['rambuffers'] = 0;

            $qstats['ramfree'] = (float) Server::extractData('ram_free', $data, true);
            $qstats['ramused'] = (float) Server::extractData('ram_usage', $data, true);
            $qstats['ramreal'] = $qstats['ramused'] - $qstats['ramcaches'] - $qstats['rambuffers'];


            //cpu
            $cpu_load = json_decode( Server::extractData('cpu_load', $data, true), true);
            $qstats['cpuused'] = round($cpu_load['currentload'],2);

            // Load
            $qstats['load1'] = $cpu_load['avgload'];
            $qstats['load5'] = $cpu_load['avgload'];
            $qstats['load15'] = $cpu_load['avgload'];


            // Net
            $totalin = 0; $totalout = 0;
            $net_stats = json_decode( Server::extractData('net_stats', $data, true), true);
            foreach($net_stats as $net_stat) {
                if($net_stat['rx_sec'] >= 0) {
                    $totalin += $net_stat['rx_sec'];
                    $totalout += $net_stat['tx_sec'];
                }
            }
            $qstats['totalin'] = $totalin;
            $qstats['totalout'] = $totalout;

            $net_interfaces = json_decode( Server::extractData('filesystems', $data, true), true);
            //echo "<pre>";
            //print_r($net_interfaces);
            //echo "</pre>";


        }



        return $qstats;

    }

    public static function extractData($key, $data, $trim = true) {
    	$start = "{" . $key . "}";
    	$end = "{/" . $key . "}";

    	$string = " " . $data;
    	$ini = strpos($string, $start);
    	if ($ini == 0) return "";
    	$ini += strlen($start);
    	$len = strpos($string, $end, $ini) - $ini;

    	if ($trim == false) return substr($string, $ini, $len);
    	if ($trim == true) return trim(substr($string, $ini, $len));

    }

    public static function latestData($serverid) {
    	global $database;

        $latestentryid = $database->max("app_servers_history", "id", ["serverid" => $serverid]);
    	$latest = $database->get("app_servers_history", "*", ["id" => $latestentryid]);

        if(isset($latest['data'])) $latest['data'] = gzuncompress($latest['data']);
    	return $latest;
    }



    # delete old data #
    public static function cleanHistory($id) {
    	global $database;

    	$history = $database->get("app_servers_history", "*", ["id" => $id]);

        if(!empty($history)) {
            $data = gzuncompress($history['data']);
            $search = array();
            $replace = array();

            $data = deleteBetween("{agent_version}", "{/agent_version}", $data);
            array_push($search, '{agent_version}', '{/agent_version}'); array_push($replace, '', '');

            $data = deleteBetween("{serverkey}", "{/serverkey}", $data);
            array_push($search, '{serverkey}', '{/serverkey}'); array_push($replace, '', '');

            $data = deleteBetween("{gateway}", "{/gateway}", $data);
            array_push($search, '{gateway}', '{/gateway}'); array_push($replace, '', '');

            $data = deleteBetween("{hostname}", "{/hostname}", $data);
            array_push($search, '{hostname}', '{/hostname}'); array_push($replace, '', '');

            $data = deleteBetween("{kernel}", "{/kernel}", $data);
            array_push($search, '{kernel}', '{/kernel}'); array_push($replace, '', '');

            $data = deleteBetween("{time}", "{/time}", $data);
            array_push($search, '{time}', '{/time}'); array_push($replace, '', '');

            $data = deleteBetween("{os}", "{/os}", $data);
            array_push($search, '{os}', '{/os}'); array_push($replace, '', '');

            $data = deleteBetween("{os_arch}", "{/os_arch}", $data);
            array_push($search, '{os_arch}', '{/os_arch}'); array_push($replace, '', '');

            $data = deleteBetween("{cpu_model}", "{/cpu_model}", $data);
            array_push($search, '{cpu_model}', '{/cpu_model}'); array_push($replace, '', '');

            $data = deleteBetween("{cpu_cores}", "{/cpu_cores}", $data);
            array_push($search, '{cpu_cores}', '{/cpu_cores}'); array_push($replace, '', '');

            $data = deleteBetween("{cpu_speed}", "{/cpu_speed}", $data);
            array_push($search, '{cpu_speed}', '{/cpu_speed}'); array_push($replace, '', '');

            //$data = deleteBetween("{cpu_load}", "{/cpu_load}", $data);
            //array_push($search, '{cpu_load}', '{/cpu_load}'); array_push($replace, '', '');

            $data = deleteBetween("{default_interface}", "{/default_interface}", $data);
            array_push($search, '{default_interface}', '{/default_interface}'); array_push($replace, '', '');

            $data = deleteBetween("{ipv4_addresses}", "{/ipv4_addresses}", $data);
            array_push($search, '{ipv4_addresses}', '{/ipv4_addresses}'); array_push($replace, '', '');

            $data = deleteBetween("{ipv6_addresses}", "{/ipv6_addresses}", $data);
            array_push($search, '{ipv6_addresses}', '{/ipv6_addresses}'); array_push($replace, '', '');

            $data = deleteBetween("{uptime}", "{/uptime}", $data);
            array_push($search, '{uptime}', '{/uptime}'); array_push($replace, '', '');

            $data = deleteBetween("{processes}", "{/processes}", $data);
            array_push($search, '{processes}', '{/processes}'); array_push($replace, '', '');

            $data = deleteBetween("{mdadm}", "{/mdadm}", $data);
            array_push($search, '{mdadm}', '{/mdadm}'); array_push($replace, '', '');


            ### WINDOWS SPECIFIC ###
            $data = deleteBetween("{net_interfaces}", "{/net_interfaces}", $data);
            array_push($search, '{net_interfaces}', '{/net_interfaces}'); array_push($replace, '', '');

            $data = deleteBetween("{default_interface}", "{/default_interface}", $data);
            array_push($search, '{default_interface}', '{/default_interface}'); array_push($replace, '', '');

            $data = deleteBetween("{disk_layout}", "{/disk_layout}", $data);
            array_push($search, '{disk_layout}', '{/disk_layout}'); array_push($replace, '', '');

            $data = deleteBetween("{system}", "{/system}", $data);
            array_push($search, '{system}', '{/system}'); array_push($replace, '', '');

            $data = deleteBetween("{bios}", "{/bios}", $data);
            array_push($search, '{bios}', '{/bios}'); array_push($replace, '', '');

            $data = deleteBetween("{baseboard}", "{/baseboard}", $data);
            array_push($search, '{baseboard}', '{/baseboard}'); array_push($replace, '', '');

            // may be used sometime
            $data = deleteBetween("{network_connections}", "{/network_connections}", $data);
            array_push($search, '{network_connections}', '{/network_connections}'); array_push($replace, '', '');

            ### END WINDOWS SPECIFIC ###

        	$data = str_replace($search, $replace, $data);
            $database->update("app_servers_history", [ "data" => gzcompress($data,9) ], [ "id" => $id ]);
        }


    }


    public static function uptime($uptime) {
        global $database;
        $uptime = floatval($uptime);
        $buh = round($uptime);
        $days = sprintf( "%2d", ($buh/(3600*24)) );
        $hours = sprintf( "%2d", ( ($buh % (3600*24)) / 3600) );
        $min = sprintf( "%2d", ($buh % (3600*24) % 3600)/60 );
        //$sec = sprintf( "%2d", ($buh % (3600*24) % 3600)%60 );

        $string = $days . " " . __('Days') . ", " . $hours . " " . __('Hours') . ", " . $min . " " . __('Minutes');
        return $string;

    }





    public static function processAll() {
        global $database;
        $servers = getTable("app_servers");
        $count = 0;

        foreach($servers as $server) {
            $alerts = getTableFiltered("app_servers_alerts","serverid",$server['id'],"status",1);
            $incidents = getTableFiltered("app_servers_incidents","serverid",$server['id'],"status[!]",1);

            if($server['type'] == 'linux') {
                foreach ($alerts as $alert) {
                    $occured = 0;


                    if($alert['type'] == "nodata") {
                        $history = $database->get("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'] ]);

                        if(!empty($history)) {
                            $now = strtotime(date("Y-m-d H:i:s"));
                            $latest = strtotime($history['timestamp']);

                            $now = $now - 60; // 60 seconds tolerance
                            $maxold = $now - (60 * $alert['occurrences']);
                            if( $maxold >= $latest ) $occured = $alert['occurrences'];
                        }

                        $incident_level = 3;
                    }



                    if($alert['type'] == "cpu") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $cpustats = Server::cpuAllStats(Server::extractData('cpu_info_current', $item['data']),Server::extractData('cpu_info', $item['data']));

                            if (isset($cpustats['cpu'][0]['usage'])) {
                                if( compare($cpustats['cpu'][0]['usage'], $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "cpuio") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $cpustats = Server::cpuAllStats(Server::extractData('cpu_info_current', $item['data']),Server::extractData('cpu_info', $item['data']));

                            if (isset($cpustats['cpu'][0]['iowait'])) {
                                if( compare($cpustats['cpu'][0]['iowait'], $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "load1min") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $cpu_load = explode(',', Server::extractData('cpu_load', $item['data']));
                            $load = $cpu_load[0];

                            if (isset($load)) {
                                if( compare($load, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "load5min") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $cpu_load = explode(',', Server::extractData('cpu_load', $item['data']));
                            $load = $cpu_load[1];

                            if (isset($load)) {
                                if( compare($load, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "load15min") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $cpu_load = explode(',', Server::extractData('cpu_load', $item['data']));
                            $load = $cpu_load[2];

                            if (isset($load)) {
                                if( compare($load, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }



                    if($alert['type'] == "service") {
                        $alert['occurrences'] = 1;
                        $running = 0;

                        $history = $database->get("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'] ]);
                        
                        if(!empty($history)) {
                            $history['data'] = gzuncompress($history['data']);

                            $rows = explode(";", Server::extractData('processes', $history['data'], true));
                            array_shift($rows); // delete first
                            array_pop($rows); // delete last
    
                            foreach ($rows as $row) {
                                $cells = explode(",", $row);
                                if($cells[7] == $alert['comparison_limit']) $running = 1;
                            }
    
                            if ($running == 0) {
                                $occured++;
                            }
                        }


                        $incident_level = 2;
                    }


                    if($alert['type'] == "mdadmDegraded") {
                        $alert['occurrences'] = 1;
                        $running = 0;

                        $history = $database->get("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'] ]);
                        if(!empty($history)) {
                            $history['data'] = gzuncompress($history['data']);

                            $raw_data = Server::extractData('mdadm', $history['data'], true);
                           
    
                            if (strpos($raw_data, 'degraded') !== false) {
                                $occured++;
                            }
                        }


                        $incident_level = 2;
                    }



                    if($alert['type'] == "ram") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $ram_total = round((Server::extractData('ram_total', $item['data'], true))/1024);
                            $ram_used = round((Server::extractData('ram_total', $item['data'], true)-Server::extractData('ram_free', $item['data'], true)-Server::extractData('ram_caches', $item['data'], true)-Server::extractData('ram_buffers', $item['data'], true))/1024);
                            $percentage = ($ram_used / $ram_total) * 100;

                            if (isset($percentage)) {
                                if( compare($percentage, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "ramMB") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $ramMB = round((Server::extractData('ram_total', $item['data'], true)-Server::extractData('ram_free', $item['data'], true)-Server::extractData('ram_caches', $item['data'], true)-Server::extractData('ram_buffers', $item['data'], true))/1024);

                            if (isset($ramMB)) {
                                if( compare($ramMB, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "swap") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $swap_total = round(Server::extractData('swap_total', $item['data'], true)/1024);
                            $swap_used = round(Server::extractData('swap_usage', $item['data'], true)/1024);
                            $percentage = ($swap_used / $swap_total) * 100;

                            if (isset($percentage)) {
                                if( compare($percentage, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "swapMB") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $swapMB = round(Server::extractData('swap_usage', $item['data'], true)/1024);

                            if (isset($swapMB)) {
                                if( compare($swapMB, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }


                    if($alert['type'] == "disk") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $disks_data = explode(";", Server::extractData('disks', $item['data'], true)); array_pop($disks_data); // delete last
                            $disks_count  = count($disks_data);
                            for ($x = 0; $x < $disks_count; $x++) {
                                $disk_data = explode(",", $disks_data[$x]);
                                $disktotal += $disk_data[2];
                                $diskused += $disk_data[3];
                            }
                            $diskperc = ($diskused/$disktotal)*100;

                            if (isset($diskperc)) {
                                if( compare($diskperc, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "diskGB") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $disks_data = explode(";", Server::extractData('disks', $item['data'], true)); array_pop($disks_data); // delete last
                            $disks_count  = count($disks_data);
                            for ($x = 0; $x < $disks_count; $x++) {
                                $disk_data = explode(",", $disks_data[$x]);
                                $disktotal += $disk_data[2];
                                $diskused += $disk_data[3];
                            }

                            if (isset($diskused)) {
                                if( compare($diskused/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }


                    if(strpos($alert['type'],'disk:') !== false) {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {

                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $disks_data = explode(";", Server::extractData('disks', $item['data'], true)); array_pop($disks_data); // delete last

                            $current_disk_array = explode(":",$alert['type']);
                            $current_disk = $current_disk_array[1];

                            $disks_count  = count($disks_data);
                            for ($x = 0; $x < $disks_count; $x++) {
                                $disk_data = explode(",", $disks_data[$x]);

                                if($disk_data[6] == $current_disk) {
                                    $disktotal = $disk_data[2];
                                    $diskused = $disk_data[3];
                                }
                            }
                            $diskperc = ($diskused/$disktotal)*100;

                            if (isset($diskperc)) {
                                if( compare($diskperc, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if(strpos($alert['type'],'diskGB:') !== false) {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {

                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $disks_data = explode(";", Server::extractData('disks', $item['data'], true)); array_pop($disks_data); // delete last

                            $current_disk_array = explode(":",$alert['type']);
                            $current_disk = $current_disk_array[1];

                            $disks_count  = count($disks_data);
                            for ($x = 0; $x < $disks_count; $x++) {
                                $disk_data = explode(",", $disks_data[$x]);

                                if($disk_data[6] == $current_disk) {
                                    $disktotal = $disk_data[2];
                                    $diskused = $disk_data[3];
                                }

                            }

                            if (isset($diskused)) {
                                if( compare($diskused/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "connections") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $connections = Server::extractData('active_connections', $item['data'], true);

                            if (isset($connections)) {
                                if( compare($connections, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "ssh") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $ssh = Server::extractData('ssh_sessions', $item['data'], true);

                            if (isset($ssh)) {
                                if( compare($ssh, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "ping") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $ping = Server::extractData('ping_latency', $item['data'], true);

                            if (isset($ping)) {
                                if( compare($ping, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "netdl") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);

                            $totalin = 0; $totalout = 0;
                            $all_interfaces = explode(";", Server::extractData('all_interfaces', $item['data'])); array_pop($all_interfaces);
                            $all_interfaces_current = explode(";", Server::extractData('all_interfaces_current', $item['data'])); array_pop($all_interfaces_current);
                            $interface_count  = count($all_interfaces_current);
                            for ($x = 0; $x < $interface_count; $x++) {
                                $interface = explode(",", $all_interfaces[$x]); $interface_current = explode(",", $all_interfaces_current[$x]);
                                $totalin += $interface_current[1]  - $interface[1];
                                $totalout += $interface_current[2]  - $interface[2];
                            }


                            if (isset($totalin)) {
                                if( compare($totalin/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "netup") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);

                            $totalin = 0; $totalout = 0;
                            $all_interfaces = explode(";", Server::extractData('all_interfaces', $item['data'])); array_pop($all_interfaces);
                            $all_interfaces_current = explode(";", Server::extractData('all_interfaces_current', $item['data'])); array_pop($all_interfaces_current);
                            $interface_count  = count($all_interfaces_current);
                            for ($x = 0; $x < $interface_count; $x++) {
                                $interface = explode(",", $all_interfaces[$x]); $interface_current = explode(",", $all_interfaces_current[$x]);
                                $totalin += $interface_current[1]  - $interface[1];
                                $totalout += $interface_current[2]  - $interface[2];
                            }


                            if (isset($totalout)) {
                                if( compare($totalout/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }



                    if($occured >= $alert['occurrences']) {
                        // check if incident is already opened, if not open a new one
                        if( !$database->has("app_servers_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ] )) {
                            $incident_id = $database->insert("app_servers_incidents", [
                                "serverid" => $server['id'],
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
                            App::send_alert_notif('open', 'server', $alert['id']);
                        }

                    } else {
                        if( $database->has("app_servers_incidents", [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ] )) {

                            $database->update("app_servers_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ]);

                            // send notification incident closed
                            App::send_alert_notif('close', 'server', $alert['id']);
                        }
                    }

                } // end alerts processing
            }



            if($server['type'] == 'windows') {
                foreach ($alerts as $alert) {
                    $occured = 0;


                    if($alert['type'] == "nodata") {
                        $history = $database->get("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'] ]);

                        if(!empty($history)) {
                            $now = strtotime(date("Y-m-d H:i:s"));
                            $latest = strtotime($history['timestamp']);

                            $now = $now - 60; // 60 seconds tolerance
                            $maxold = $now - (60 * $alert['occurrences']);
                            if( $maxold >= $latest ) $occured = $alert['occurrences'];
                        }

                        $incident_level = 3;
                    }



                    if($alert['type'] == "cpu") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $cpus = json_decode( Server::extractData('cpu_load', $item['data'], true), true);

                            if (isset($cpus['currentload'])) {
                                if( compare($cpus['currentload'], $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }



                    if($alert['type'] == "service") {
                        $alert['occurrences'] = 1;
                        $running = 0;

                        $history = $database->get("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'] ]);
                        $history['data'] = gzuncompress($history['data']);

                        $processes = json_decode( Server::extractData('processes', $history['data'], true), true);

                        foreach ($processes['list'] as $row) {
                            if($row['name'] == $alert['comparison_limit']) $running = 1;
                        }

                        if ($running == 0) {
                            $occured++;
                        }

                        $incident_level = 2;
                    }



                    if($alert['type'] == "ram") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $ram_total = round((Server::extractData('ram_total', $item['data'], true))/1048576);
                            $ram_used = round((Server::extractData('ram_usage', $item['data'], true))/1048576);
                            $percentage = ($ram_used / $ram_total) * 100;

                            if (isset($percentage)) {
                                if( compare($percentage, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "ramMB") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $ramMB = round((Server::extractData('ram_usage', $item['data'], true))/1048576);

                            if (isset($ramMB)) {
                                if( compare($ramMB, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "swap") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $swap_total = round(Server::extractData('swap_total', $item['data'], true)/1048576);
                            $swap_used = round(Server::extractData('swap_usage', $item['data'], true)/1048576);
                            $percentage = ($swap_used / $swap_total) * 100;

                            if (isset($percentage)) {
                                if( compare($percentage, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "swapMB") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $swapMB = round(Server::extractData('swap_usage', $item['data'], true)/1048576);

                            if (isset($swapMB)) {
                                if( compare($swapMB, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }


                    if($alert['type'] == "disk") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $filesystems = json_decode( Server::extractData('filesystems', $item['data'], true), true);
                            foreach ($filesystems as $filesystem) {
                                $disktotal += $filesystem['size'];
                                $diskused += $filesystem['used'];
                            }
                            $diskperc = ($diskused/$disktotal)*100;

                            if (isset($diskperc)) {
                                if( compare($diskperc, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "diskGB") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $filesystems = json_decode( Server::extractData('filesystems', $item['data'], true), true);
                            foreach ($filesystems as $filesystem) {
                                $disktotal += $filesystem['size'];
                                $diskused += $filesystem['used'];
                            }

                            if (isset($diskused)) {
                                if( compare($diskused/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }


                    if(strpos($alert['type'],'disk:') !== false) {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {

                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $filesystems = json_decode( Server::extractData('filesystems', $item['data'], true), true);

                            $current_disk_array = explode(":",$alert['type'],2);
                            $current_disk = $current_disk_array[1];

                            foreach ($filesystems as $filesystem) {

                                if($filesystem['fs'] == $current_disk) {
                                    $disktotal = $filesystem['size'];
                                    $diskused = $filesystem['used'];
                                }
                            }
                            $diskperc = ($diskused/$disktotal)*100;

                            if (isset($diskperc)) {
                                if( compare($diskperc, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if(strpos($alert['type'],'diskGB:') !== false) {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {

                            $item['data'] = gzuncompress($item['data']);
                            $disktotal = 0; $diskused = 0;
                            $filesystems = json_decode( Server::extractData('filesystems', $item['data'], true), true);

                            $current_disk_array = explode(":",$alert['type'],2);
                            $current_disk = $current_disk_array[1];

                            foreach ($filesystems as $filesystem) {

                                if($filesystem['fs'] == $current_disk) {
                                    $disktotal = $filesystem['size'];
                                    $diskused = $filesystem['used'];
                                }

                            }

                            if (isset($diskused)) {
                                if( compare($diskused/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }


                    if($alert['type'] == "ping") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);
                            $ping = Server::extractData('ping_latency', $item['data'], true);

                            if (isset($ping)) {
                                if( compare($ping, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "netdl") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);

                            $totalin = 0; $totalout = 0;
                            $net_stats = json_decode( Server::extractData('net_stats', $item['data'], true), true);

                            foreach($net_stats as $net_stat) {
                                $totalin += $net_stat['rx_sec'];
                                $totalout += $net_stat['tx_sec'];
                            }


                            if (isset($totalin)) {
                                if( compare($totalin/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }

                    if($alert['type'] == "netup") {
                        $history = $database->select("app_servers_history", "*", [ "serverid" => $server['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => $alert['occurrences'] ]);
                        foreach($history as $item) {
                            $item['data'] = gzuncompress($item['data']);

                            $totalin = 0; $totalout = 0;
                            $net_stats = json_decode( Server::extractData('net_stats', $item['data'], true), true);

                            foreach($net_stats as $net_stat) {
                                $totalin += $net_stat['rx_sec'];
                                $totalout += $net_stat['tx_sec'];
                            }


                            if (isset($totalout)) {
                                if( compare($totalout/1048476, $alert['comparison_limit'], $alert['comparison']) ) $occured++;
                            }

                        }
                        $incident_level = 2;
                    }



                    if($occured >= $alert['occurrences']) {
                        // check if incident is already opened, if not open a new one
                        if( !$database->has("app_servers_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ] )) {
                            $database->insert("app_servers_incidents", [
                                "serverid" => $server['id'],
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
                            App::send_alert_notif('open', 'server', $alert['id']);
                        }

                    } else {
                        if( $database->has("app_servers_incidents", [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ] )) {

                            $database->update("app_servers_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'alertid'=> $alert['id'], 'status[!]' => 1 ] ]);

                            // send notification incident closed
                            App::send_alert_notif('close', 'server', $alert['id']);
                        }
                    }

                } // end alerts processing
            }


            $general_status = 1;
            if(empty($alerts)) $general_status = 0; // unknow status if no alerts defined

            if( $database->has("app_servers_incidents", [ "AND" => [ 'serverid'=> $server['id'], 'status' => 2 ] ] )) {
                $general_status = 2;
            }

            if( $database->has("app_servers_incidents", [ "AND" => [ 'serverid'=> $server['id'], 'status' => 3 ] ] )) {
                $general_status = 3;
            }

            $database->update("app_servers", ['status' => $general_status], ['id' => $server['id']]);

            $count++;
        }

        return $count;

    }


    public static function sendUnresolvedNotifications() {
        global $database;
        $count = 0;
        $now = strtotime("now");

        $unresolved_incidents = getTableFiltered("app_servers_incidents","status[!]","1","repeats[!]","0");

        foreach($unresolved_incidents as $unresolved_incident) {
            $last_notification = strtotime($unresolved_incident['last_notification']);
            $difference = $now - $last_notification;

            $required_difference = 60 * $unresolved_incident['repeats'];

            if($difference >= $required_difference) {

                $database->update("app_servers_incidents", [ "last_notification" => date('Y-m-d H:i:s') ], ['id' => $unresolved_incident['id']]);

                App::send_alert_notif('unresolved', 'server', $unresolved_incident['alertid']);
                $count++;
            }
        }

        return $count;

    }



}

?>
