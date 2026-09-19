const crypto = require('crypto');
const config = require('../config');

// Generates a human-readable key like SNTR-XXXX-XXXX-XXXX-XXXX from CSPRNG bytes.
function generateLicenseKey() {
  const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I to avoid confusion
  const groups = [];
  for (let g = 0; g < 4; g++) {
    let group = '';
    const bytes = crypto.randomBytes(4);
    for (let i = 0; i < 4; i++) {
      group += alphabet[bytes[i] % alphabet.length];
    }
    groups.push(group);
  }
  return `SNTR-${groups.join('-')}`;
}

function signPayload(payload) {
  const json = JSON.stringify(payload);
  const signature = crypto
    .createHmac('sha256', config.licenseHmacSecret)
    .update(json)
    .digest('hex');
  return { ...payload, signature };
}

module.exports = { generateLicenseKey, signPayload };
