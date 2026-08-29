<?php

##################################
###         JSON DATA          ###
##################################

header('Content-Type: application/json; charset=utf-8');

// A-2: every datasource requires the matching view permission. Previously json.php
// had no authorization at all, so any authenticated user (any role) could read
// every activity / email / SMS / cron log row.
$__json_perms = [
    'servers'     => 'viewServers',
    'websites'    => 'viewWebsites',
    'checks'      => 'viewChecks',
    'domains'     => 'viewDomains',
    'ssl'         => 'viewSsl',
    'alertinglog' => 'viewAlertLogs',
    'activitylog' => 'viewLogs',
    'emaillog'    => 'viewLogs',
    'smslog'      => 'viewLogs',
    'cronlog'     => 'viewLogs',
];
$__need = $__json_perms[$_GET['json']] ?? null;
if ($__need === null || !is_array($perms) || !in_array($__need, $perms, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden', 'data' => []]);
    exit;
}

if(!isset($_GET['search']['value'])) $_GET['search']['value'] = "";
if(!isset($_GET['start'])) $_GET['start'] = 0;
if(!isset($_GET['length'])) $_GET['length'] = getConfigValue("table_records");
if(!isset($_GET['draw'])) $_GET['draw'] = 1;


switch($_GET['json']) {



    case "servers":

        $results = array();

		// count all items
		$allcount = $database->count("app_servers");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "ASC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "app_servers.id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "app_servers.status";
        if($_GET['order']['0']['column'] == "1") $sort_column = "app_servers.id";
        if($_GET['order']['0']['column'] == "2") $sort_column = "app_servers.name";
        if($_GET['order']['0']['column'] == "3") $sort_column = "app_groups.name";

		// if search string is given
		if( $_GET['search']['value'] != "") {
			$items = $database->select("app_servers", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_servers.id",
                "app_servers.groupid",
                "app_servers.type",
                "app_servers.name",
                "app_servers.status",
                "app_groups.name(groupname)"
            ],
			[
                "AND" =>
                	[
                		"app_servers.groupid" => $liu_groups,
                		"OR" =>
                			[
                                "app_servers.id[~]" => $_GET['search']['value'],
                                "app_servers.type[~]" => $_GET['search']['value'],
        						"app_servers.name[~]" => $_GET['search']['value'],
        						"app_groups.name[~]" => $_GET['search']['value'],
                			],
                	],
				"LIMIT" => [ $_GET['start'],$_GET['length'] ],
				"ORDER" => [$sort_column => $sort_direction]
			]);
			$filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
		}

		// if no search tring is set
		if( $_GET['search']['value'] == "") {
            $items = $database->select("app_servers", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_servers.id",
                "app_servers.groupid",
                "app_servers.type",
                "app_servers.name",
                "app_servers.status",
                "app_groups.name(groupname)"
            ],
			[
                "app_servers.groupid" => $liu_groups,
				"LIMIT" => [ $_GET['start'],$_GET['length'] ],
				"ORDER" => [$sort_column => $sort_direction]
			]);
			$filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
		}

		// compose results
		$i = 0;
		$results["draw"] = $_GET['draw'];
		$results["recordsTotal"] = $allcount;
		$results["data"] = array();

		foreach($items as $item) {

            $latest = Server::latestData($item['id']);

            if($item['status'] == 1) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="'. __("OK"). '"></i>';
            } elseif($item['status'] == 2) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="'. __("Warning"). '"></i>';
            } elseif($item['status'] == 3) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="'. __("Alert"). '"></i>';
            } else {
                $results["data"][$i][0] = '<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="'. __("Unknown"). '"></i>';
            }

            $results["data"][$i][1] = $item['id'];
            $results["data"][$i][2] = '<a href="?route=servers/manage-' . $item['type'] . '&id=' . $item['id'] . '">' . $item['name'] . '</a>';
            $results["data"][$i][3] = $item['groupname'];

            if(!empty($latest)) {

                $qstats = Server::quickStats($latest['data'], $item['type']);

                // OS IMAGE
                $os = Server::extractData('os', $latest['data'], true);
                if(stripos($os, 'centos') !== false) { $results["data"][$i][4] = '<img src="template/images/centos.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'cloudlinux') !== false) { $results["data"][$i][4] = '<img src="template/images/cloudlinux.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'coreos') !== false) { $results["data"][$i][4] = '<img src="template/images/coreos.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'debian') !== false) { $results["data"][$i][4] = '<img src="template/images/debian.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'fedora') !== false) { $results["data"][$i][4] = '<img src="template/images/fedora.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'freebsd') !== false) { $results["data"][$i][4] = '<img src="template/images/freebsd.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'proxmox') !== false) { $results["data"][$i][4] = '<img src="template/images/proxmox.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'redhat') !== false) { $results["data"][$i][4] = '<img src="template/images/redhat.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'routeros') !== false) { $results["data"][$i][4] = '<img src="template/images/routeros.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'suse') !== false) { $results["data"][$i][4] = '<img src="template/images/suse.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'ubuntu') !== false) { $results["data"][$i][4] = '<img src="template/images/ubuntu.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                elseif(stripos($os, 'windows') !== false) { $results["data"][$i][4] = '<img src="template/images/windows.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                else { $results["data"][$i][4] = '<img src="template/images/other.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }


                // CPU
                $results["data"][$i][5] = '
                    <span data-toggle="tooltip" title="'.$qstats['cpuused'].__('% Used').'">
                        <span data-peity=\'{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }\' class="donut">'.$qstats['cpuused'].'/100</span>
                    </span>
                ';

                // RAM
                if($item['type'] == "linux") {
                    $results["data"][$i][6] = '
                        <span data-toggle="tooltip" data-html="true"
                            title="'.formatBytes($qstats['ramtotal']*1024).' '.__('Total').' <br> '.formatBytes($qstats['ramreal']*1024).' '.__('Used').' <br> '.formatBytes($qstats['ramfree']*1024).' '.__('Free').' ">
                            <span data-peity=\'{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }\' class="donut">'.$qstats['ramreal'].'/'.$qstats['ramtotal'].'</span>
                        </span>
                    ';
                }

                if($item['type'] == "windows") {
                    $results["data"][$i][6] = '
                        <span data-toggle="tooltip" data-html="true"
                            title="'.formatBytes($qstats['ramtotal']).' '.__('Total').' <br> '.formatBytes($qstats['ramreal']).' '.__('Used').' <br> '.formatBytes($qstats['ramfree']).' '.__('Free').' ">
                            <span data-peity=\'{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }\' class="donut">'.$qstats['ramreal'].'/'.$qstats['ramtotal'].'</span>
                        </span>
                    ';
                }


                // DISK
                $results["data"][$i][7] = '
                    <span data-toggle="tooltip" title="'. $qstats['totaldiskusedp'] . __('% Used').'">
                        <span data-peity=\'{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }\' class="donut">'. $qstats['totaldiskusedp'].'/100</span>
                    </span>
                ';


                // LOAD
                if($item['type'] == "linux") {
                    $results["data"][$i][8] = '
                        <span data-toggle="tooltip" data-html="true" title="'. $qstats['load1'].' '. __('1 Min').' <br>'. $qstats['load5'].' '. __('5 Min').' <br>'. $qstats['load15'].' '. __('15 Min').' <br>">
                            '. $qstats['load1'].'
                        </span>
                    ';
                }
                if($item['type'] == "windows") {
                    $results["data"][$i][8] = '-';
                }

                // NET
                $results["data"][$i][9] = formatBytes($qstats['totalin']) . __('/s') . ' <i class="fa fa-long-arrow-down"></i><br>';
                $results["data"][$i][9] .= formatBytes($qstats['totalout']) . __('/s') . ' <i class="fa fa-long-arrow-up"></i>';


                // UPTIME DONUTS
                $results["data"][$i][10] = '
                    <span data-toggle="tooltip" title="' . __('Last 24 Hours') . ' ' . Server::uptimePercentage($item['id'],"24h") . '%">
                        <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Server::uptimePercentage($item['id'],"24h") . '/100</span>
                    </span>

                    <span data-toggle="tooltip" title="' . __('Last 7 Days') . ' ' . Server::uptimePercentage($item['id'],"7days") . '%">
                        <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Server::uptimePercentage($item['id'],"7days") . '/100</span>
                    </span>

                    <span data-toggle="tooltip" title="' . __('Last 30 Days') . ' ' . Server::uptimePercentage($item['id'],"30days") . '%">
                        <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Server::uptimePercentage($item['id'],"30days") . '/100</span>
                    </span>
                ';

                // LAST SEEN
                $results["data"][$i][11] = smartDate($latest['timestamp']);


            } else {
                $results["data"][$i][4] = ""; //os
                $results["data"][$i][5] = ""; //cpu
                $results["data"][$i][6] = ""; //ram
                $results["data"][$i][7] = ""; //disk
                $results["data"][$i][8] = ""; //load
                $results["data"][$i][9] = ""; //net
                $results["data"][$i][10] = ""; //uptime
                $results["data"][$i][11] = __('-') . "<br>" . __('Never Seen'); //last seen
            }


			$results["data"][$i][12] = "<div class='pull-right'><div class='btn-group'>";

                $results["data"][$i][12] .= '<a href="?route=servers/manage-'.$item['type'].'&id='.$item['id'].'" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-eye"></i></a>';

            	if(in_array("editServer",$perms))
                    $results["data"][$i][12] .= '<a href="#" onClick=\'showM("?modal=servers/edit&reroute=servers&routeid=&id='.$item['id'].'&section=");return false\'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a>';

            	if(in_array("deleteServer",$perms))
                    $results["data"][$i][12] .= '<a href="#" onClick=\'showM("?modal=servers/delete&reroute=servers&routeid=&id='.$item['id'].'&section=");return false\' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a>';

			$results["data"][$i][12] .= "</div></div>";

			$i++;

		}

		echo json_encode($results);

	break;



    case "websites":

        $results = array();

		// count all items
		$allcount = $database->count("app_websites");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "ASC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "app_websites.id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "app_websites.status";
        if($_GET['order']['0']['column'] == "1") $sort_column = "app_websites.id";
        if($_GET['order']['0']['column'] == "2") $sort_column = "app_websites.name";
        if($_GET['order']['0']['column'] == "3") $sort_column = "app_groups.name";


		// if search string is given
		if( $_GET['search']['value'] != "") {
			$items = $database->select("app_websites", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_websites.id",
                "app_websites.name",
                "app_websites.status",
                "app_groups.name(groupname)"
            ],
			[
                "AND" =>
                    [
                        "app_websites.groupid" => $liu_groups,
                        "OR" =>
                            [
                                "app_websites.id[~]" => $_GET['search']['value'],
                                "app_websites.name[~]" => $_GET['search']['value'],
                                "app_websites.url[~]" => $_GET['search']['value'],
                                "app_websites.status[~]" => $_GET['search']['value'],
                                "app_groups.name[~]" => $_GET['search']['value'],
                            ],
                    ],
				"LIMIT" => [ $_GET['start'],$_GET['length'] ],
				"ORDER" => [$sort_column => $sort_direction]
			]);
			$filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
		}

		// if no search tring is set
		if( $_GET['search']['value'] == "") {
            $items = $database->select("app_websites", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_websites.id",
                "app_websites.name",
                "app_websites.status",
                "app_groups.name(groupname)"
            ],
			[
                "app_websites.groupid" => $liu_groups,
				"LIMIT" => [ $_GET['start'],$_GET['length'] ],
				"ORDER" => [$sort_column => $sort_direction]
			]);
			$filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
		}

		// compose results
		$i = 0;
		$results["draw"] = $_GET['draw'];
		$results["recordsTotal"] = $allcount;
		$results["data"] = array();

		foreach($items as $item) {

            if($item['status'] == 1) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="'. __("OK"). '"></i>';
            } elseif($item['status'] == 2) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="'. __("Warning"). '"></i>';
            } elseif($item['status'] == 3) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="'. __("Alert"). '"></i>';
            } else {
                $results["data"][$i][0] = '<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="'. __("Unknown"). '"></i>';
            }

            $results["data"][$i][1] = $item['id'];
            $results["data"][$i][2] = $item['name'];
            $results["data"][$i][3] = $item['groupname'];
            $results["data"][$i][4] = smartDate(Website::lastChecked($item['id']));
            $results["data"][$i][5] = Website::lastLoadTime($item['id']);

            $results["data"][$i][6] = '
            <span data-toggle="tooltip" title="' . __('Last 24 Hours') . ' ' . Website::uptime($item['id'],"24h") . '%">
                <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Website::uptime($item['id'],"24h") . '/100</span>
            </span>

            <span data-toggle="tooltip" title="' . __('Last 7 Days') . ' ' . Website::uptime($item['id'],"7days") . '%">
                <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Website::uptime($item['id'],"7days") . '/100</span>
            </span>

            <span data-toggle="tooltip" title="' . __('Last 30 Days') . ' ' . Website::uptime($item['id'],"30days") . '%">
                <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Website::uptime($item['id'],"30days") . '/100</span>
            </span>
            ';


			$results["data"][$i][7] = "<div class='pull-right'><div class='btn-group'>";

                $results["data"][$i][7] .= '<a href="?route=websites/manage&id='.$item['id'].'" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-eye"></i></a>';

            	if(in_array("editWebsite",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=websites/edit&reroute=websites&routeid=&id='.$item['id'].'&section=");return false\'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a>';

            	if(in_array("deleteWebsite",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=websites/delete&reroute=websites&routeid=&id='.$item['id'].'&section=");return false\' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a>';

			$results["data"][$i][7] .= "</div></div>";

			$i++;

		}

		echo json_encode($results);

	break;



    case "checks":

        $results = array();

        // count all items
        $allcount = $database->count("app_checks");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "ASC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "app_checks.id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "app_checks.status";
        if($_GET['order']['0']['column'] == "1") $sort_column = "app_checks.id";
        if($_GET['order']['0']['column'] == "2") $sort_column = "app_checks.name";
        if($_GET['order']['0']['column'] == "3") $sort_column = "app_checks.type";
        if($_GET['order']['0']['column'] == "4") $sort_column = "app_groups.name";

        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("app_checks", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_checks.id",
                "app_checks.name",
                "app_checks.status",
                "app_checks.common",
                "app_checks.type",
                "app_checks.port",
                "app_checks.host",
                "app_groups.name(groupname)"
            ],
            [
                "AND" =>
                	[
                		"app_checks.groupid" => $liu_groups,
                		"OR" =>
                			[
                                "app_checks.id[~]" => $_GET['search']['value'],
                                "app_checks.name[~]" => $_GET['search']['value'],
                                "app_checks.status[~]" => $_GET['search']['value'],
                                "app_checks.type[~]" => $_GET['search']['value'],
                                "app_checks.port[~]" => $_GET['search']['value'],
                                "app_checks.host[~]" => $_GET['search']['value'],
                                "app_groups.name[~]" => $_GET['search']['value'],
                			],
                	],

                "LIMIT" => [ $_GET['start'],$_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("app_checks", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_checks.id",
                "app_checks.name",
                "app_checks.status",
                "app_checks.common",
                "app_checks.type",
                "app_checks.port",
                "app_checks.host",
                "app_groups.name(groupname)"
            ],
            [
                "app_checks.groupid" => $liu_groups,
                "LIMIT" => [ $_GET['start'],$_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;
        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            if($item['status'] == 1) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="'. __("OK"). '"></i>';
            } elseif($item['status'] == 2) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="'. __("Warning"). '"></i>';
            } elseif($item['status'] == 3) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="'. __("Alert"). '"></i>';
            } else {
                $results["data"][$i][0] = '<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="'. __("Unknown"). '"></i>';
            }

            $results["data"][$i][1] = $item['id'];
            $results["data"][$i][2] = $item['name'];

            if($item['type'] == "tcp") { $results["data"][$i][3] = __('TCP Port') . ": " . $item['port']; }
            if($item['type'] == "udp") { $results["data"][$i][3] = __('UDP Port') . ": " . $item['port']; }
            if($item['type'] == "icmp") { $results["data"][$i][3] = __('ICMP (Ping)') . ": " . $item['host']; }
            if($item['type'] == "dns") { $results["data"][$i][3] = __('DNS Lookup') . ": " . $item['host']; }
            if($item['type'] == "blacklist") { $results["data"][$i][3] = __('Blacklist Check') . ": " . $item['host']; }
            if($item['type'] == "callback") { $results["data"][$i][3] = __('Callback') . ": " . $item['host']; }

            $results["data"][$i][4] = $item['groupname'];
            $results["data"][$i][5] = smartDate(Check::lastChecked($item['id']));

            if($item['type'] == "callback") { $results["data"][$i][6] = ""; }
            else {
                $results["data"][$i][6] = '
                <span data-toggle="tooltip" title="' . __('Last 24 Hours') . ' ' . Check::uptime($item['id'],"24h") . '%">
                    <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Check::uptime($item['id'],"24h") . '/100</span>
                </span>

                <span data-toggle="tooltip" title="' . __('Last 7 Days') . ' ' . Check::uptime($item['id'],"7days") . '%">
                    <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Check::uptime($item['id'],"7days") . '/100</span>
                </span>

                <span data-toggle="tooltip" title="' . __('Last 30 Days') . ' ' . Check::uptime($item['id'],"30days") . '%">
                    <span data-peity=\'{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }\' class="donut">' . Check::uptime($item['id'],"30days") . '/100</span>
                </span>
                ';
            }



            $results["data"][$i][7] = "<div class='pull-right'><div class='btn-group'>";

                $results["data"][$i][7] .= '<a href="?route=checks/manage&id='.$item['id'].'" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-eye"></i></a>';

                if(in_array("editCheck",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=checks/edit&reroute=checks&routeid=&id='.$item['id'].'&section=");return false\'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a>';

                if(in_array("deleteCheck",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=checks/delete&reroute=checks&routeid=&id='.$item['id'].'&section=");return false\' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a>';

            $results["data"][$i][7] .= "</div></div>";

            $i++;

        }

        echo json_encode($results);

    break;



    case "domains":

        $results = array();

        // count all items
        $allcount = $database->count("app_domains");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "ASC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "app_domains.id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "app_domains.status";
        if($_GET['order']['0']['column'] == "1") $sort_column = "app_domains.id";
        if($_GET['order']['0']['column'] == "2") $sort_column = "app_domains.name";
        if($_GET['order']['0']['column'] == "3") $sort_column = "app_groups.name";

        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("app_domains", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_domains.id",
                "app_domains.name",
                "app_domains.domain",
                "app_domains.status",
                "app_groups.name(groupname)"
            ],
            [
                "AND" =>
                    [
                        "app_domains.groupid" => $liu_groups,
                        "OR" =>
                            [
                                "app_domains.id[~]" => $_GET['search']['value'],
                                "app_domains.name[~]" => $_GET['search']['value'],
                                "app_domains.domain[~]" => $_GET['search']['value'],
                                "app_domains.status[~]" => $_GET['search']['value'],
                                "app_groups.name[~]" => $_GET['search']['value'],
                            ],
                    ],
                "LIMIT" => [ $_GET['start'],$_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("app_domains", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_domains.id",
                "app_domains.name",
                "app_domains.domain",
                "app_domains.status",
                "app_groups.name(groupname)"
            ],
            [
                "app_domains.groupid" => $liu_groups,
                "LIMIT" => [ $_GET['start'],$_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;
        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            if($item['status'] == 1) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="'. __("OK"). '"></i>';
            } elseif($item['status'] == 2) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="'. __("Warning"). '"></i>';
            } elseif($item['status'] == 3) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="'. __("Alert"). '"></i>';
            } else {
                $results["data"][$i][0] = '<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="'. __("Unknown"). '"></i>';
            }

            $results["data"][$i][1] = $item['id'];
            $results["data"][$i][2] = $item['name'];
            $results["data"][$i][3] = $item['groupname'];
            $results["data"][$i][4] = $item['domain'];
            $results["data"][$i][5] = smartDate(Domain::lastChecked($item['id']));

            $latest = Domain::latestData($item['id']);
            if(!empty($latest)) {
                $days = (int)$latest['days_remaining'];
                $color = $days <= 0 ? "text-red" : ($days <= 30 ? "text-yellow" : "text-green");
                $results["data"][$i][6] = $latest['expiry_date'] . ' <span class="'.$color.'">(' . $days . ' ' . __('days') . ')</span>';
            } else {
                $results["data"][$i][6] = __('No data');
            }

            $results["data"][$i][7] = "<div class='pull-right'><div class='btn-group'>";

                $results["data"][$i][7] .= '<a href="?route=domains/manage&id='.$item['id'].'" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-eye"></i></a>';

                if(in_array("editDomain",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=domains/edit&reroute=domains&routeid=&id='.$item['id'].'&section=");return false\'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a>';

                if(in_array("deleteDomain",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=domains/delete&reroute=domains&routeid=&id='.$item['id'].'&section=");return false\' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a>';

            $results["data"][$i][7] .= "</div></div>";

            $i++;

        }

        echo json_encode($results);

    break;



    case "ssl":

        $results = array();

        // count all items
        $allcount = $database->count("app_ssl");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "ASC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "app_ssl.id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "app_ssl.status";
        if($_GET['order']['0']['column'] == "1") $sort_column = "app_ssl.id";
        if($_GET['order']['0']['column'] == "2") $sort_column = "app_ssl.name";
        if($_GET['order']['0']['column'] == "3") $sort_column = "app_groups.name";

        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("app_ssl", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_ssl.id",
                "app_ssl.name",
                "app_ssl.url",
                "app_ssl.status",
                "app_groups.name(groupname)"
            ],
            [
                "AND" =>
                    [
                        "app_ssl.groupid" => $liu_groups,
                        "OR" =>
                            [
                                "app_ssl.id[~]" => $_GET['search']['value'],
                                "app_ssl.name[~]" => $_GET['search']['value'],
                                "app_ssl.url[~]" => $_GET['search']['value'],
                                "app_ssl.status[~]" => $_GET['search']['value'],
                                "app_groups.name[~]" => $_GET['search']['value'],
                            ],
                    ],
                "LIMIT" => [ $_GET['start'],$_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("app_ssl", [ "[>]app_groups" => ["groupid" => "id"] ], [
                "app_ssl.id",
                "app_ssl.name",
                "app_ssl.url",
                "app_ssl.status",
                "app_groups.name(groupname)"
            ],
            [
                "app_ssl.groupid" => $liu_groups,
                "LIMIT" => [ $_GET['start'],$_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;
        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            if($item['status'] == 1) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="'. __("OK"). '"></i>';
            } elseif($item['status'] == 2) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="'. __("Warning"). '"></i>';
            } elseif($item['status'] == 3) {
                $results["data"][$i][0] = '<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="'. __("Alert"). '"></i>';
            } else {
                $results["data"][$i][0] = '<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="'. __("Unknown"). '"></i>';
            }

            $results["data"][$i][1] = $item['id'];
            $results["data"][$i][2] = $item['name'];
            $results["data"][$i][3] = $item['groupname'];
            $results["data"][$i][4] = $item['url'];
            $results["data"][$i][5] = smartDate(Ssl::lastChecked($item['id']));

            $latest = Ssl::latestData($item['id']);
            if(!empty($latest)) {
                $days = (int)$latest['days_remaining'];
                $color = $days <= 0 ? "text-red" : ($days <= 30 ? "text-yellow" : "text-green");
                $results["data"][$i][6] = $latest['expiry_date'] . ' <span class="'.$color.'">(' . $days . ' ' . __('days') . ')</span>';
            } else {
                $results["data"][$i][6] = __('No data');
            }

            $results["data"][$i][7] = "<div class='pull-right'><div class='btn-group'>";

                $results["data"][$i][7] .= '<a href="?route=ssl/manage&id='.$item['id'].'" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-eye"></i></a>';

                if(in_array("editSsl",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=ssl/edit&reroute=ssl&routeid=&id='.$item['id'].'&section=");return false\'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a>';

                if(in_array("deleteSsl",$perms))
                    $results["data"][$i][7] .= '<a href="#" onClick=\'showM("?modal=ssl/delete&reroute=ssl&routeid=&id='.$item['id'].'&section=");return false\' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a>';

            $results["data"][$i][7] .= "</div></div>";

            $i++;

        }

        echo json_encode($results);

    break;




    case "alertinglog":

        $results = array();

        // count all items
        $allcount = $database->count("app_alertlog");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "date";
        if($_GET['order']['0']['column'] == "1") $sort_column = "contactname";
        if($_GET['order']['0']['column'] == "2") $sort_column = "message";


        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("app_alertlog", "*",
            [
                "OR" =>
                    [
                        "id[~]" => $_GET['search']['value'],
                        "contactname[~]" => $_GET['search']['value'],
                        "date[~]" => $_GET['search']['value'],
                        "subject[~]" => $_GET['search']['value'],
                        "message[~]" => $_GET['search']['value'],
                        "email[~]" => $_GET['search']['value'],
                        "mobilenumber[~]" => $_GET['search']['value'],
                        "pushbullet[~]" => $_GET['search']['value'],
                        "twitter[~]" => $_GET['search']['value'],
                        "pushover[~]" => $_GET['search']['value'],
                    ],
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("app_alertlog", "*",
            [
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;
        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            $results["data"][$i][0] = $item['id'];
            $results["data"][$i][1] = dateTimeDisplay($item['date']);
            $results["data"][$i][2] = $item['contactname'];
            $results["data"][$i][3] = $item['message'];
            $results["data"][$i][4] = '';

            if($item['email'] != "") {
                $results["data"][$i][4] .= '<i class="fa fa-at fa-fw" data-toggle="tooltip" title="'. __("Email:"). ' ' . $item['email'] .'"></i>';
            }

            if($item['mobilenumber'] != "") {
                $results["data"][$i][4] .= '<i class="fa fa-mobile fa-fw" data-toggle="tooltip" title="'. __("Mobile Number:"). ' ' . $item['mobilenumber'] .'"></i>';
            }

            if($item['pushbullet'] != "") {
                $results["data"][$i][4] .= '<i class="fa fa-arrow-circle-o-right fa-fw" data-toggle="tooltip" title="'. __("Pushbullet:"). ' ' . $item['pushbullet'] .'"></i>';
            }

            if($item['twitter'] != "") {
                $results["data"][$i][4] .= '<i class="fa fa-twitter fa-fw" data-toggle="tooltip" title="'. __("Twitter:"). ' ' . $item['twitter'] .'"></i>';
            }

            if($item['pushover'] != "") {
                $results["data"][$i][4] .= '<i class="fa fa-caret-square-o-right fa-fw" data-toggle="tooltip" title="'. __("Pushover:"). ' ' . $item['pushover'] .'"></i>';
            }

            $i++;

        }

        echo json_encode($results);

    break;



    case "activitylog":

        $results = array();

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "core_activitylog.id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "core_activitylog.id";
        if($_GET['order']['0']['column'] == "1") $sort_column = "core_users.name";
        if($_GET['order']['0']['column'] == "2") $sort_column = "core_activitylog.ipaddress";
        if($_GET['order']['0']['column'] == "3") $sort_column = "core_activitylog.description";
        if($_GET['order']['0']['column'] == "4") $sort_column = "core_activitylog.timestamp";

        // count all items
        $allcount = $database->count("core_activitylog");

        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("core_activitylog",  [ "[>]core_users" => ["userid" => "id"] ], [
                "core_activitylog.id",
                "core_activitylog.ipaddress",
                "core_activitylog.description",
                "core_activitylog.timestamp",
                "core_users.name"
            ],
            [
                "OR" =>
                    [
                        "core_activitylog.id[~]" => $_GET['search']['value'],
                        "core_activitylog.timestamp[~]" => $_GET['search']['value'],
                        "core_activitylog.ipaddress[~]" => $_GET['search']['value'],
                        "core_activitylog.description[~]" => $_GET['search']['value'],
                        "core_users.name[~]" => $_GET['search']['value'],
                    ],
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("core_activitylog",  [ "[>]core_users" => ["userid" => "id"] ], [
                "core_activitylog.id",
                "core_activitylog.ipaddress",
                "core_activitylog.description",
                "core_activitylog.timestamp",
                "core_users.name"
            ],
            [
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;

        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            $results["data"][$i][0] = $item['id'];
            $results["data"][$i][1] = $item['name'];
            $results["data"][$i][2] = $item['ipaddress'];
            $results["data"][$i][3] = $item['description'];
            $results["data"][$i][4] = dateTimeDisplay($item['timestamp']);

            $i++;

        }

        echo json_encode($results);

    break;


    case "emaillog":

        $results = array();

        // count all items
        $allcount = $database->count("core_emaillog");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "core_emaillog.id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "core_emaillog.id";
        if($_GET['order']['0']['column'] == "1") $sort_column = "core_users.name";
        if($_GET['order']['0']['column'] == "2") $sort_column = "core_emaillog.to";
        if($_GET['order']['0']['column'] == "3") $sort_column = "core_emaillog.subject";
        if($_GET['order']['0']['column'] == "4") $sort_column = "core_emaillog.timestamp";

        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("core_emaillog",  [ "[>]core_users" => ["userid" => "id"] ], [
                "core_emaillog.id",
                "core_emaillog.to",
                "core_emaillog.subject",
                "core_emaillog.timestamp",
                "core_users.name"
            ],
            [
                "OR" =>
                    [
                        "core_emaillog.id[~]" => $_GET['search']['value'],
                        "core_emaillog.timestamp[~]" => $_GET['search']['value'],
                        "core_emaillog.to[~]" => $_GET['search']['value'],
                        "core_emaillog.subject[~]" => $_GET['search']['value'],
                        "core_users.name[~]" => $_GET['search']['value'],
                    ],
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("core_emaillog",  [ "[>]core_users" => ["userid" => "id"] ], [
                "core_emaillog.id",
                "core_emaillog.to",
                "core_emaillog.subject",
                "core_emaillog.timestamp",
                "core_users.name"
            ],
            [
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;

        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            $results["data"][$i][0] = $item['id'];
            $results["data"][$i][1] = $item['name'];
            $results["data"][$i][2] = $item['to'];
            $results["data"][$i][3] = $item['subject'];
            $results["data"][$i][4] = dateTimeDisplay($item['timestamp']);

            $i++;

        }

        echo json_encode($results);

    break;


    case "smslog":

        $results = array();

        // count all items
        $allcount = $database->count("core_smslog");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";

        if($_GET['order']['0']['dir'] == "") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "id";
        if($_GET['order']['0']['column'] == "1") $sort_column = "timestamp";
        if($_GET['order']['0']['column'] == "2") $sort_column = "to";

        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("core_smslog", "*",
            [
                "OR" =>
                    [
                        "id[~]" => $_GET['search']['value'],
                        "timestamp[~]" => $_GET['search']['value'],
                        "to[~]" => $_GET['search']['value'],
                        "message[~]" => $_GET['search']['value'],
                    ],
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("core_smslog", "*",
            [
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;

        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            $results["data"][$i][0] = $item['id'];
            $results["data"][$i][1] = dateTimeDisplay($item['timestamp']);
            $results["data"][$i][2] = $item['to'];
            $results["data"][$i][3] = $item['message'];

            $i++;

        }

        echo json_encode($results);

    break;



    case "cronlog":

        $results = array();

        // count all items
        $allcount = $database->count("core_cronlog");

        // column order mappings
        if(!isset($_GET['order']['0']['dir'])) $_GET['order']['0']['dir'] = "";
        if(!isset($_GET['order']['0']['column'])) $_GET['order']['0']['column'] = "";
        
        if($_GET['order']['0']['dir'] == "") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "desc") $sort_direction = "DESC";
        if($_GET['order']['0']['dir'] == "asc") $sort_direction = "ASC";

        if($_GET['order']['0']['column'] == "") $sort_column = "id";
        if($_GET['order']['0']['column'] == "0") $sort_column = "id";
        if($_GET['order']['0']['column'] == "1") $sort_column = "timestamp";

        // if search string is given
        if( $_GET['search']['value'] != "") {
            $items = $database->select("core_cronlog", "*",
            [
                "OR" =>
                    [
                        "id[~]" => $_GET['search']['value'],
                        "timestamp[~]" => $_GET['search']['value'],
                        "data[~]" => $_GET['search']['value'],
                        "execution_time[~]" => $_GET['search']['value'],
                    ],
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $filteredcount;
        }

        // if no search tring is set
        if( $_GET['search']['value'] == "") {
            $items = $database->select("core_cronlog", "*",
            [
                "LIMIT" => [ $_GET['start'], $_GET['length'] ],
                "ORDER" => [$sort_column => $sort_direction]
            ]);
            $filteredcount = count($items);
            $results["recordsFiltered"] = $allcount;
        }

        // compose results
        $i = 0;

        $results["draw"] = $_GET['draw'];
        $results["recordsTotal"] = $allcount;
        $results["data"] = array();

        foreach($items as $item) {

            $results["data"][$i][0] = $item['id'];
            $results["data"][$i][1] = dateTimeDisplay($item['timestamp']);
            $results["data"][$i][2] = $item['data'];

            $i++;

        }

        echo json_encode($results);

    break;







}

?>
