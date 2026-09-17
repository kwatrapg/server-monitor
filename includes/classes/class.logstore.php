<?php
// ----------------------------------------------------------------------------------------------
// LOG STORAGE ABSTRACTION
//
// Raw log lines are NEVER read from or written to MySQL. This file is the only place
// the app talks to the log backend (Loki today). LogQuery is a structured value object -
// callers never pass raw LogQL/query strings.
//
// This file declares four classes (LogStore, LogQuery, LogResult, LokiLogStore).
// appClassAutoload() only maps the exact filename "class.logstore.php" to the class
// name "LogStore" - the other three ride along once this file is loaded, but are not
// individually autoloadable by name. Always obtain a store via getLogStore() in
// includes/functions.php, which require_once's this file explicitly rather than
// relying on autoload order.
// ----------------------------------------------------------------------------------------------

interface LogStore {
    public function search(LogQuery $q);          // paginated filtered search -> LogResult
    public function tail(LogQuery $q, $cursor);    // incremental, live view -> LogResult
    public function countMatches(LogQuery $q);     // drives alert evaluation -> int
    public function sources($serverid);            // discovered sourceids for a server -> array
    public function health();                      // backend reachable? ingest lag? -> array
    public function histogram(LogQuery $q, $stepSeconds); // time-bucketed counts per level -> array
}


class LogQuery {
    public $serverids = [];    // REQUIRED, non-empty - group-scoped serverids, enforced by the store
    public $sourceids = [];
    public $levels    = [];    // subset of: debug|info|notice|warn|error|crit
    public $text      = '';    // plain substring, escaped by the backend - never raw LogQL
    public $from      = '';    // ISO8601, empty = backend default
    public $to        = '';    // ISO8601, empty = now
    public $limit     = 500;
    public $direction = 'backward'; // backward=search (newest first), forward=tail
}


class LogResult {
    public $rows    = [];   // each: ts, ts_nanos, serverid, sourceid, level, message, repeat_count
    public $cursor  = null; // opaque string, pass back into tail() to continue
    public $hasMore = false;
    public $stats   = [];
}


class LokiLogStore implements LogStore {

    const ALLOWED_LEVELS = ['debug', 'info', 'notice', 'warn', 'error', 'crit'];

    private $baseUrl;
    private $readToken;

