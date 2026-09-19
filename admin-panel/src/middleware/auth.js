const config = require('../config');

function requireAuth(req, res, next) {
  if (req.session && req.session.adminUserId) return next();
  return res.redirect('/login');
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
