# Server Log Monitoring — Implementation Spec

> **Status:** Approved design, not yet implemented.
> **Audience:** An engineer or AI session implementing this feature in `server-monitor`.
> **Goal:** Collect application/system logs from monitored Linux servers, display them live and filterable in the app, and raise alerts from them — at ~100 servers — without degrading app or database performance.

This document is self-contained. Everything in "Current Architecture" and "Codebase Conventions" has been **verified against the live codebase and database**; trust it rather than re-deriving it.

---

## 1. Current architecture (verified)

`server-monitor` today collects **metrics only**. The pipeline:

```
install.sh <serverkey> <gateway>
   └─ writes /opt/server-monitor/{serverkey,gateway}
   └─ downloads /opt/server-monitor/agent.sh
   └─ adds root crontab:  * * * * * bash /opt/server-monitor/agent.sh   (60s)

agent.sh  (every 60s)
   └─ collects metrics into a "{tag}value{/tag}" pseudo-XML string
   └─ echo "data=$POST" | curl -m 50 -k -s -d @- "$GATEWAY"     → <gateway>/agent.php

agent.php
   └─ looks up app_servers by serverkey  (unauthenticated otherwise)
   └─ gzcompress($_POST['data'], 9)
   └─ INSERT one blob row into app_servers_history
   └─ Server::cleanHistory($lastHistoryId)  — strips static fields from the PREVIOUS row
```

Key facts:

| Fact | Value |
|---|---|
| Agent runtime | Pure `bash` + `cron`, no daemon |
| Cadence | 60s (cron minimum granularity) |
| Transport | Outbound HTTPS POST only — no inbound ports on monitored hosts |
| Auth | 64-char `serverkey`, `UNIQUE KEY` on `app_servers.serverkey` |
| Payload parsing | `Server::extractData($key, $data)` — string offsets on `{tag}…{/tag}` |
| Storage | `app_servers_history.data` = `blob`, gzcompressed |
| Retention | `App::purgeMonitoringHistory()` — `DELETE … WHERE timestamp <=` |
| Config keys | `history_retention` = 90, `log_retention` = 90 (in `core_config`) |
| Guaranteed deps | `cron`, `curl`, `gzip`, `unzip`, **`perl`** — `install.sh` installs all of these on every supported distro |
| Stack | Apache (prefork) + PHP 8.1 + MariaDB 10.6 |

**Measured baseline (live DB):** ~2.7 KB compressed for the latest full snapshot, **~420 bytes/min/server** for cleaned rows ≈ **600 KB/server/day**.

---

## 2. Why raw logs must not go into MySQL

| | Per server/day | ×100 servers/day | ×30 days |
|---|---|---|---|
| Metrics (today, compressed) | ~0.6 MB | ~60 MB | ~1.8 GB |
| Logs @ 1k lines/min, 200 B/line ("Everything") | ~288 MB raw / ~40 MB gz | ~28.8 GB raw / ~4 GB gz | **~120 GB** |

Logs are **100–1000× metrics volume**. Three failure modes if forced into the current design:

1. **Write load** — a blob row per interval per server becomes thousands of rows/sec.
2. **Retention** — `DELETE … WHERE timestamp <=` over a table that size causes lock and IO storms.
3. **Search** — `LIKE '%pattern%'` is a full table scan; one user search stalls the whole app.

**Decision:** raw logs live in **Loki**. MySQL holds only the small control plane (sources, policies, rules, incidents, agent health). PHP gets a **storage abstraction layer** so other backends can be added later.

---

## 3. Target architecture

```
┌────────────── MONITORED SERVER (×100) ───────────────┐
│  agent.sh       → metrics, 60s cron   → agent.php    │  (UNCHANGED)
│  Grafana Alloy  → logs, streaming     → Loki         │  (NEW, bulk path)
│    config pulled from app; per-source policy applied │
└──────────────────────────────────────────────────────┘
                         │ push (per-server token, via reverse proxy)
                         ▼
                   ┌──────────┐
                   │   LOKI   │  chunks + retention + compactor
                   └──────────┘
                         ▲ HTTP query (LogQL) — built server-side only
                         │
┌───────────────── server-monitor APP ─────────────────┐
│  LogStore interface ── LokiLogStore / MysqlLogStore  │
│  MySQL: log sources, policies, alert rules,          │
│         incidents, agent health, users, groups       │
│  UI: log explorer, live tail, per-source config      │
│  Alerting: reuses App::send_alert_notif() fan-out    │
└──────────────────────────────────────────────────────┘
```

