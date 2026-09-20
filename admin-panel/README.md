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
# Paste into SESSION_SECRET, LICENSE_HMAC_SECRET and ENCRYPTION_KEY in .env
# (use a different generated value for each of the three)

npm run create-admin -- --username=admin --email=you@example.com --password='a-strong-password-here'

npm start
# Admin panel now listening on http://localhost:4001
```

Credentials are never stored in this repo. To reset a password, sign in as
another admin and edit the account under Users. If you're locked out
entirely (no working admin account), update `password_hash` directly in
`data/admin.sqlite`'s `admin_users` table with a fresh bcrypt hash
(`node -e "console.log(require('bcryptjs').hashSync('new-password', 12))"`).

## What it manages

- **Customers** — name, email, company, notes.
- **Plans** — price, billing interval, and usage limits (max servers /
  websites / checks) that map to the main monitor app's plan tiers.
- **Licenses** — a generated key (`SNTR-XXXX-XXXX-XXXX-XXXX`) tied to a
  customer and plan, with status (active/suspended/revoked/expired),
  optional domain binding, an activation limit, an expiry date, and the
  **amount actually charged** for that license (independent of the plan's
  list price, e.g. for a discount or custom deal).
- **Admin Users** (`/admin-users`) — the staff accounts that can log into
  *this* admin panel (separate from SaaS customers). Add, edit, delete, or
  **pause** an account. A paused account is immediately blocked from
  logging in or continuing an existing session, but its history isn't
  deleted. You can't pause or delete the last remaining active admin, and
  you can't delete the account you're currently signed in as.
- **Settings** (`/settings`):
  - **General** — site name, support email, default currency.
  - **Payment Gateways** (`/settings/payment-gateways`) — store credentials
    for Razorpay, Stripe, PayPal or PayU and mark one or more as
    enabled/test/live. Secrets are AES-256-GCM encrypted at rest with
    `ENCRYPTION_KEY` and never re-displayed in the form (blank = keep the
    saved value). **This saves and encrypts gateway credentials only** — it
    does not implement a checkout flow or webhook handling; wiring an
    actual payment provider's SDK/checkout/webhooks up to license
    creation or renewal is a separate integration.
  - **Invoice** (`/settings/invoice`) — company name, address, state, GST
    number and GST rate shown on customer invoices, plus a free-form field
    for anything else (PAN, bank details, terms).

## Customer portal (`/portal`)

A separate, public-facing self-service area for your SaaS customers —
completely different session/cookie (`sentruo.customer.sid`) from the admin
session, so someone can be signed into both the admin panel and their own
customer account in the same browser without either logging the other out.
Not behind `TRUSTED_ADMIN_IPS` (customers sign up from anywhere).

- **Sign up / log in** (`/portal/signup`, `/portal/login`) — a customer
  creates their own account with an email + password. Signing up with an
  email an admin already added as a `saas_customers` record (no password
  set yet) claims that existing record rather than erroring.
- **Plans → checkout → license** (`/portal/plans`, `/portal/checkout/:id`) —
  the customer picks an active plan and completes the order. **No live
  payment gateway is wired up to this checkout yet** (see Payment Gateways
  above) — completing the order issues the license immediately, recorded
  with `payment_status = paid`, `payment_method = manual`. Swap the handler
  in `src/routes/portal.js` for a real gateway confirmation (e.g. verifying
  a Razorpay payment signature) once you're ready to test that against real
  gateway credentials.
- **Dashboard** (`/portal/dashboard`) — every license the customer owns:
  key, plan limits, activations used vs. the license's activation limit,
  amount paid, payment status, issue/expiry dates, last verified time.
- **Billing** (`/portal/billing`) — billing name/address/city/state/zip/
  country, and an "I need a GST invoice" checkbox that reveals GST number,
  GST address and GST state fields. **The GST state must match the billing
  state** — enforced server-side, not just in the UI.

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
- Rotating `ENCRYPTION_KEY` makes every already-saved payment gateway
  credential undecryptable (it'll silently read back as empty) — re-enter
  them after rotating.

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
