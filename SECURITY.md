# Security

## Reporting

Email the maintainers privately with steps to reproduce. Do not open a public issue
for an unpatched vulnerability. Target response: 72 hours.

## Security model

- **Secrets**: `.env` / real environment variables only. `config.php` contains no
  secrets. Keep `.env` outside the web root, `chmod 640`, owned by root, group-readable
  by the PHP user. `APP_KEY` and `AGENT_HMAC_SECRET` are per-deployment.
- **Authentication**: argon2id (bcrypt fallback), transparent upgrade of legacy
  hashes on login. Password policy: ≥12 chars, mixed case + digit. Sliding-window
  rate limit (5 failures / 15 min → exponential lockout) per IP+account.
  `session_regenerate_id()` on every privilege change. Cookies: `HttpOnly`,
  `SameSite=Lax`, `Secure` under HTTPS, `session.use_strict_mode=1`.
- **CSRF**: per-session 64-hex token required on every state-changing request
  (`csrf_token` field or `X-CSRF-Token` header) plus a same-origin check.
- **Authorization**: role permissions checked per action and per JSON datasource;
  group ownership checked on asset reads.
- **Routing**: `route` / `modal` / `qa` / `json` / `action` are allow-listed; no
  include path is derived from request input.
- **Output**: `e()` (HTML-escape) on user/DB/agent strings; input filter retained;
  CSP with `frame-ancestors 'none'` as the backstop.
- **SSRF**: `HostGuard` rejects probe targets that resolve to loopback / private /
  link-local / reserved ranges (opt-in override), enforces an `http(s)` scheme
  allow-list, pins the validated IP, and re-checks every redirect hop.
- **Agent ingest**: per-server CSPRNG key + HMAC-SHA256 over `timestamp.payload`,
  ±300 s window, strictly increasing per-server timestamp (replay → 409),
  HTTPS required (`AGENT_ALLOW_HTTP` opt-out for dev only).
- **Cron**: `crons/cron.php` runs from the CLI, or over HTTP only with `CRON_TOKEN`.

## Hardening checklist

- [ ] TLS enforced; HTTP redirects to HTTPS; HSTS enabled.
- [ ] `.env` outside web root, `0640`, `SENTRUO_ENV_FILE` set.
- [ ] DB user has no `FILE` / `GRANT` / DDL; MariaDB bound to localhost.
- [ ] Previously-exposed credentials rotated (SMTP, DB, any admin password).
- [ ] `bin/ci-checks.sh` green in CI; `composer audit` clean.
- [ ] Cron scheduled every minute (CLI).
- [ ] Outbound egress restricted to 80/443 → public IPs.
- [ ] Off-box encrypted backups; no `.sql` in/near the web root.
- [ ] `AGENT_REQUIRE_SIGNATURE=true`, `AGENT_ALLOW_HTTP=false`.

## Known backlog

See "Backlog" in `docs/REMEDIATION.md` — MFA, AdminLTE 3 / Bootstrap 5, Twilio/MessageBird
major upgrades, SSO, SIEM export.