**The invariant that makes this scale:** the log firehose never passes through PHP or MySQL. PHP issues *queries* and stores *rules and results* only.

---

## 4. Codebase conventions to follow

Match these exactly — the app has a consistent house style and generated code must not look foreign.

### Database access
medoo, via `global $database`. Helpers in `includes/functions.php`:

| Helper | Purpose |
|---|---|
| `getTable($table, $columns, $sortby, $sortway)` | whole table |
| `getTableFiltered($table, $col1, $val1, $col2, $val2, $columns, $sortby, $sortway)` | filtered select |
| `getRowById($table, $id)` / `getSingleValue($table, $column, $id)` | single row / value |
| `countTableFiltered($table, $col, $val)` | counts |
| `compare($what, $with, $how)` | threshold comparison used by all alert evaluators |
| `getConfigValue($name)` | reads `core_config` |
| `logSystem($description)` | writes `core_activitylog` |
| `checkGroup($groupid)` / `checkGroupRedirect($groupid)` | group-permission gates |

### Class autoloading
`appClassAutoload()` maps class `Foo` → `includes/classes/class.foo.php` (lowercased). Just create the file; **no registration needed**.

### The four wiring points
Every feature touches the same four dispatch files:

| File | Role |
|---|---|
| `includes/controllers/data.php` | `if ($route == "…") { … }` — page data + `isAuthorized()` |
| `includes/controllers/json.php` | `case "…":` — DataTables server-side AJAX |
| `includes/controllers/actions.php` | `case "…":` — POST form actions → class methods |
| `includes/controllers/modals.php` | `case "…":` — preloads data for modal templates |

### Entity module pattern
Established shape: `app_X` + `app_X_alerts` + `app_X_incidents` + `app_X_history`, with a class exposing
`add / edit / delete / addAlert / editAlert / deleteAlert / markIncident / editComment / checkAll / processAll / sendUnresolvedNotifications`.

**Read these as working references before writing code:**
- `includes/classes/class.website.php` — the canonical original
- `includes/classes/class.domain.php` and `class.ssl.php` — most recent, cleanest examples of adding a new module

### Alerting fan-out
`App::send_alert_notif($action, $assettype, $alertid)` in `includes/classes/class.app.php` handles
email / SMS / Pushbullet / Pushover / Twitter + `app_alertlog` audit rows.
Extend it with a new `$assettype` branch and typestrings — see the existing `domain` / `ssl` branches for the exact pattern. **Do not write new notification code.**

### Cron
`crons/cron.php` runs the fixed sequence: check → process → unresolved-notify → purge. Add log steps in the same order and style.

### UI
- Nav: `template/header.php` (sidebar `<li>` items, gated by `in_array("perm", $perms)`)
- Pages: `template/pages/<route>.php`; detail pages at `template/pages/<route>/manage.php`
- Modals: `template/modals/<group>/<action>.php`
- Permissions checkboxes: `template/pages/system/roles/add.php` **and** `edit.php` (keep both in sync)

### Migrations
- Schema → `install-old/sql/*.sql`
- Permissions are **PHP-serialized arrays** in `core_roles.perms`; they cannot be safely patched with raw SQL. Write a PHP migration — copy the approach in `install-old/migrate_domains_ssl_permissions.php`, which grants new perms by mirroring each role's existing equivalent access level and is idempotent.

---

## 5. Control-plane schema (MySQL)

Bounded and small — megabytes, not gigabytes. Deliver as `install-old/sql/db_server_logs.sql`.

```sql
-- What to collect, and how, per server
CREATE TABLE `app_servers_logsources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `path_glob` varchar(512) NOT NULL,
  `mode` varchar(16) NOT NULL DEFAULT 'errors_only',  -- disabled|errors_only|filtered|everything
  `include_regex` text NOT NULL,
  `exclude_regex` text NOT NULL,
  `multiline_pattern` varchar(255) NOT NULL DEFAULT '',
  `rate_limit` int(11) NOT NULL DEFAULT 1000,          -- max lines/min, 0 = unlimited
  `sample_rate` int(11) NOT NULL DEFAULT 1,            -- ship 1-in-N, 1 = all
  `labels` text NOT NULL,                              -- extra Loki labels, serialized
  `status` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Alert rules (mirrors app_servers_alerts + pattern/window)