    public function __construct($baseUrl, $readToken = '') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->readToken = $readToken;
    }


    public function search(LogQuery $q) {
        $this->requireServerScope($q);

        $params = [
            'query'     => $this->buildLogQL($q),
            'limit'     => max(1, min((int)$q->limit, 5000)),
            'direction' => ($q->direction === 'forward') ? 'forward' : 'backward',
        ];
        if ($q->from !== '') $params['start'] = $this->toNanos($q->from);
        if ($q->to   !== '') $params['end']   = $this->toNanos($q->to);

        $data = $this->httpGet('/loki/api/v1/query_range', $params);
        return $this->parseStreams($data, $params['limit'], $params['direction'] === 'forward');
    }


    public function tail(LogQuery $q, $cursor) {
        $this->requireServerScope($q);

        $start = ($cursor !== '' && $cursor !== null) ? ((string)((int)$cursor + 1)) : (string)($this->nowNanos() - (30 * 1000000000));

        $params = [
            'query'     => $this->buildLogQL($q),
            'limit'     => max(1, min((int)$q->limit, 1000)),
            'direction' => 'forward',
            'start'     => $start,
            'end'       => (string)$this->nowNanos(),
        ];

        $data = $this->httpGet('/loki/api/v1/query_range', $params);
        return $this->parseStreams($data, $params['limit'], true);
    }


    public function countMatches(LogQuery $q) {
        $this->requireServerScope($q);

        $fromTs = $q->from !== '' ? strtotime($q->from) : (time() - 300);
        $toTs   = $q->to   !== '' ? strtotime($q->to)   : time();
        if ($fromTs === false || $toTs === false) throw new InvalidArgumentException('Invalid LogQuery from/to');
        $rangeSeconds = max(1, $toTs - $fromTs);

        $expr = 'sum(count_over_time(' . $this->buildLogQL($q) . '[' . $rangeSeconds . 's]))';

        $data = $this->httpGet('/loki/api/v1/query', [
            'query' => $expr,
            'time'  => (string)($toTs * 1000000000),
        ]);

        $result = $data['data']['result'] ?? [];
        if (empty($result)) return 0;
        return (int) round((float) ($result[0]['value'][1] ?? 0));
    }


    public function histogram(LogQuery $q, $stepSeconds) {
        $this->requireServerScope($q);
        $stepSeconds = max(60, (int)$stepSeconds);

        $fromTs = $q->from !== '' ? strtotime($q->from) : (time() - 86400);
        $toTs   = $q->to   !== '' ? strtotime($q->to)   : time();
        if ($fromTs === false || $toTs === false) throw new InvalidArgumentException('Invalid LogQuery from/to');

        // Range-vector duration matches step so buckets are adjacent, not overlapping.
        $expr = 'sum by (level) (count_over_time(' . $this->buildLogQL($q) . '[' . $stepSeconds . 's]))';

        $data = $this->httpGet('/loki/api/v1/query_range', [
            'query' => $expr,
            'start' => (string)($fromTs * 1000000000),
            'end'   => (string)($toTs * 1000000000),
            'step'  => (string)$stepSeconds,
        ]);

        $buckets = [];
        foreach (($data['data']['result'] ?? []) as $series) {
            $level = $series['metric']['level'] ?? 'unknown';
            foreach (($series['values'] ?? []) as $point) {
                $ts = (int)$point[0];
                $count = (int) round((float)$point[1]);
                if ($count === 0) continue;
                $buckets[$ts][$level] = ($buckets[$ts][$level] ?? 0) + $count;
            }
        }
        ksort($buckets);

        $out = [];
        foreach ($buckets as $ts => $levels) {
            $out[] = [
                'ts'     => date('Y-m-d H:i:s', $ts),
                'levels' => $levels,
                'total'  => array_sum($levels),
            ];
        }
        return $out;
    }


    public function sources($serverid) {
        $serverid = (int)$serverid;
        if ($serverid <= 0) throw new InvalidArgumentException('sources() requires a positive serverid');

        $data = $this->httpGet('/loki/api/v1/series', [
            'match[]' => '{serverid="' . $serverid . '"}',
            'start'   => (string)($this->nowNanos() - (24 * 3600 * 1000000000)),
            'end'     => (string)$this->nowNanos(),
        ]);

        $sourceids = [];
        foreach (($data['data'] ?? []) as $set) {
            if (isset($set['sourceid'])) $sourceids[(int)$set['sourceid']] = true;
        }
        return array_keys($sourceids);
    }


    public function health() {
        $start = microtime(true);
        try {
            $headers = [];
            if ($this->readToken !== '') $headers[] = 'Authorization: Bearer ' . $this->readToken;

            curl_setopt_array($ch = curl_init(), [
                CURLOPT_URL => $this->baseUrl . '/ready',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $latency = round((microtime(true) - $start) * 1000, 1);

            if ($errno !== 0) {
                return ['up' => false, 'latency_ms' => $latency, 'error' => $error];
            }
            return [
                'up'         => ($httpCode === 200),
                'latency_ms' => $latency,
                'error'      => ($httpCode === 200) ? null : ('HTTP ' . $httpCode . ': ' . trim((string)$body)),
            ];
        } catch (Throwable $e) {
            return ['up' => false, 'latency_ms' => round((microtime(true) - $start) * 1000, 1), 'error' => $e->getMessage()];
        }
    }


    // ----------------------------------------------------------------------------------------------
    // INTERNALS
    // ----------------------------------------------------------------------------------------------

    private function requireServerScope(LogQuery $q) {
        if (empty($q->serverids)) {
            throw new InvalidArgumentException('LogQuery->serverids must not be empty - callers must group-scope before querying');
        }
    }

    private function buildSelector(LogQuery $q) {
        $labels = [];

        $serverids = array_values(array_unique(array_map('intval', $q->serverids)));
        $labels[] = 'serverid=~"' . implode('|', $serverids) . '"';

        $sourceids = array_values(array_unique(array_map('intval', $q->sourceids)));
        if (!empty($sourceids)) {
            $labels[] = 'sourceid=~"' . implode('|', $sourceids) . '"';
        }

        // Lowercase before validating - levels are always lowercase in this system (Alloy
        // emits them that way), but callers building a LogQuery from free-typed admin input
        // (e.g. an alert rule's pattern field) can't be trusted to match case. Silently
        // dropping an unrecognized level here would remove the filter entirely rather than
        // erroring, so case-normalizing is not optional.
        $normalizedLevels = array_map('strtolower', $q->levels);
        $levels = array_values(array_intersect($normalizedLevels, self::ALLOWED_LEVELS));
        if (!empty($levels)) {
            $labels[] = 'level=~"' . implode('|', $levels) . '"';
        }

        return '{' . implode(',', $labels) . '}';
    }

    private function escapeLine($text) {
        // Loki line filters use double-quoted strings; escape backslash then quote so
        // embedded quotes stay inside the literal instead of breaking out into new LogQL.
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $text);
    }

    private function buildLogQL(LogQuery $q) {
        $query = $this->buildSelector($q);
        if ($q->text !== '') {
            $query .= ' |= "' . $this->escapeLine($q->text) . '"';
        }
        return $query;
    }

    private function toNanos($iso8601) {
        if ($iso8601 === '') return $this->nowNanos();
        $ts = strtotime($iso8601);
        if ($ts === false) throw new InvalidArgumentException('Invalid date: ' . $iso8601);
        return (string)($ts * 1000000000);
    }

    private function nowNanos() {
        return time() * 1000000000;
    }

    private function httpGet($path, array $params) {
        $url = $this->baseUrl . $path . '?' . http_build_query($params);

        $headers = [];
        if ($this->readToken !== '') $headers[] = 'Authorization: Bearer ' . $this->readToken;

        curl_setopt_array($ch = curl_init(), [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new RuntimeException('Loki request failed: ' . $error);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Loki returned HTTP ' . $httpCode . ': ' . substr((string)$body, 0, 300));
        }

        $decoded = json_decode((string)$body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Loki returned invalid JSON');
        }
        return $decoded;
    }

    private function parseStreams(array $data, $limit, $ascending) {
        $result = new LogResult();
        $entries = [];

        foreach (($data['data']['result'] ?? []) as $stream) {
            $labels = $stream['stream'] ?? [];
            foreach (($stream['values'] ?? []) as $value) {
                $tsNanos = (string)$value[0];
                $line = (string)$value[1];
                $entries[] = [
                    'ts_nanos'     => $tsNanos,
                    'ts'           => date('Y-m-d H:i:s', (int)((int)$tsNanos / 1000000000)),
                    'serverid'     => (int)($labels['serverid'] ?? 0),
                    'sourceid'     => (int)($labels['sourceid'] ?? 0),
                    'level'        => $labels['level'] ?? 'unknown',
                    'message'      => $line,
                    'repeat_count' => (int)($labels['repeat_count'] ?? 1),
                ];
            }
        }

        usort($entries, function($a, $b) {
            return (int)$a['ts_nanos'] <=> (int)$b['ts_nanos'];
        });
        if (!$ascending) $entries = array_reverse($entries);

        $result->rows = array_slice($entries, 0, $limit);
        $result->hasMore = count($entries) > $limit;
        $result->stats = $data['data']['stats'] ?? [];

        if (!empty($result->rows)) {
            $last = end($result->rows);
            $result->cursor = $last['ts_nanos'];
        } else {
            $result->cursor = null;
        }

        return $result;
    }

}
