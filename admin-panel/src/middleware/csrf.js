const crypto = require('crypto');

// Minimal synchronizer-token CSRF protection scoped to the session,
// avoiding a dependency on the unmaintained `csurf` package.
function csrfToken(req, res, next) {
  if (!req.session.csrfToken) {
    req.session.csrfToken = crypto.randomBytes(32).toString('hex');
  }
  res.locals.csrfToken = req.session.csrfToken;
  next();
}

function csrfProtect(req, res, next) {
  const submitted = req.body && req.body._csrf;
  if (submitted && req.session && submitted === req.session.csrfToken) {
    return next();
  }
  return res.status(403).send('Invalid or missing CSRF token.');
}

module.exports = { csrfToken, csrfProtect };