CREATE TABLE `app_servers_logs_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `sourceid` int(11) NOT NULL DEFAULT 0,               -- 0 = all sources on server
  `type` varchar(25) NOT NULL,                         -- matchcount|levelcount|absence|ratespike
  `pattern` text NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `window_minutes` int(11) NOT NULL DEFAULT 5,
  `occurrences` int(10) NOT NULL DEFAULT 1,
  `contacts` text NOT NULL,                            -- serialized contact ids
  `repeats` int(5) NOT NULL DEFAULT 0,
  `last_evaluated` datetime DEFAULT NULL,              -- staggering, see §8
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Incidents (same columns/semantics as app_servers_incidents so UI + notifications carry over)
CREATE TABLE `app_servers_logs_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `alertid` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `comparison` varchar(25) NOT NULL,
  `comparison_limit` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `sample_line` text NOT NULL,                         -- example matching line for context
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `repeats` int(5) NOT NULL DEFAULT 0,
  `last_notification` datetime NOT NULL,
  `comment` text NOT NULL,
  `ignore` tinyint(1) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`serverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Agent/shipper health. UPDATEs not INSERTs: ~500 rows at 100 servers x 5 sources.
CREATE TABLE `app_servers_logs_agentstate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` int(11) NOT NULL,
  `sourceid` int(11) NOT NULL,
  `last_seen` datetime DEFAULT NULL,
  `lines_shipped` bigint(20) NOT NULL DEFAULT 0,
  `lines_dropped` bigint(20) NOT NULL DEFAULT 0,
  `bytes_shipped` bigint(20) NOT NULL DEFAULT 0,
  `last_error` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `server_source` (`serverid`,`sourceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

Add `core_config` rows: `log_backend` (`loki`|`mysql`), `loki_url`, `loki_read_token`, `log_live_poll_seconds` (default 3).
Add `app_servers.logs_token varchar(64)` — per-server push credential.

---

## 6. Storage abstraction layer

New file `includes/classes/class.logstore.php`.

```php
interface LogStore {
    public function search(LogQuery $q): LogResult;          // paginated filtered search
    public function tail(LogQuery $q, string $cursor): LogResult; // incremental, live view
    public function countMatches(LogQuery $q): int;          // drives alert evaluation
    public function sources(int $serverid): array;           // discovered streams/labels
    public function health(): array;                         // backend reachable? ingest lag?
}
```

`LogQuery` is a **structured value object** — never a raw query string:

```php
class LogQuery {
    public array  $serverids = [];   // ALWAYS group-scoped before use (see Gotchas)
    public array  $sourceids = [];
    public array  $levels    = [];   // debug|info|notice|warn|error|crit
    public string $text      = '';   // plain substring, escaped by the backend
    public string $from;             // ISO8601
    public string $to;
    public int    $limit     = 500;
    public string $direction = 'backward'; // backward=search, forward=tail
}
```

`LogResult` carries `rows[]` (`ts`, `serverid`, `sourceid`, `level`, `message`, `repeat_count`), `cursor`, `hasMore`, `stats`.

Implementations:
- **`LokiLogStore`** — HTTP to `/loki/api/v1/query_range`; builds LogQL from `LogQuery`, escaping all user text. Use `curl` (already a dependency; see `Website::checkAll()` for existing curl usage in this codebase).
- **`MysqlLogStore`** — fallback for small installs so the product stays installable without Loki. Its line table **must** use `BIGINT` ids and **daily `RANGE` partitioning with `DROP PARTITION` retention**.

Resolve via a `getLogStore()` factory in `includes/functions.php`, switching on `getConfigValue('log_backend')`.

---

## 7. Collection: Alloy + per-source policy

### Shipper: Grafana Alloy (not a hand-written tailer)

Alloy already solves file tailing with persisted offsets, log rotation and truncation, multiline stack traces, relabeling, batching, retry/backoff and TLS. These are subtle and bug-prone to reimplement. **The app's job is to generate Alloy's config, not to reinvent shipping.**

*Fallback:* `install.sh` guarantees **Perl** on every supported distro (verified — it installs/checks Perl in all ten distro branches). A minimal Perl shipper is a viable degraded option where a binary cannot be installed. It is not the default.

### Collection modes

| Mode | Behaviour |
|---|---|
| **Disabled** | Not collected at all. |
| **Errors Only** *(default)* | Built-in pattern, case-insensitive: `ERROR`, `CRITICAL`, `FATAL`, `PANIC`, `EMERG`, `ALERT`, `Exception`, `Traceback`, `WARN`/`WARNING` |
| **Filtered** | User-supplied `include_regex` + `exclude_regex`. |
| **Everything** | Full stream (still rate-capped and sampled). |

