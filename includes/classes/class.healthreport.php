<?php

// Scheduled health report email: a snapshot of every monitored server, website,
// check, domain and SSL certificate plus all open incidents, rendered by
// MailTemplate::report(). Configured on Settings > Reports; sent from the cron
// (sendIfDue) or on demand (send).
class HealthReport {

    // Interval choices offered on the settings page, in hours.
    const INTERVALS = [ 1 => 'every hour', 6 => 'every 6 hours', 12 => 'every 12 hours', 24 => 'daily', 168 => 'weekly' ];


    private static function tableExists($table) {
        global $database;
        $result = $database->query("SHOW TABLES LIKE " . $database->quote($table));
        return $result && $result->fetchColumn() !== false;
    }

    private static function ago($datetime) {
        $secs = time() - strtotime($datetime);
        if ($secs < 120) return __('just now');
        if ($secs < 7200) return sprintf(__('%d min ago'), round($secs / 60));
        if ($secs < 172800) return sprintf(__('%d hours ago'), round($secs / 3600));
        return sprintf(__('%d days ago'), round($secs / 86400));
    }

    public static function scheduleLabel() {
        $hours = (int) getConfigValue("report_interval");
        return isset(self::INTERVALS[$hours]) ? __(self::INTERVALS[$hours]) : '';
    }


    // ----------------------------------------------------------------------------------------------
    // DATA
    // ----------------------------------------------------------------------------------------------

    private static function servers() {
        $items = [];
        foreach (getTable("app_servers") as $server) {
            $item = [ "name" => $server['name'], "status" => $server['status'], "detail" => "", "chips" => [] ];
            $latest = Server::latestData($server['id']);

            if (empty($latest)) {
                // no data means we can't vouch for it, whatever the stored status says
                $item['status'] = 0;
                $item['detail'] = __('No data received from the agent yet.');
                $items[] = $item;
                continue;
            }

            $q = Server::quickStats($latest['data'], $server['type']);
            // linux reports RAM in KB, windows in bytes (see json.php)
            $toMB = $server['type'] == 'linux' ? 1024 : 1048576;
            $ramPct = $q['ramtotal'] > 0 ? round($q['ramreal'] * 100 / $q['ramtotal']) : 0;

            $item['detail'] = sprintf(__('CPU %s%% · RAM %d%% (%d / %d MB) · Disk %d%%'),
                round((float) $q['cpuused']), $ramPct, $q['ramreal'] / $toMB, $q['ramtotal'] / $toMB, $q['totaldiskusedp']);

            if ($server['type'] == 'linux') {
                $item['chips'][] = "load: " . trim($q['load1']) . " / " . trim($q['load5']) . " / " . trim($q['load15']);
            }
            $uptime = Server::extractData('uptime', $latest['data'], true);
            if ($uptime !== '') {
                $secs = (int) $uptime;
                $item['chips'][] = sprintf("uptime: %dd %dh %dm", $secs / 86400, ($secs % 86400) / 3600, ($secs % 3600) / 60);
            }
            $item['chips'][] = __('last data') . ": " . self::ago($latest['timestamp']);
            $item['chips'][] = __('24h uptime') . ": " . Server::uptimePercentage($server['id'], "24h") . "%";
            $items[] = $item;
        }
        return $items;
    }

    private static function websites() {
        $items = [];
        foreach (getTable("app_websites") as $site) {
            $item = [ "name" => $site['name'], "status" => $site['status'], "detail" => $site['url'], "chips" => [] ];
            $latest = Website::latestData($site['id']);
            if (!empty($latest)) {
                $item['chips'][] = "HTTP " . $latest['statuscode'];
                $item['chips'][] = __('response') . ": " . round((float) $latest['latency'], 2) . "s";
                if ($site['expect'] !== '') $item['chips'][] = __('expected content') . ": " . ($latest['has_expected'] ? __('found') : __('missing'));
                $item['chips'][] = __('checked') . ": " . self::ago($latest['timestamp']);
            }
            $items[] = $item;
        }
        return $items;
    }

