function requireCustomerAuth(req, res, next) {
  if (req.session && req.session.customerId) return next();
  return res.redirect('/portal/login');
}

function redirectIfCustomerAuthed(req, res, next) {
  if (req.session && req.session.customerId) return res.redirect('/portal/dashboard');
  return next();
}

module.exports = { requireCustomerAuth, redirectIfCustomerAuthed };