Applied **at the edge**, for every mode:

- **Deduplication** — normalize the line (strip timestamps, numbers, UUIDs, IPs), hash it, collapse repeats within the batch window into one entry carrying `repeat_count`.
- **Rate limiting** — per-source max lines/min. On breach, emit a synthetic marker line:
  `[server-monitor] rate limit exceeded for <source>, dropped N lines`.
  **Never drop silently** — an invisible gap destroys trust in the tool.
- **Sampling** — optional 1-in-N for noisy sources, tagged with `sample_rate` so counts can be extrapolated honestly.

### Config distribution

- New endpoint `agentconfig.php?serverkey=…` returns the generated Alloy config plus an **ETag/hash**.
- Agent-side wrapper fetches on start and every N minutes; **reloads only when the hash changes**.
- `install.sh` gains an **optional, idempotent** Alloy install step. It still takes only `<serverkey> <gateway>` — Loki URL and push token are *pulled from the app*, never baked into installer arguments.

---

## 8. Viewing and live tail

### Pages
| Path | Purpose |
|---|---|
| `template/pages/logs.php` | Global log explorer: server / source / level / time-range / text filters, results table, **Live toggle** |
| `template/pages/logs/sources.php` | Manage sources and policies per server |
| Server detail page | New **Logs** tab in `template/pages/servers/manage-*.php`, scoped to that server |
| `template/modals/logsources/{add,edit,delete}.php` | Source CRUD |
| `template/modals/logalerts/{add,edit,delete,markResolved,editComment}.php` | Rule CRUD + incident actions |

### Live mechanism — cursor polling, NOT SSE/WebSocket

Under Apache **prefork** + PHP, every held-open stream occupies a worker process, and PHP session locking serializes other requests from the same user. At 100 servers with multiple viewers that is a self-inflicted outage. Use polling:

- Endpoint `?json=logtail&serverid=…&sourceid=…&level=…&q=…&after=<cursor>` returns only rows newer than the cursor, plus the next cursor.
- JS polls every ~3s (`log_live_poll_seconds`) while Live is on.
- **Back off to ~10s when no new data** arrives.
- **Pause entirely when `document.hidden`** — forgotten background tabs must generate zero load.
- Client-side **ring buffer (~2000 lines)**, dropping oldest, so long sessions can't exhaust browser memory.
- **Pause-on-scroll-up** so reading history isn't yanked away by incoming lines.

### Interaction with existing autorefresh

The app has a full-page countdown reload driven by `$liu['autorefresh']` (whitelist `$autorefresh_pages` in `data.php`, countdown in `template/footer.php`).

**While Live tail is active it must be suppressed** — a full page reload would destroy scroll position and the line buffer. The Live toggle owns refresh; the user's autorefresh preference still governs the page when Live is off.

---

## 9. Alerting

Rule types on `app_servers_logs_alerts`:

| Type | Fires when |
|---|---|
| `matchcount` | N matches of `pattern` within `window_minutes` |
| `levelcount` | N lines at ERROR/CRITICAL within `window_minutes` |
| `absence` | **No** lines from a source in `window_minutes` — catches dead agent / stopped service |
| `ratespike` | Rate exceeds X× rolling baseline |

New `includes/classes/class.log.php` with `Log::processAll()`, called from `crons/cron.php` in the same check → process → notify sequence as `Website` / `Check` / `Server` / `Domain` / `Ssl`. It calls `LogStore->countMatches()`, opens/closes `app_servers_logs_incidents`, and fires
`App::send_alert_notif('open'|'close'|'unresolved', 'log', $alertid)`.

Extend `App::send_alert_notif()` with a `log` branch plus typestrings — the identical extension already done there for `domain`/`ssl`. This inherits **with no new notification code**: contacts, all notification channels, `app_alertlog` audit rows, incident open/close/unresolved-repeat semantics, and the dashboard/header incident widgets.

**Scale:** do not run one Loki query per rule per minute across hundreds of rules. Use `last_evaluated` to stagger and skip rules whose window hasn't elapsed.
*Phase 5 optimisation:* generate **Loki ruler** rule files from the DB and have Loki evaluate and POST to a `logalert.php` webhook — moving evaluation out of PHP entirely.

---

## 10. Gotchas

These will cause real bugs if missed. All verified against the live system.

