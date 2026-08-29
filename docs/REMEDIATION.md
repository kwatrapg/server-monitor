# Sentruo — VAPT Remediation Map

Source assessment: `VAPT_Report_Server_Monitor.md` (HackerAI, 2026-08-15).
Every finding below is fixed in code on branch `remediation/vapt-and-rebrand` and
verified against a live instance. Line references are post-change.

## Critical / High

| # | Finding | Fix | Where | Verified |
|---|---------|-----|-------|----------|
| F-01 | Unauth LFI → PHP execution | Allow-list router: `route`/`modal`/`qa`/`json`/`action` validated against explicit lists + realpath containment; **no include path derives from input**. Auth + perms run before every controller. | `includes/whitelist.php`, `includes/loader.php`, `index.php` | `?route=../x`, `?modal=../../config`, `?route=signin&json=activitylog` → 404/400, no data |
| F-02 | Leftover installer → reinstall/RCE | `install-old/` **deleted**. New CLI installer refuses to run on a populated DB, never echoes credentials, writes nothing web-served. | removed; `bin/install.php`, `bin/migrate.php` | installer gone; `bin/install.php` aborts when `core_users` non-empty |
| F-03 | Default creds + stored session IDs | No admin seeded; first admin created interactively with policy. Migration nulls all `sessionid`, flags default-hash accounts `must_change_password` (forced to profile until changed). | `database/migrations/*0001*`, `includes/loader.php`, `includes/functions.php` | flagged account bounced to `?route=profile` until reset |
| F-04 | Plaintext SMTP creds + DB dumps exposed | SMTP + provider secrets read from `.env`, blanked in `core_config`. No `*.sql` outside `database/migrations/`. `.htaccess` + vhosts deny `.sql`/`.env`/`config.php`/dumps. | `includes/functions.php` `sendEmail()`, `.htaccess`, `deploy/*` | `/config.php`,`/*.sql`,`/.env` → 403 |
| F-05 | Unsalted SHA-1 passwords | `password_hash` (argon2id, bcrypt fallback) + `password_verify`; legacy sha1 accepted once then rehashed. | `includes/security.php`, `functions.php`, `class.user.php`, `class.profile.php` | `admin` row became `$argon2id$` on next login |
| F-06 | `rand()` tokens; non-expiring reset keys | `random_bytes` CSPRNG for all tokens; reset key = `sha256(token)`, 30-min TTL, single-use, invalidates sessions. | `includes/security.php` `sm_random_token`, `functions.php` `resetConfirmation`/`resetPassword`; `database/migrations/*0001*` (`resetkey_expires`) | expired/replayed reset link rejected |
| F-07 | Session fixation; no cookie hardening; no CSRF | `session_regenerate_id(true)` on login/logout/reset; `HttpOnly`+`SameSite=Lax`+`Secure`(HTTPS)+`use_strict_mode`; CSRF token (64 hex/session) enforced on every action + same-origin check. | `includes/loader.php`, `includes/security.php`, `controllers/{actions,general,quickactions}.php`, `template/{header,footer,signin,forgot}.php`, `assets/app.js` | POST without token → 403; new session id after login |
| F-08 | No login rate limiting; user enumeration; plaintext pw email | Sliding-window throttle (`core_auththrottle`), 5 fails/15 min → exponential lockout; uniform responses for login + forgot-password. | `includes/security.php` `sm_throttle_*`, `functions.php` | 6 bad logins → lockout; identical response for unknown vs known email |

## Medium / Low