    private static function checks() {
        $items = [];
        foreach (getTable("app_checks") as $check) {
            $target = $check['host'] . ($check['port'] ? ":" . $check['port'] : "");
            $item = [ "name" => $check['name'], "status" => $check['status'], "detail" => strtoupper($check['type']) . " · " . $target, "chips" => [] ];
            $latest = Check::latestData($check['id']);
            if (!empty($latest)) {
                $unit = in_array($check['type'], ["dns", "blacklist"]) ? "s" : "ms";
                $item['chips'][] = __('latency') . ": " . $latest['latency'] . $unit;
                if ($check['type'] == "blacklist") {
                    // blacklist checks store the list of blacklists the host is on, serialized
                    $lists = unserialize((string) $latest['statuscode'], ['allowed_classes' => false]);
                    $item['chips'][] = empty($lists) ? __('not listed') : sprintf(__('listed on %d blacklists'), count((array) $lists));
                } elseif ($latest['statuscode'] !== '' && $latest['statuscode'] !== null) {
                    $item['chips'][] = __('result') . ": " . $latest['statuscode'];
                }
                $item['chips'][] = __('checked') . ": " . self::ago($latest['timestamp']);
            }
            $items[] = $item;
        }
        return $items;
    }

    // domains and SSL certificates share the same expiry-history shape
    private static function expiring($table, $class, $field, $extraField, $extraLabel) {
        $items = [];
        foreach (getTable($table) as $row) {
            $item = [ "name" => $row['name'], "status" => $row['status'], "detail" => $row[$field], "chips" => [] ];
            $latest = $class::latestData($row['id']);
            if (!empty($latest)) {
                $item['chips'][] = __('expires') . ": " . $latest['expiry_date'];
                $item['chips'][] = sprintf(__('%d days left'), $latest['days_remaining']);
                if (!empty($latest[$extraField])) $item['chips'][] = $extraLabel . ": " . $latest[$extraField];
            }
            $items[] = $item;
        }
        return $items;
    }

    private static function commands() {
        if (!self::tableExists("app_servers_commands")) return [];
        $items = [];
        foreach (getTable("app_servers_commands") as $command) {
            if ($command['status'] != 1) continue;
            $server = getRowById("app_servers", $command['serverid']);
            if ($command['last_exit_code'] === null) $status = 0;
            else $status = ((int) $command['last_exit_code'] === 0) ? 1 : 3;
            $item = [ "name" => $command['name'], "status" => $status, "detail" => ($server['name'] ?? '') . " · " . $command['command'], "chips" => [] ];
            if ($command['last_exit_code'] !== null) $item['chips'][] = __('exit code') . ": " . $command['last_exit_code'];
            if (!empty($command['last_checked'])) $item['chips'][] = __('ran') . ": " . self::ago($command['last_checked']);
            $items[] = $item;
        }
        return $items;
    }


    private static function issues() {
        global $database;
        // [incident table, asset fk column, asset table, category label]
        $sources = [
            ["app_servers_incidents", "serverid", "app_servers", __('Server')],
            ["app_websites_incidents", "websiteid", "app_websites", __('Website')],
            ["app_checks_incidents", "checkid", "app_checks", __('Check')],
            ["app_domains_incidents", "domainid", "app_domains", __('Domain')],
            ["app_ssl_incidents", "sslid", "app_ssl", __('SSL Certificate')],
            ["app_servers_logs_incidents", "serverid", "app_servers", __('Server Log')],
        ];

        $issues = [];
        foreach ($sources as [$table, $fk, $assetTable, $label]) {
            if (!self::tableExists($table)) continue;
            // ignored incidents were explicitly acknowledged - leave them out
            foreach ($database->select($table, "*", [ "AND" => [ "status[!]" => 1, "ignore[!]" => 1 ], "ORDER" => [ "start_time" => "ASC" ] ]) as $incident) {
                $asset = getRowById($assetTable, $incident[$fk]);
                $issues[] = [
                    "name" => $asset['name'] ?? __('(deleted)'),
                    "status" => $incident['status'],
                    "detail" => App::alertTypeString($incident),
                    "since" => dateTimeDisplay($incident['start_time']),
                    "category" => $label,
                ];
            }
        }

        if (self::tableExists("app_servers_commands_incidents")) {
            foreach ($database->select("app_servers_commands_incidents", "*", [ "AND" => [ "status[!]" => 1, "ignore[!]" => 1 ], "ORDER" => [ "start_time" => "ASC" ] ]) as $incident) {
                $command = getRowById("app_servers_commands", $incident['commandid']);
                $detail = __('Exit code') . " " . $incident['exit_code'] . ($incident['output'] !== '' ? " - " . mb_strimwidth($incident['output'], 0, 160, "…") : "");
                $issues[] = [ "name" => $command['name'] ?? __('(deleted)'), "status" => 3, "detail" => $detail, "since" => dateTimeDisplay($incident['start_time']), "category" => __('Command') ];
            }
        }

        // errors first, then warnings; oldest first within each
        usort($issues, function ($a, $b) { return $b['status'] <=> $a['status']; });
        return $issues;
    }