1. **Permission name collision.** `viewLogs` and `viewAlertLogs` **already exist** (system activity log / alert log). Server-log permissions must be named **`viewServerLogs`**, **`manageLogSources`**, **`editLogAlert`**.
2. **Never accept raw user LogQL.** Build queries server-side from `LogQuery`. Raw pass-through allows cross-tenant data access and trivially expensive query DoS.
3. **Always group-scope.** Inject the caller's permitted `serverid`s from `$liu_groups` / `checkGroup()` into every query. Verify by tampering with request parameters, not just by hiding UI.
4. **Never expose Loki directly.** Put it behind a reverse proxy validating the per-server push token (`app_servers.logs_token`); the app uses a separate read credential. Consider `X-Scope-OrgID` multi-tenancy per customer.
5. **No SSE/WebSocket on Apache prefork** (worker exhaustion + session locking). If ever attempted, `session_write_close()` before the stream loop.
6. **MySQL log tables:** `BIGINT` ids (INT overflows at 2.1B rows) and **`DROP PARTITION`** retention, never `DELETE … WHERE timestamp <=`.
7. **`logSystem()` requires `$_SERVER['REMOTE_ADDR']`** — it inserts it non-null. CLI scripts calling class `add()`/`edit()` methods must set it or the insert throws.
8. **`install.sh` log step must be idempotent** and must never break existing metric collection if it is skipped or fails. `install.sh` deletes itself on completion and rewrites the root crontab — tread carefully.
9. **Keep `roles/add.php` and `roles/edit.php` in sync** — they are two separate files with duplicated permission checkbox blocks.
10. **`core_roles.perms` is a PHP-serialized array.** Never patch it with string SQL; byte-length prefixes must match. Use a PHP migration.

---

## 11. Phased delivery

Each phase is independently shippable.

### Phase 1 — Foundation
Deploy Loki + reverse proxy. Implement `LogStore` interface, `LogQuery`/`LogResult`, `LokiLogStore`, `getLogStore()` factory. Create the four control-plane tables + new `core_config` rows + `app_servers.logs_token`. Add the three permissions and the PHP permission migration.
**Accept when:** `LogStore->health()` reports the backend up, and `search()` returns known seeded lines.

### Phase 2 — Collection
Extend `install.sh` with the idempotent Alloy step. Build `agentconfig.php` (config generation + ETag). Build the per-source policy UI (modes, regex, rate limit, sampling) and `app_servers_logsources` CRUD.
**Accept when:** one non-production server ships logs; switching modes Disabled → Errors Only → Filtered → Everything visibly changes what arrives; **metrics collection is unaffected**.

### Phase 3 — Viewing
Log explorer page, filters, live tail with cursor polling, server Logs tab, agent-health display from `app_servers_logs_agentstate`.
**Accept when:** tailing a file shows lines in the UI within ~3–5s; polling backs off when idle and stops when the tab is hidden; full-page autorefresh does not fire while Live is on.

### Phase 4 — Alerting
`class.log.php` with rule evaluation, `Log::processAll()`, incidents, `send_alert_notif('log')` wiring, cron integration, rule UI + modals.
**Accept when:** a `matchcount` rule opens an incident, notifies a test contact through the existing channels, and closes when the condition clears; stopping the agent fires the `absence` rule.

### Phase 5 — Hardening
Loki ruler + `logalert.php` webhook evaluation, retention tuning, ingest backpressure, `MysqlLogStore` fallback.

---

## 12. Verification

1. **Backend** — point `log_backend=loki` at a test Loki; confirm `health()` up and `search()` returns seeded lines.
2. **Collection** — install on one non-production server; verify mode switching changes what arrives; confirm `app_servers_history` still receives metrics (no regression).
3. **Policy controls** — flood a test log; confirm rate-limit marker lines appear (gaps visible, never silent), dedup collapses repeats with `repeat_count`, and rotation/truncation neither duplicates nor loses lines.
4. **Live view** — tail a file while watching the UI; confirm ~3–5s latency, idle backoff, hidden-tab pause, and that autorefresh does not reload the page while Live is on.
5. **Filtering and isolation** — verify level/source/text/time filters; then log in as a user whose group excludes a server and confirm its logs are unreachable **including by tampering with request parameters**.
6. **Alerting** — trigger `matchcount`, confirm incident + notification + auto-close; stop the agent and confirm `absence` fires.
7. **Load (the core acceptance test)** — simulate ~100 servers' ingest rate; confirm MySQL size and CPU stay flat (control-plane writes only) and app page-load times are unchanged. This is the "performance not hampered" proof.
