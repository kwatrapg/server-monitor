const crypto = require('crypto');
const config = require('../config');

// SHA-256 of the configured key gives a fixed 32-byte AES-256 key regardless
// of what ENCRYPTION_KEY looks like (hex string, passphrase, etc.).
const key = crypto.createHash('sha256').update(config.encryptionKey).digest();

function encryptSecret(plaintext) {
  if (!plaintext) return '';
  const iv = crypto.randomBytes(12);
  const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
  const ciphertext = Buffer.concat([cipher.update(String(plaintext), 'utf8'), cipher.final()]);
  const tag = cipher.getAuthTag();
  return Buffer.concat([iv, tag, ciphertext]).toString('base64');
}

function decryptSecret(stored) {
  if (!stored) return '';
  try {
    const raw = Buffer.from(stored, 'base64');
    const iv = raw.subarray(0, 12);
    const tag = raw.subarray(12, 28);
    const ciphertext = raw.subarray(28);
    const decipher = crypto.createDecipheriv('aes-256-gcm', key, iv);
    decipher.setAuthTag(tag);
    return Buffer.concat([decipher.update(ciphertext), decipher.final()]).toString('utf8');
  } catch (err) {
    return '';
  }
}

module.exports = { encryptSecret, decryptSecret };
