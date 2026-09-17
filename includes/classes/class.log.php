<?php

class Log extends App {


    // ----------------------------------------------------------------------------------------------
    // LOG SOURCES
    // ----------------------------------------------------------------------------------------------

    public static function addSource($data) {
        global $database;
        $lastid = $database->insert("app_servers_logsources", [
            "serverid" => $data['serverid'],
            "name" => $data['name'],
            "path_glob" => $data['path_glob'],
            "mode" => $data['mode'],
            "include_regex" => $data['include_regex'],
            "exclude_regex" => $data['exclude_regex'],
            "multiline_pattern" => $data['multiline_pattern'],
            "rate_limit" => $data['rate_limit'],
            "sample_rate" => $data['sample_rate'],
            "labels" => serialize(self::parseLabelsInput($data['labels'] ?? '')),
            "status" => 1,
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Log Source Added - ID: " . $lastid); return "10"; }
    }


    public static function editSource($data) {
        global $database;
        $database->update("app_servers_logsources", [
            "serverid" => $data['serverid'],
            "name" => $data['name'],
            "path_glob" => $data['path_glob'],
            "mode" => $data['mode'],
            "include_regex" => $data['include_regex'],
            "exclude_regex" => $data['exclude_regex'],
            "multiline_pattern" => $data['multiline_pattern'],
            "rate_limit" => $data['rate_limit'],
            "sample_rate" => $data['sample_rate'],
            "labels" => serialize(self::parseLabelsInput($data['labels'] ?? '')),
        ], [ "id" => $data['id'] ]);
        logSystem("Log Source Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteSource($id) {
        global $database;
        $database->delete("app_servers_logsources", [ "id" => $id ]);
        $database->delete("app_servers_logs_agentstate", [ "sourceid" => $id ]);
        logSystem("Log Source Deleted - ID: " . $id);
        return "30";
    }


    // "key=value" per line (or comma-separated) -> assoc array. Unparseable/blank
    // lines are dropped rather than erroring - this only feeds Loki static labels,
    // never anything security-sensitive, so a lenient parse is the right tradeoff.
    private static function parseLabelsInput($raw) {
        $out = [];
        $parts = preg_split('/[\r\n,]+/', (string)$raw);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || strpos($part, '=') === false) continue;
            [$key, $value] = explode('=', $part, 2);
            $key = trim($key);
            if ($key === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) continue;
            $out[$key] = trim($value);
        }
        return $out;
    }

    public static function formatLabelsForInput($serialized) {
        $arr = @unserialize($serialized);
        if (!is_array($arr)) return '';
        $lines = [];
        foreach ($arr as $k => $v) $lines[] = "$k=$v";
        return implode("\n", $lines);
    }


    // ----------------------------------------------------------------------------------------------
    // LOG ALERTS
    // ----------------------------------------------------------------------------------------------

    public static function addAlert($data) {
        global $database;
        $lastid = $database->insert("app_servers_logs_alerts", [
            "serverid" => $data['serverid'],
            "sourceid" => $data['sourceid'] ?? 0,
            "type" => $data['type'],
            "pattern" => $data['pattern'] ?? '',
            "comparison" => $data['comparison'] ?? '>=',
            "comparison_limit" => $data['comparison_limit'] ?? '1',
            "window_minutes" => max(1, (int)($data['window_minutes'] ?? 5)),
            "occurrences" => 1,
            "contacts" => serialize($data['contacts'] ?? []),
            "repeats" => $data['repeats'] ?? 0,
            "last_evaluated" => null,
            "status" => isset($data['status']) ? $data['status'] : 1,
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Log Alert Added - ID: " . $lastid); return "10"; }
    }


    public static function editAlert($data) {
        global $database;
        $database->update("app_servers_logs_alerts", [
            "serverid" => $data['serverid'],
            "sourceid" => $data['sourceid'] ?? 0,
            "type" => $data['type'],
            "pattern" => $data['pattern'] ?? '',
            "comparison" => $data['comparison'] ?? '>=',
            "comparison_limit" => $data['comparison_limit'] ?? '1',
            "window_minutes" => max(1, (int)($data['window_minutes'] ?? 5)),
            "contacts" => serialize($data['contacts'] ?? []),
            "repeats" => $data['repeats'] ?? 0,
            "status" => isset($data['status']) ? $data['status'] : 1,
        ], [ "id" => $data['id'] ]);
        logSystem("Log Alert Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAlert($id) {
        global $database;
        $database->delete("app_servers_logs_alerts", [ "id" => $id ]);
        $database->delete("app_servers_logs_incidents", [ "alertid" => $id ]);
        logSystem("Log Alert Deleted - ID: " . $id);
        return "30";
    }


    public static function markIncident($id) {
        global $database;
        $database->update("app_servers_logs_incidents", [
            "status" => 1,
            "end_time" => date('Y-m-d H:i:s'),
        ], [ "id" => $id ]);
        logSystem("Log Incident Marked Resolved - ID: " . $id);
        return "20";
    }


    public static function editComment($data) {
        global $database;
        $database->update("app_servers_logs_incidents", [
            "comment" => $data['comment'],
            "ignore" => $data['ignore'],
        ], [ "id" => $data['id'] ]);
        logSystem("Log Incident Comment Updated - ID: " . $data['id']);
        return "20";
    }


    // ----------------------------------------------------------------------------------------------
    // ALERT EVALUATION
    // ----------------------------------------------------------------------------------------------

    private static function scopedQuery($alert) {
        $q = new LogQuery();
        $q->serverids = [(int)$alert['serverid']];
        if ((int)$alert['sourceid'] > 0) $q->sourceids = [(int)$alert['sourceid']];
        $q->to = date('c');
        $q->from = date('c', time() - max(1, (int)$alert['window_minutes']) * 60);
        return $q;
    }

    // Sample line for incident context. Best-effort - alerting must not fail
    // just because grabbing an example line failed.
    private static function sampleLine($store, LogQuery $q) {
        try {
            $sq = clone $q;
            $sq->limit = 1;
            $sq->direction = 'backward';
            $result = $store->search($sq);
            return !empty($result->rows) ? $result->rows[0]['message'] : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public static function processAll() {
        global $database;
        $alerts = getTableFiltered("app_servers_logs_alerts", "status", 1);
        $count = 0;
        $store = getLogStore();

        foreach ($alerts as $alert) {

            // Stagger: skip if this rule's window hasn't elapsed since it was last checked -
            // never one Loki query per rule per minute.
            if (!empty($alert['last_evaluated'])) {
                $elapsed = time() - strtotime($alert['last_evaluated']);
                if ($elapsed < max(1, (int)$alert['window_minutes']) * 60) continue;
            }

            $occurred = false;
            $value = '';
            $sampleLine = '';

            try {
                $q = self::scopedQuery($alert);

                if ($alert['type'] === 'matchcount') {
                    $q->text = $alert['pattern'];
                    $cnt = $store->countMatches($q);
                    $occurred = compare($cnt, $alert['comparison_limit'], $alert['comparison']);
                    $value = (string)$cnt;
                    if ($occurred) $sampleLine = self::sampleLine($store, $q);
                }

                elseif ($alert['type'] === 'levelcount') {
                    $levels = array_values(array_filter(array_map(function($lvl) { return strtolower(trim($lvl)); }, explode(',', $alert['pattern'] ?: 'error,crit'))));
                    $q->levels = $levels;
                    $cnt = $store->countMatches($q);
                    $occurred = compare($cnt, $alert['comparison_limit'], $alert['comparison']);
                    $value = (string)$cnt;
                    if ($occurred) $sampleLine = self::sampleLine($store, $q);
                }

                elseif ($alert['type'] === 'absence') {
                    $cnt = $store->countMatches($q);
                    $occurred = ($cnt == 0);
                    $value = (string)$cnt;
                }

                elseif ($alert['type'] === 'ratespike') {
                    $windowSeconds = max(1, (int)$alert['window_minutes']) * 60;
                    $currentCount = $store->countMatches($q);

                    // Baseline from the prior 24h, bucketed at the rule's own window size -
                    // a direct Loki query rather than app_servers_logs_rollup, which doesn't
                    // exist yet (that table lands in Phase 3). Revisit once rollups ship.
                    $baselineQ = self::scopedQuery($alert);
                    $baselineQ->to = date('c', time() - $windowSeconds);
                    $baselineQ->from = date('c', time() - $windowSeconds - (24 * 3600));
                    $buckets = $store->histogram($baselineQ, $windowSeconds);
                    $totals = array_map(function($b) { return $b['total']; }, $buckets);
                    $baseline = count($totals) > 0 ? array_sum($totals) / count($totals) : 0.0;

                    $multiplier = (float)($alert['comparison_limit'] !== '' ? $alert['comparison_limit'] : 3);
                    $occurred = ($baseline > 0) ? ($currentCount > $baseline * $multiplier) : ($currentCount > 0);
                    $value = $currentCount . ' (baseline ' . round($baseline, 1) . ')';
                    if ($occurred) $sampleLine = self::sampleLine($store, $q);
                }

            } catch (Throwable $e) {
                // Backend unreachable or query failed - skip this cycle, do not open/close
                // incidents on incomplete information. last_evaluated is intentionally NOT
                // updated here so it retries next cron run rather than waiting a full window.
                continue;
            }

            $database->update("app_servers_logs_alerts", [ "last_evaluated" => date('Y-m-d H:i:s') ], [ "id" => $alert['id'] ]);

            if ($occurred) {
                if (!$database->has("app_servers_logs_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ])) {
                    $database->insert("app_servers_logs_incidents", [
                        "serverid" => $alert['serverid'],
                        "alertid" => $alert['id'],
                        "type" => $alert['type'],
                        "comparison" => $alert['comparison'],
                        "comparison_limit" => $alert['comparison_limit'],
                        "value" => $value,
                        "sample_line" => $sampleLine,
                        "start_time" => date('Y-m-d H:i:s'),
                        "end_time" => "0000-00-00 00:00:00",
                        "repeats" => $alert['repeats'],
                        "last_notification" => date('Y-m-d H:i:s'),
                        "comment" => "",
                        "ignore" => 0,
                        "status" => ($alert['type'] === 'absence') ? 3 : 2,
                    ]);
                    App::send_alert_notif('open', 'log', $alert['id']);
                }
            } else {
                if ($database->has("app_servers_logs_incidents", [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ])) {
                    $database->update("app_servers_logs_incidents", [ 'status' => 1, 'end_time' => date('Y-m-d H:i:s') ], [ "AND" => [ 'alertid' => $alert['id'], 'status[!]' => 1 ] ]);
                    App::send_alert_notif('close', 'log', $alert['id']);
                }
            }

            $count++;
        }

        return $count;
    }


    public static function sendUnresolvedNotifications() {
        global $database;
        $count = 0;
        $now = strtotime("now");

        $unresolved_incidents = getTableFiltered("app_servers_logs_incidents", "status[!]", "1", "repeats[!]", "0");

        foreach ($unresolved_incidents as $unresolved_incident) {
            $last_notification = strtotime($unresolved_incident['last_notification']);
            $difference = $now - $last_notification;

            $required_difference = 60 * $unresolved_incident['repeats'];

            if ($difference >= $required_difference) {
                $database->update("app_servers_logs_incidents", [ "last_notification" => date('Y-m-d H:i:s') ], ['id' => $unresolved_incident['id']]);
                App::send_alert_notif('unresolved', 'log', $unresolved_incident['alertid']);
                $count++;
            }
        }

        return $count;
    }


}
