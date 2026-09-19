# Sentruo Admin Panel

Independent SaaS control panel for managing customers, subscription plans and
license keys for the Sentruo monitor product. This is a standalone Node.js
service — it does not share a codebase, database, or session store with the
main PHP monitor app, and can be deployed on the same host or a separate one.

## Stack

- Node.js + Express
- SQLite (via `better-sqlite3`) — zero external DB dependency, single file
- EJS server-rendered views (no client-side framework/build step)
- Session auth (bcrypt password hashing, CSRF tokens, rate-limited login)
- Helmet security headers

## Setup

```bash
cd admin-panel
npm install
cp .env.example .env
# Generate secrets:
node -e "console.log(require('crypto').randomBytes(48).toString('hex'))"
# Paste into SESSION_SECRET and LICENSE_HMAC_SECRET in .env (use different values for each)

npm run create-admin -- --username=admin --email=you@example.com --password='a-strong-password-here'

npm start
# Admin panel now listening on http://localhost:4001
```

## What it manages

- **Customers** — name, email, company, notes.
- **Plans** — price, billing interval, and usage limits (max servers /
  websites / checks) that map to the main monitor app's plan tiers.
- **Licenses** — a generated key (`SNTR-XXXX-XXXX-XXXX-XXXX`) tied to a
  customer and plan, with status (active/suspended/revoked/expired),
  optional domain binding, an activation limit, and an expiry date.

## License verification API

Any Sentruo install (the main PHP app, or a customer's self-hosted copy) can
check a license against this service:

```
POST /api/v1/license/verify
Content-Type: application/json

{ "license_key": "SNTR-ABCD-1234-EFGH-5678", "domain": "customer-site.com", "fingerprint": "<stable per-install id>" }
```

The response is HMAC-signed with `LICENSE_HMAC_SECRET` (field: `signature`,
computed over the JSON body with that field excluded) so callers can verify
it was issued by this server rather than spoofed. This endpoint is public
and rate-limited (30 requests/min per IP); it does not use the admin session.

## Security notes

- Put this service behind HTTPS (nginx/Apache reverse proxy or a TLS-terminating
  load balancer) — `SESSION_SECRET`-signed cookies are marked `secure` in
  production and won't be sent over plain HTTP.
- Set `TRUSTED_ADMIN_IPS` in `.env` to restrict the admin UI (not the
  `/api` verification endpoint) to known office/VPN IPs if possible.
- The SQLite database and session store live under `data/` — back this up;
  losing it loses all customers/plans/licenses. It's excluded from git via
  `.gitignore`.
- Rotate `LICENSE_HMAC_SECRET` only if you also update every caller that
  verifies signatures — rotating it invalidates old cached verifications.

## Deploying independently

```bash
docker build -t sentruo-admin-panel .
docker run -d --name sentruo-admin \
  -p 4001:4001 \
  -v sentruo_admin_data:/app/data \
  --env-file .env \
  sentruo-admin-panel
```

Put it behind the same reverse proxy as the main app (e.g. a new `server {}`
block in nginx or a `<VirtualHost>` in Apache) on a subdomain such as
`admin.yourdomain.com`, or run it on a completely separate host — nothing
here depends on the PHP app being reachable.
