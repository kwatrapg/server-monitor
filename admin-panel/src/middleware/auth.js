const config = require('../config');
const db = require('../db');

function requireAuth(req, res, next) {
  if (!req.session || !req.session.adminUserId) return res.redirect('/login');

  // Re-check on every request (not just at login) so pausing/deleting an
  // admin takes effect immediately, not just after their session expires.
  const user = db.prepare('SELECT id, paused FROM admin_users WHERE id = ?').get(req.session.adminUserId);
  if (!user || user.paused) {
    return req.session.destroy(() => res.redirect('/login'));
  }
  return next();
}

function redirectIfAuthed(req, res, next) {
  if (req.session && req.session.adminUserId) return res.redirect('/');
  return next();
}

// Optional defense-in-depth: restrict the admin UI to an IP allowlist when configured.
function ipAllowlist(req, res, next) {
  if (config.trustedAdminIps.length === 0) return next();
  const ip = req.ip;
  if (config.trustedAdminIps.includes(ip)) return next();
  return res.status(403).send('Forbidden');
}

module.exports = { requireAuth, redirectIfAuthed, ipAllowlist };
