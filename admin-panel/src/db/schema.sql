CREATE TABLE IF NOT EXISTS admin_users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'admin',
  paused INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  last_login_at TEXT
);

CREATE TABLE IF NOT EXISTS saas_plans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  description TEXT NOT NULL DEFAULT '',
  price_cents INTEGER NOT NULL DEFAULT 0,
  currency TEXT NOT NULL DEFAULT 'USD',
  billing_interval TEXT NOT NULL DEFAULT 'monthly', -- monthly | yearly | lifetime
  max_servers INTEGER NOT NULL DEFAULT 1,
  max_websites INTEGER NOT NULL DEFAULT 1,
  max_checks INTEGER NOT NULL DEFAULT 10,
  max_domains INTEGER NOT NULL DEFAULT 1,
  max_ssl INTEGER NOT NULL DEFAULT 1,
  is_active INTEGER NOT NULL DEFAULT 1,
  is_demo INTEGER NOT NULL DEFAULT 0,
  trial_days INTEGER, -- only meaningful when is_demo = 1; license expiry is set to approval time + trial_days
  gst_type TEXT NOT NULL DEFAULT 'exclusive', -- exclusive (GST added on top of price) | inclusive (price already includes GST)
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS saas_customers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  company TEXT NOT NULL DEFAULT '',
  notes TEXT NOT NULL DEFAULT '',
  password_hash TEXT, -- NULL until the customer sets up self-service portal access
  billing_name TEXT NOT NULL DEFAULT '',
  billing_address TEXT NOT NULL DEFAULT '',
  billing_city TEXT NOT NULL DEFAULT '',
  billing_state TEXT NOT NULL DEFAULT '',
  billing_zip TEXT NOT NULL DEFAULT '',
  billing_country TEXT NOT NULL DEFAULT '',
  wants_gst_invoice INTEGER NOT NULL DEFAULT 0,
  gst_number TEXT NOT NULL DEFAULT '',
  gst_address TEXT NOT NULL DEFAULT '',
  gst_state TEXT NOT NULL DEFAULT '', -- must equal billing_state when wants_gst_invoice is set (enforced in code)
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS saas_licenses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  license_key TEXT NOT NULL UNIQUE,
  customer_id INTEGER NOT NULL REFERENCES saas_customers(id) ON DELETE CASCADE,
  plan_id INTEGER NOT NULL REFERENCES saas_plans(id) ON DELETE RESTRICT,
  status TEXT NOT NULL DEFAULT 'active', -- active | suspended | revoked | expired | pending_approval | rejected
  domain TEXT NOT NULL DEFAULT '',
  activation_limit INTEGER NOT NULL DEFAULT 1,
  amount_cents INTEGER, -- amount actually charged for this license (may differ from the plan's list price)
  payment_status TEXT NOT NULL DEFAULT 'n/a', -- n/a (admin-issued) | paid | pending
  payment_method TEXT NOT NULL DEFAULT '', -- e.g. razorpay, manual
  issued_at TEXT NOT NULL DEFAULT (datetime('now')),
  expires_at TEXT,
  last_verified_at TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS saas_license_activations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  license_id INTEGER NOT NULL REFERENCES saas_licenses(id) ON DELETE CASCADE,
  fingerprint TEXT NOT NULL,
  domain TEXT NOT NULL DEFAULT '',
  ip TEXT NOT NULL DEFAULT '',
  activated_at TEXT NOT NULL DEFAULT (datetime('now')),
  last_seen_at TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE(license_id, fingerprint)
);

CREATE TABLE IF NOT EXISTS audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  admin_user_id INTEGER REFERENCES admin_users(id) ON DELETE SET NULL,
  action TEXT NOT NULL,
  entity TEXT NOT NULL,
  entity_id INTEGER,
  meta TEXT NOT NULL DEFAULT '{}',
  ip TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS app_settings (
  name TEXT PRIMARY KEY,
  value TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS payment_gateways (
  provider TEXT PRIMARY KEY, -- razorpay | stripe | paypal | payu
  enabled INTEGER NOT NULL DEFAULT 0,
  mode TEXT NOT NULL DEFAULT 'test', -- test | live
  config_encrypted TEXT NOT NULL DEFAULT '', -- JSON {key_id, key_secret, ...}, AES-256-GCM encrypted
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Invoices are immutable snapshots at the time of purchase — customer
-- billing/GST details and company invoice details are copied in at
-- creation time rather than joined live, so editing a customer's address
-- or the company's invoice settings later never rewrites past invoices.
CREATE TABLE IF NOT EXISTS invoices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_number TEXT NOT NULL UNIQUE,
  customer_id INTEGER NOT NULL REFERENCES saas_customers(id) ON DELETE RESTRICT,
  license_id INTEGER REFERENCES saas_licenses(id) ON DELETE SET NULL,
  plan_name TEXT NOT NULL DEFAULT '',

  customer_name TEXT NOT NULL DEFAULT '',
  customer_email TEXT NOT NULL DEFAULT '',
  billing_address TEXT NOT NULL DEFAULT '',
  billing_city TEXT NOT NULL DEFAULT '',
  billing_state TEXT NOT NULL DEFAULT '',
  billing_country TEXT NOT NULL DEFAULT '',
  gst_number TEXT NOT NULL DEFAULT '',
  gst_address TEXT NOT NULL DEFAULT '',
  gst_state TEXT NOT NULL DEFAULT '',

  company_name TEXT NOT NULL DEFAULT '',
  company_address TEXT NOT NULL DEFAULT '',
  company_state TEXT NOT NULL DEFAULT '',
  company_gst_number TEXT NOT NULL DEFAULT '',

  currency TEXT NOT NULL DEFAULT 'USD',
  gst_type TEXT NOT NULL DEFAULT 'exclusive', -- inclusive | exclusive, snapshot of the plan at purchase time
  tax_type TEXT NOT NULL DEFAULT 'none', -- none | cgst_sgst | igst
  cgst_rate REAL NOT NULL DEFAULT 0,
  sgst_rate REAL NOT NULL DEFAULT 0,
  igst_rate REAL NOT NULL DEFAULT 0,
  subtotal_cents INTEGER NOT NULL DEFAULT 0, -- amount before tax
  cgst_cents INTEGER NOT NULL DEFAULT 0,
  sgst_cents INTEGER NOT NULL DEFAULT 0,
  igst_cents INTEGER NOT NULL DEFAULT 0,
  total_cents INTEGER NOT NULL DEFAULT 0,

  status TEXT NOT NULL DEFAULT 'paid', -- paid | void | refunded
  notes TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_invoices_customer ON invoices(customer_id);
CREATE INDEX IF NOT EXISTS idx_licenses_customer ON saas_licenses(customer_id);
CREATE INDEX IF NOT EXISTS idx_licenses_plan ON saas_licenses(plan_id);
CREATE INDEX IF NOT EXISTS idx_licenses_status ON saas_licenses(status);
CREATE INDEX IF NOT EXISTS idx_activations_license ON saas_license_activations(license_id);