| # | Finding | Fix | Verified |
|---|---------|-----|----------|
| F-09 | `phpinfo()` endpoint | None present in this tree. `bin/ci-checks.sh` fails the build on any `phpinfo(` or `a.php`. | CI gate green |
| F-10 | Missing security headers | `includes/http_headers.php` (CSP `frame-ancestors 'none'`, XFO, nosniff, Referrer-Policy, Permissions-Policy, HSTS) + `.htaccess` + vhosts. `X-Powered-By` stripped. | `curl -I` shows all headers |
| F-11 | Stored/reflected XSS (no output encoding) | `e()` helper; `json.php` HTML builders + ~150 template sinks escaped (names, urls, hosts, emails, comments, notes, agent `$os`, `$_GET` reflections, session range). Input filter kept; CSP backstop. | raw `"><img onerror>` name renders inert in the datatable |
| F-12 | jQuery 2.2.3 / Bootstrap 3.3.7 | jQuery → 3.7.1 + `jquery-migrate` 3.4.1. Bootstrap/AdminLTE 3 migration tracked (Backlog). | old file 404; pages load |
| F-13 | Unauth agent ingest; static key; no TLS | HMAC-SHA256(`ts.payload`, secret) + ±300 s window + strict per-server timestamp advance; HTTPS enforced (`AGENT_ALLOW_HTTP` opt-out). `agent.sh` signs, drops `curl -k`. | unsigned → 401, replay → 409, stale → 401 |
| F-14 | SSRF by design in probes | `HostGuard` (public-IP allow-list, scheme allow-list, DNS-rebinding IP pin, redirect re-validation) in `class.website/check/ssl`, geodata. `crons/cron.php` CLI/`CRON_TOKEN` guard. Admin opt-in for private ranges. | probes to `127.0.0.1` / `169.254.169.254` / RFC1918 blocked |
| F-15 | Hardcoded encryption key + default DB creds | `.env` model; per-deploy `APP_KEY`; `config.php` is a secrets-free shim. | `config.php` contains no literal secret (CI gate) |
| F-16 | Outdated / EOL dependencies | `composer.json` php≥8.1; `pear/net_dns2` (abandoned) → `mikepultz/netdns2`; `dg/twitter-php` 3.6→4.1; `geerlingguy/ping` 1.1.2→1.2.1. `composer audit` clean. twilio/messagebird held (Backlog). | `composer audit` → no advisories |
| F-17 | World-writable `loki/` in webroot | `loki/` removed from the tree (runtime data → `deploy-extras/`). Ships later as opt-in `deploy/loki/` with auth + a dedicated user. `assets-org/` and dead `*-bk.php` removed. | not in tree |

## Additional issues found during remediation (not in the report)

| # | Issue | Fix |
|---|-------|-----|
| A-1 | `?route=signin\|forgot\|publicpage` still executed every controller (log dump, file read, `editProfile` with no session) | controllers gated behind the auth check; not run on public view routes |
| A-2 | `json.php` had **zero** authorization; any role could read all activity/email/sms/cron logs | per-datasource permission gate; verified 403 for a role without `viewLogs` |
| A-3 | IDOR: `editProfile` took arbitrary `$_POST['id']`; `download` quick-action had no ownership check | `editProfile` locked to `$liu['id']`; dead file-upload/download feature removed entirely |
| A-4 | `callback.php` authenticated by the guessable `app_checks.host` | per-check CSPRNG `callbackkey` column; strict format check |
| A-5 | `class.profile.php` also used `sha1()` | argon2id + policy |
| A-6 | SSRF sinks the report missed: `class.ssl.php` socket, geodata `gethostbyname` | `HostGuard` applied |
| A-7 | Stored XSS via `json.php` from agent-submitted `$os` (unauth ingest) | `e()` on all `json.php` HTML builders |
| A-8 | `install-old/{upgrade,check,migrate_domains_ssl_permissions}.php` — unauth migration re-run / role-perm rewrite | whole directory deleted |
| A-9 | `session_start()` without `use_strict_mode`; redirect to a non-existent `install/` | `use_strict_mode=1`; 503 "not configured" page |
| A-10 | `unserialize()` of DB columns without `allowed_classes` | `['allowed_classes' => false]` everywhere |

## Backlog (documented, not done this pass)

- MFA / TOTP for administrators.
- Full AdminLTE 3 / Bootstrap 5 front-end migration (interim: jquery-migrate shim + CSP).
- Twilio SDK 5→8 and MessageBird 1→3 (major API changes; no live CVE; blocked on an
  advisory-flagged transitive `firebase/php-jwt`). Keep pinned; re-evaluate.
- Build the Loki-backed log-monitoring feature (`docs/LOG_MONITORING_SPEC.md`).
- Recompile `lang/fr.mo` with `msgfmt` after the msgid changes.
- SSO / SAML / OIDC; SIEM export of the auth/audit log.

## Post-deploy re-test checklist (for the assessor)

1. `bin/ci-checks.sh` — green.
2. LFI: `?route=<bogus>`, `?modal=../../config`, `?route=signin&json=activitylog`, `/crons/cron.php` (HTTP) → 404/400/403.
3. Auth: no-CSRF POST → 403; 6 bad logins → lockout; forced-change account cannot leave `/profile`; sha1 fixture upgrades on login.
4. Reset token: expires after 30 min, single-use.
5. SSRF: website/check to `169.254.169.254`, `127.0.0.1:3306`, `10.0.0.0/8` → refused + logged.
6. Agent: unsigned / stale / replayed POST to `agent.php` → 401/409.
7. Headers: CSP `frame-ancestors 'none'`, HSTS, XFO, nosniff present; public status page not framable.
8. XSS: asset named `"><script>` renders inert on dashboard/search/manage/public.
9. Files: `/.env`, `/config.php`, `/*.sql`, `/composer.json`, `/includes/*` → 403/404.