    public static function build() {
        $categories = [
            [ "label" => __('Servers'), "items" => self::servers() ],
            [ "label" => __('Websites'), "items" => self::websites() ],
            [ "label" => __('Checks'), "items" => self::checks() ],
            [ "label" => __('Domains'), "items" => self::expiring("app_domains", "Domain", "domain", "registrar", __('registrar')) ],
            [ "label" => __('SSL Certificates'), "items" => self::expiring("app_ssl", "Ssl", "url", "issuer", __('issuer')) ],
            [ "label" => __('Commands'), "items" => self::commands() ],
        ];

        $totals = [ "ok" => 0, "warning" => 0, "error" => 0, "unknown" => 0, "total" => 0 ];
        foreach ($categories as $cat) {
            foreach ($cat['items'] as $item) {
                $totals[MailTemplate::statusKey($item['status'])]++;
                $totals['total']++;
            }
        }

        return [ "totals" => $totals, "issues" => self::issues(), "categories" => $categories, "schedule" => self::scheduleLabel() ];
    }


    // ----------------------------------------------------------------------------------------------
    // SENDING
    // ----------------------------------------------------------------------------------------------

    // Sends the report to every configured recipient contact with an email
    // address. Returns [sent count, failed count, last error].
    public static function send() {
        $contactids = unserialize((string) getConfigValue("report_contacts"), ['allowed_classes' => false]);
        if (!is_array($contactids)) $contactids = [];

        $report = self::build();
        $html = MailTemplate::report($report);

        $t = $report['totals'];
        $company = getConfigValue("company_name") ?: "Sentruo";
        $state = $t['error'] ? __('DEGRADED') : ($t['warning'] ? __('WARNING') : __('OK'));
        $subject = sprintf(__('%s Health Report: %s - %d/%d healthy'), $company, $state, $t['ok'], $t['total']);

        $sent = 0; $failed = 0; $lastError = null;
        foreach ($contactids as $contactid) {
            $contact = getRowById("app_contacts", $contactid);
            if (empty($contact) || $contact['email'] == "") continue;
            $error = null;
            if (sendEmail($contact['email'], $subject, $html, 0, [], $error)) $sent++;
            else { $failed++; $lastError = $error; }
        }
        return [$sent, $failed, $lastError];
    }


    // Called from every cron run; sends when the configured interval has elapsed.
    public static function sendIfDue() {
        if (getConfigValue("report_enabled") !== "true") return 0;

        $hours = (int) getConfigValue("report_interval");
        if ($hours < 1) $hours = 24;
        $last = strtotime((string) getConfigValue("report_last_sent")) ?: 0;
        // one minute of slack so a cron that fires slightly early doesn't skip a whole cycle
        if (time() - $last < $hours * 3600 - 60) return 0;

        // mark as sent BEFORE sending, so an overlapping cron run can't send twice
        Settings::update("report_last_sent", date('Y-m-d H:i:s'));
        [$sent] = self::send();
        return $sent;
    }

}
