const session = require('express-session');
const db = require('./index');

db.exec(`
  CREATE TABLE IF NOT EXISTS sessions (
    sid TEXT PRIMARY KEY,
    sess TEXT NOT NULL,
    expires_at INTEGER NOT NULL
  );
  CREATE INDEX IF NOT EXISTS idx_sessions_expires ON sessions(expires_at);
`);

const getStmt = db.prepare('SELECT sess, expires_at FROM sessions WHERE sid = ?');
const upsertStmt = db.prepare(`
  INSERT INTO sessions (sid, sess, expires_at) VALUES (@sid, @sess, @expiresAt)
  ON CONFLICT(sid) DO UPDATE SET sess = excluded.sess, expires_at = excluded.expires_at
`);
const destroyStmt = db.prepare('DELETE FROM sessions WHERE sid = ?');
const clearStmt = db.prepare('DELETE FROM sessions');
const purgeExpiredStmt = db.prepare('DELETE FROM sessions WHERE expires_at < ?');

// Minimal express-session store backed by the same better-sqlite3 database
// used for app data, so the admin panel has no other native/runtime dependency.
class SqliteSessionStore extends session.Store {
  constructor() {
    super();
    this.purgeInterval = setInterval(() => {
      purgeExpiredStmt.run(Date.now());
    }, 60 * 60 * 1000);
    this.purgeInterval.unref();
  }

  get(sid, callback) {
    try {
      const row = getStmt.get(sid);
      if (!row || row.expires_at < Date.now()) return callback(null, null);
      callback(null, JSON.parse(row.sess));
    } catch (err) {
      callback(err);
    }
  }

  set(sid, sessionData, callback) {
    try {
      const maxAge = sessionData.cookie && sessionData.cookie.maxAge ? sessionData.cookie.maxAge : 8 * 60 * 60 * 1000;
      upsertStmt.run({ sid, sess: JSON.stringify(sessionData), expiresAt: Date.now() + maxAge });
      callback(null);
    } catch (err) {
      callback(err);
    }
  }

  destroy(sid, callback) {
    try {
      destroyStmt.run(sid);
      callback(null);
    } catch (err) {
      callback(err);
    }
  }

  touch(sid, sessionData, callback) {
    this.set(sid, sessionData, callback);
  }

  clear(callback) {
    try {
      clearStmt.run();
      callback(null);
    } catch (err) {
      callback(err);
    }
  }
}

module.exports = SqliteSessionStore;
