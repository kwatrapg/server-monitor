# Sentruo — Production Deployment & Hardening Runbook

Target stack: PHP 8.3 (Apache mod_php **or** nginx + php-fpm), MariaDB 10.6+.
Required PHP extensions: `pdo_mysql`, `curl`, `openssl`, `mbstring`, `json`. Optional: `gd` (avatars).

---

## 1. Provision

```bash
# Debian/Ubuntu example
apt-get install -y php8.3 php8.3-{cli,curl,mysql,mbstring,gd,opcache} \
                   apache2 libapache2-mod-php8.3 mariadb-server whois iputils-ping composer
a2enmod headers rewrite ssl
```

Create a dedicated DB and least-privilege user:

```sql
CREATE DATABASE sentruo CHARACTER SET utf8mb4;
CREATE USER 'sentruo'@'localhost' IDENTIFIED BY '<strong-random>';
GRANT SELECT, INSERT, UPDATE, DELETE ON sentruo.* TO 'sentruo'@'localhost';
-- no FILE, no GRANT OPTION, no DDL beyond what migrations need at install time
FLUSH PRIVILEGES;
```

Bind MariaDB to localhost only (`bind-address = 127.0.0.1` in `50-server.cnf`).

## 2. Get the code

```bash
install -d -o www-data -g www-data /var/www/sentruo
git clone <repo> /var/www/sentruo && cd /var/www/sentruo
composer install --no-dev --optimize-autoloader
```

## 3. Configure secrets (`.env`)

Keep the real `.env` **outside** the web root and point to it:

```bash
install -d -m 0750 -o root -g www-data /etc/sentruo
cp .env.example /etc/sentruo/env
chown root:www-data /etc/sentruo/env && chmod 0640 /etc/sentruo/env
# tell the app where it is:
echo 'SetEnv SENTRUO_ENV_FILE /etc/sentruo/env' >> /etc/apache2/conf-available/sentruo.conf
```

Fill in `/etc/sentruo/env`:

| Key | Notes |
|-----|-------|
| `APP_KEY` | `php -r 'echo bin2hex(random_bytes(32))."\n";'` — 64 hex. Rotating it invalidates HMACs/reset-key hashes. |
| `DB_HOST/PORT/NAME/USER/PASSWORD` | the least-priv user above |
| `AGENT_HMAC_SECRET` | `php -r 'echo bin2hex(random_bytes(32))."\n";'` — same value goes to every agent host as `/opt/sentruo/hmac_secret` |
| `AGENT_REQUIRE_SIGNATURE` | `true` in production |
| `AGENT_ALLOW_HTTP` | `false` — agents must POST over HTTPS |
| `CRON_TOKEN` | only if you trigger cron over HTTP instead of CLI |
| `SMTP_*` | mail credentials — **not** stored in the DB anymore |
| `TRUSTED_PROXIES` | comma-separated proxy IPs; only then is `X-Forwarded-For` trusted |
| `ALLOW_PRIVATE_PROBE_TARGETS` | `false` unless you deliberately monitor RFC1918 hosts |

**Rotate now:** the previously-committed Gmail SMTP password and any DB password
that ever lived in `config.php`.

## 4. Install the database

```bash
sudo -u www-data SENTRUO_ENV_FILE=/etc/sentruo/env php bin/install.php
# creates schema + seed + migrations, then prompts for the first admin (policy-enforced)
```

Upgrades later: `php bin/migrate.php` (idempotent; tracked in `core_migrations`).

## 5. Web server

Use `deploy/apache/sentruo.conf` **or** `deploy/nginx/sentruo.conf` as the starting point.
Both: DocumentRoot = the app root, `AllowOverride All` so the shipped `.htaccess`
deny rules apply, TLS with a redirect from `:80`, HSTS, and PHP disabled under `/uploads`.

Get a certificate (`certbot --apache -d monitor.example.com`) and confirm:

```bash
curl -sI https://monitor.example.com/?route=signin | grep -Ei 'content-security-policy|strict-transport|x-frame|x-content-type'
curl -sI https://monitor.example.com/config.php        # 403
curl -sI https://monitor.example.com/database/schema.sql # 403/404
curl -sI https://monitor.example.com/crons/cron.php      # 403 (no token)
```

Set the canonical URL in **Settings → General → app_url** (`https://monitor.example.com/`).

## 6. Cron (required — drives all checks & alerts)

CLI (preferred): install the systemd units in `deploy/systemd/` —

```bash
cp deploy/systemd/sentruo-cron.* /etc/systemd/system/
systemctl enable --now sentruo-cron.timer
```

or a crontab line: `* * * * * www-data /usr/bin/php /var/www/sentruo/crons/cron.php`.

HTTP fallback only: `curl -fsS "https://monitor.example.com/crons/cron.php?token=$CRON_TOKEN"`.

## 7. Filesystem permissions

```bash
chown -R www-data:www-data /var/www/sentruo
find /var/www/sentruo -type d -exec chmod 750 {} \;
find /var/www/sentruo -type f -exec chmod 640 {} \;
chmod 755 /var/www/sentruo/bin/*.sh
```

## 8. Agent rollout

Server → *Install Agent* modal gives the one-liner. It downloads
`assets/install.sh`, which now: installs to `/opt/sentruo`, runs as `sentruo-agent`,
reads `/opt/sentruo/hmac_secret` (push the value from `AGENT_HMAC_SECRET`),
signs every POST (HMAC + timestamp), and uses TLS verification. Re-run the installer
on existing hosts to pick up the signed protocol.

## 9. Egress hardening (defence in depth for F-14)

The app blocks probes to private ranges at the application layer. Additionally,
restrict the app/cron host's outbound traffic to 80/443 to public IPs
(drop RFC1918, `169.254.0.0/16`, the DB subnet) with nftables/security groups.

## 10. Backups

`mysqldump` on a schedule to an **off-box**, encrypted target (age/gpg). Never leave
`.sql` files in or beside the web root. Test a restore quarterly.

## 11. Docker alternative

`compose.yaml` builds `app` + `cron` + `db`. Provide `.env`, `docker compose up -d --build`,
then `docker compose exec app php bin/install.php`. Still terminate TLS in front
(nginx/Caddy/LB) — the app container binds to `127.0.0.1:8080`.

## 12. Ongoing

- Run `bin/ci-checks.sh` in CI on every change; block merge on failure.
- `composer audit` weekly.
- Review `system/logs` for `Security: request denied`, `Agent ingest rejected`,
  `Check … skipped (blocked target)`, and login-lockout entries.
- Recompile `lang/fr.mo` (`msgfmt lang/fr.po -o lang/fr.mo`) — the build box here lacked gettext.
- Track the Backlog in `docs/REMEDIATION.md`.
