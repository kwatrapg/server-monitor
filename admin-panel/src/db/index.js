const fs = require('fs');
const path = require('path');
const Database = require('better-sqlite3');
const config = require('../config');

fs.mkdirSync(path.dirname(config.dbPath), { recursive: true });

const db = new Database(config.dbPath);
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

const schema = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf8');
db.exec(schema);

// CREATE TABLE IF NOT EXISTS above only helps fresh databases. For a database
// created before a column existed, add it here — guarded so re-running on an
// already-migrated database is a no-op.
function addColumnIfMissing(table, column, ddl) {
  const existing = db.prepare(`PRAGMA table_info(${table})`).all().map((c) => c.name);
  if (!existing.includes(column)) {
    db.exec(`ALTER TABLE ${table} ADD COLUMN ${ddl}`);
  }
}

addColumnIfMissing('admin_users', 'paused', 'paused INTEGER NOT NULL DEFAULT 0');
addColumnIfMissing('saas_licenses', 'amount_cents', 'amount_cents INTEGER');
addColumnIfMissing('saas_licenses', 'payment_status', "payment_status TEXT NOT NULL DEFAULT 'n/a'");
addColumnIfMissing('saas_licenses', 'payment_method', "payment_method TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'password_hash', 'password_hash TEXT');
addColumnIfMissing('saas_customers', 'billing_name', "billing_name TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'billing_address', "billing_address TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'billing_city', "billing_city TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'billing_state', "billing_state TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'billing_zip', "billing_zip TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'billing_country', "billing_country TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'wants_gst_invoice', 'wants_gst_invoice INTEGER NOT NULL DEFAULT 0');
addColumnIfMissing('saas_customers', 'gst_number', "gst_number TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'gst_address', "gst_address TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_customers', 'gst_state', "gst_state TEXT NOT NULL DEFAULT ''");
addColumnIfMissing('saas_plans', 'is_demo', 'is_demo INTEGER NOT NULL DEFAULT 0');
addColumnIfMissing('saas_plans', 'trial_days', 'trial_days INTEGER');

// Seed a default 15-day Demo plan once, if one doesn't already exist. Admins
// can edit its limits/trial length afterward like any other plan.
const hasDemoPlan = db.prepare('SELECT 1 FROM saas_plans WHERE is_demo = 1').get();
if (!hasDemoPlan) {
  db.prepare(
    `INSERT INTO saas_plans (name, slug, description, price_cents, currency, billing_interval, max_servers, max_websites, max_checks, is_active, is_demo, trial_days)
     VALUES ('Demo', 'demo', '15-day free trial', 0, 'USD', 'lifetime', 1, 1, 5, 1, 1, 15)`
  ).run();
}

addColumnIfMissing('saas_plans', 'gst_type', "gst_type TEXT NOT NULL DEFAULT 'exclusive'");

module.exports = db;
