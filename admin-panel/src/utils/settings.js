const db = require('../db');

function getSetting(name, fallback = '') {
  const row = db.prepare('SELECT value FROM app_settings WHERE name = ?').get(name);
  return row ? row.value : fallback;
}

function setSetting(name, value) {
  db.prepare(
    `INSERT INTO app_settings (name, value) VALUES (?, ?)
     ON CONFLICT(name) DO UPDATE SET value = excluded.value`
  ).run(name, String(value));
}

module.exports = { getSetting, setSetting };
