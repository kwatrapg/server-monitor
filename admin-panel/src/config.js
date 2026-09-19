const path = require('path');
require('dotenv').config();

function required(name, fallbackDevValue) {
  const value = process.env[name];
  if (value && value.trim() !== '') return value;
  if (process.env.NODE_ENV !== 'production') return fallbackDevValue;
  throw new Error(`Missing required environment variable: ${name}`);
}

module.exports = {
  env: process.env.NODE_ENV || 'development',
  port: parseInt(process.env.PORT, 10) || 4001,
  dbPath: path.resolve(process.cwd(), process.env.DB_PATH || './data/admin.sqlite'),
  sessionSecret: required('SESSION_SECRET', 'dev-only-insecure-session-secret'),
  licenseHmacSecret: required('LICENSE_HMAC_SECRET', 'dev-only-insecure-hmac-secret'),
  trustedAdminIps: (process.env.TRUSTED_ADMIN_IPS || '')
    .split(',')
    .map((ip) => ip.trim())
    .filter(Boolean),
  trustProxy: process.env.TRUST_PROXY === '1',
};
