# Sentruo

Self-hosted infrastructure monitoring — servers, websites, TCP/UDP/ICMP/DNS checks,
domains, SSL certificates, alerting (email / SMS / Pushover / Pushbullet / Twitter),
and public status pages.

PHP 8.3 · MariaDB 10.6+ · Apache or nginx+php-fpm.

## Quick start (Docker)

```bash
cp .env.example .env          # set APP_KEY, DB_*, AGENT_HMAC_SECRET, SMTP_*
docker compose up -d --build
docker compose exec app php bin/install.php
```

## Quick start (bare metal)

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env          # edit
php bin/install.php           # schema + seed + migrations + first admin
# point a vhost (deploy/) at this directory, enable TLS, add the cron (deploy/systemd/)
```

Full instructions: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Security

This release is a hardened fork. See [`SECURITY.md`](SECURITY.md) and the full
VAPT remediation map in [`docs/REMEDIATION.md`](docs/REMEDIATION.md).

- Secrets live in `.env` (or real env vars), never in the DB or `config.php`.
- argon2id password hashing, CSRF tokens, login rate-limiting, session regeneration.
- SSRF allow-listing on every outbound probe; signed agent ingest (HMAC + replay window).
- Allow-list routing (no dynamic includes); strict security headers / CSP.
- `bin/ci-checks.sh` is a static gate — run it in CI.

## Layout

```
index.php  agent.php  callback.php   web entrypoints
includes/                            bootstrap, security primitives, classes, controllers
template/                            views + front-end assets
crons/cron.php                       monitoring cycle (CLI / CRON_TOKEN)
database/{schema,seed}.sql
database/migrations/                  ordered, idempotent; run via bin/migrate.php
bin/{install,migrate}.php  bin/ci-checks.sh
deploy/                               apache / nginx / systemd templates
docs/                                DEPLOYMENT.md, REMEDIATION.md, LOG_MONITORING_SPEC.md
```

## Configuration

Runtime settings (branding, retention, timezone, notification templates) are in
**Settings** inside the app. Infrastructure settings (DB, keys, SMTP, agent policy)
are environment-only — see `.env.example`.

## License

Proprietary. Bundled third-party components retain their own licenses
(`vendor/`, `template/assets/`).



 bin/install.php is the only way to create the first admin account (refuses to run if any user already exists, password chosen interactively, never logged).
- .env.example already has secure defaults (APP_ENV=production, AGENT_REQUIRE_SIGNATURE=true,
