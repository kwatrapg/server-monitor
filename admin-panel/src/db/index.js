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

module.exports = db;
