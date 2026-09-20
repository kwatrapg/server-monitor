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
  // 64 hex chars (32 bytes), used only to encrypt payment gateway secrets at
  // rest (AES-256-GCM). Generate the same way as SESSION_SECRET.
  encryptionKey: required('ENCRYPTION_KEY', 'dev-only-insecure-encryption-key-not-32-bytes'),
  trustedAdminIps: (process.env.TRUSTED_ADMIN_IPS || '')
    .split(',')
    .map((ip) => ip.trim())
    .filter(Boolean),
  trustProxy: process.env.TRUST_PROXY === '1',
};
