# Changelog

## 2.0.0 — Security remediation & rebrand

Hardened fork of the "nMon" / "Server Monitor" script. Addresses every finding in
the 2026-08-15 VAPT report plus additional issues found during remediation.
Full map: `docs/REMEDIATION.md`.

### Security — Critical/High
- **Unauthenticated LFI → code execution eliminated** — allow-list routing; no
  include path derives from request input; auth runs before all controllers.
- **Legacy installer removed** (`install-old/`); new CLI installer refuses re-install.
- **No default admin / stored session IDs** — forced password change for legacy-hash
  accounts; all sessions invalidated on upgrade.
- **Secrets moved to `.env`** — DB creds, `APP_KEY`, SMTP, provider keys; `config.php`
  is now a secrets-free shim; web server denies `.sql`/`.env`/`config.php`/dumps.
- **argon2id password hashing** with transparent upgrade of legacy SHA-1.
- **CSPRNG tokens**; password-reset keys hashed at rest, 30-min TTL, single-use.
- **CSRF protection**, session regeneration, hardened session cookies.
- **Login rate-limiting** + account lockout; uniform responses (no user enumeration).

### Security — Medium/Low
- Security headers + CSP (`frame-ancestors 'none'`) from PHP and the web server.
- Output encoding pass across `json.php` and templates (stored/reflected XSS).
- SSRF allow-listing on website / check / SSL / geodata probes; cron execution guard.
- Signed agent ingest (HMAC + timestamp + replay window); TLS verification restored.
- `json.php` now authorizes every datasource; profile edit locked to the current user.
- `callback.php` uses a per-check CSPRNG key instead of the guessable host string.
- Dead/broken file-upload feature removed.
- jQuery 2.2.3 kept + official htmlPrefilter XSS shim (CVE-2020-11022/11023);
  full jQuery 3 upgrade is Backlog. `pear/net_dns2` → `mikepultz/netdns2`;
  `dg/twitter-php` 3.6 → 4.1; `geerlingguy/ping` 1.1.2 → 1.2.1. `composer audit` clean.
- `loki/`, `assets-org/`, and dead `*-bk.php` backups removed from the tree.

### Product
- Rebranded to **Sentruo**; version string de-hardcoded (`SM_APP_VERSION`).
- Agent installs to `/opt/sentruo`, runs as `sentruo-agent`.

### Tooling
- `bin/install.php`, `bin/migrate.php` (+ `database/migrations/`, `core_migrations`).
- `bin/ci-checks.sh` static security gate.
- `Dockerfile`, `compose.yaml`, `deploy/{apache,nginx,systemd}/` templates.
- `docs/DEPLOYMENT.md` runbook, `docs/REMEDIATION.md` finding map.

### Upgrade notes
- Requires PHP ≥ 8.1 with `pdo_mysql`, `curl`, `openssl`, `mbstring`.
- Create `.env` (see `.env.example`), then `php bin/migrate.php`.
- All users are logged out; legacy-hash accounts must set a new password on next login.
- Re-run the agent installer on monitored hosts to adopt signed ingest.
- Recompile `lang/fr.mo` with `msgfmt` after pulling.
