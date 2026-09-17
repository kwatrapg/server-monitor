<?php

##################################
###           MODALS           ###
##################################

switch($_GET['modal']) {


    // servers
    case "servers/add":
        $groups = getTable("app_groups");
    break;

    case "servers/edit":
        $server = getRowById("app_servers",$_GET['id']);
        $groups = getTable("app_groups");
    break;

    case "servers/install-linux":
    case "servers/install-windows":
        $server = getRowById("app_servers",$_GET['id']);

    break;



    case "serveralerts/add":
        $contacts = getTable("app_contacts");
        $latest = Server::latestData($_GET['routeid']);
        $server = getRowById("app_servers",$_GET['routeid']);

        if(isset($latest['data'])) {
            if($server['type'] == 'linux') {
                $disks = explode(";", Server::extractData('disks', $latest['data'], true));
                array_pop($disks); // delete last
            }
            if($server['type'] == 'windows') {
                $disks = json_decode( Server::extractData('filesystems', $latest['data'], true), true);
            }

        }
    break;

    case "serveralerts/edit":
        $alert = getRowById("app_servers_alerts",$_GET['id']);
        $server = getRowById("app_servers",$_GET['routeid']);
        $contacts = getTable("app_contacts");
        $selected_contacts = unserialize((string) $alert['contacts'], ['allowed_classes' => false]);
        if(!$selected_contacts) $selected_contacts = [];
        if(empty($selected_contacts)) $selected_contacts = [];

        $latest = Server::latestData($_GET['routeid']);

        if(isset($latest['data'])) {
            if($server['type'] == 'linux') {
                $disks = explode(";", Server::extractData('disks', $latest['data'], true));
                array_pop($disks); // delete last
            }
            if($server['type'] == 'windows') {
                $disks = json_decode( Server::extractData('filesystems', $latest['data'], true), true);
            }
        }
    break;

    case "serveralerts/editComment":
        $incident = getRowById("app_servers_incidents",$_GET['id']);
    break;



    // websites
    case "websites/add":
        $groups = getTable("app_groups");
    break;

    case "websites/edit":
        $website = getRowById("app_websites",$_GET['id']);
        $groups = getTable("app_groups");
    break;

    case "websitealerts/add":
        $contacts = getTable("app_contacts");
    break;

    case "websitealerts/edit":
        $alert = getRowById("app_websites_alerts",$_GET['id']);
        $contacts = getTable("app_contacts");
        $selected_contacts = unserialize((string) $alert['contacts'], ['allowed_classes' => false]);
        if(!$selected_contacts) $selected_contacts = [];
        if(empty($selected_contacts)) $selected_contacts = [];
    break;

    case "websitealerts/editComment":
        $incident = getRowById("app_websites_incidents",$_GET['id']);
    break;


    // checks
    case "checks/add":
        $groups = getTable("app_groups");
    break;

    case "checks/edit":
        $check = getRowById("app_checks",$_GET['id']);
        $groups = getTable("app_groups");
    break;

    case "checkalerts/add":
        $check = getRowById("app_checks",$_GET['routeid']);
        $contacts = getTable("app_contacts");
    break;

    case "checkalerts/edit":
        $alert = getRowById("app_checks_alerts",$_GET['id']);
        $check = getRowById("app_checks",$alert['checkid']);
        $contacts = getTable("app_contacts");
        $selected_contacts = unserialize((string) $alert['contacts'], ['allowed_classes' => false]);
        if(!$selected_contacts) $selected_contacts = [];
        if(empty($selected_contacts)) $selected_contacts = [];
    break;

    case "checkalerts/editComment":
        $incident = getRowById("app_checks_incidents",$_GET['id']);
    break;


    // domains
    case "domains/add":
        $groups = getTable("app_groups");
    break;

    case "domains/edit":
        $domain = getRowById("app_domains",$_GET['id']);
        $groups = getTable("app_groups");
    break;

    case "domainalerts/add":
        $contacts = getTable("app_contacts");
    break;

    case "domainalerts/edit":
        $alert = getRowById("app_domains_alerts",$_GET['id']);
        $contacts = getTable("app_contacts");
        $selected_contacts = unserialize((string) $alert['contacts'], ['allowed_classes' => false]);
        if(!$selected_contacts) $selected_contacts = [];
        if(empty($selected_contacts)) $selected_contacts = [];
    break;

    case "domainalerts/editComment":
        $incident = getRowById("app_domains_incidents",$_GET['id']);
    break;


    // ssl
    case "ssl/add":
        $groups = getTable("app_groups");
    break;

    case "ssl/edit":
        $sslcert = getRowById("app_ssl",$_GET['id']);
        $groups = getTable("app_groups");
    break;

    case "sslalerts/add":
        $contacts = getTable("app_contacts");
    break;

    case "sslalerts/edit":
        $alert = getRowById("app_ssl_alerts",$_GET['id']);
        $contacts = getTable("app_contacts");
        $selected_contacts = unserialize((string) $alert['contacts'], ['allowed_classes' => false]);
        if(!$selected_contacts) $selected_contacts = [];
        if(empty($selected_contacts)) $selected_contacts = [];
    break;

    case "sslalerts/editComment":
        $incident = getRowById("app_ssl_incidents",$_GET['id']);
    break;




    // alerting - contacts
    case "contacts/add":
        $groups = getTable("app_groups");
    break;

    case "contacts/edit":
        $groups = getTable("app_groups");
        $contact = getRowById("app_contacts",$_GET['id']);
    break;


    // pages
    case "pages/add":
        $groups = getTable("app_groups");
        $all_servers = getTable("app_servers");
        $all_websites = getTable("app_websites");
        $all_checks = getTable("app_checks");
    break;

    // pages
    case "pages/edit":
        $groups = getTable("app_groups");
        $page = getRowById("app_pages",$_GET['id']);

        $all_servers = getTable("app_servers");
        $all_websites = getTable("app_websites");
        $all_checks = getTable("app_checks");

        $selected_servers = unserialize((string) $page['servers'], ['allowed_classes' => false]);
        if(!$selected_servers) $selected_servers = [];
        if(empty($selected_servers)) $selected_servers = [];

        $selected_websites = unserialize((string) $page['websites'], ['allowed_classes' => false]);
        if(!$selected_websites) $selected_websites = [];
        if(empty($selected_websites)) $selected_websites = [];

        $selected_checks = unserialize((string) $page['checks'], ['allowed_classes' => false]);
        if(!$selected_checks) $selected_checks = [];
        if(empty($selected_checks)) $selected_checks = [];

    break;

    // users
    case "users/add":
        $groups = getTable("app_groups");
        $roles = getTable("core_roles");
    break;

    // groups
    case "groups/edit":
        $group = getRowById("app_groups",$_GET['id']);
    break;


    // notifications
    case "notifications/edit":
        $template = getRowById("core_notifications",$_GET['id']);
    break;


    case "logsources/add":
        // NOT $servers - that name collides with data.php's "dashboard" route,
        // which runs unconditionally after this file and overwrites $servers
        // with only on_map=1 servers (a modal request's $route defaults to
        // "dashboard" since it has no route= param of its own).
        $logsource_servers = getTableFiltered("app_servers","groupid",$liu_groups,"","","*","name","ASC");
    break;

    case "logsources/edit":
        $logsource = getRowById("app_servers_logsources",$_GET['id']);
        $logsource_labels = Log::formatLabelsForInput($logsource['labels']);
        $logsource_servers = getTableFiltered("app_servers","groupid",$liu_groups,"","","*","name","ASC");
    break;

    case "logalerts/add":
        $logsource = getRowById("app_servers_logsources", $_GET['routeid']);
        $logsourceServer = getRowById("app_servers", $logsource['serverid']);
        $logsourceSiblingSources = getTableFiltered("app_servers_logsources","serverid",$logsourceServer['id'],"","","*","name","ASC");
        $contacts = getTable("app_contacts");
    break;

    case "logalerts/edit":
        $logalert = getRowById("app_servers_logs_alerts", $_GET['id']);
        $logsource = getRowById("app_servers_logsources", $_GET['routeid']);
        $logsourceServer = getRowById("app_servers", $logsource['serverid']);
        $logsourceSiblingSources = getTableFiltered("app_servers_logsources","serverid",$logsourceServer['id'],"","","*","name","ASC");
        $contacts = getTable("app_contacts");
        $selected_contacts = unserialize($logalert['contacts']);
        if(!$selected_contacts) $selected_contacts = [];
        if(empty($selected_contacts)) $selected_contacts = [];
    break;

    case "logalerts/editComment":
        $logincident = getRowById("app_servers_logs_incidents", $_GET['id']);
    break;


    case "commands/add":
        // NOT $servers - see the identical note on logsources/add above.
        $command_servers = getTableFiltered("app_servers","groupid",$liu_groups,"","","*","name","ASC");
        $contacts = getTable("app_contacts");
    break;

    case "commands/edit":
        $command = getRowById("app_servers_commands",$_GET['id']);
        $command_servers = getTableFiltered("app_servers","groupid",$liu_groups,"","","*","name","ASC");
        $contacts = getTable("app_contacts");
        $selected_contacts = unserialize($command['contacts']);
        if(!$selected_contacts) $selected_contacts = [];
        if(empty($selected_contacts)) $selected_contacts = [];
    break;


} // end switch

?>
