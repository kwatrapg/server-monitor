const express = require('express');
const bcrypt = require('bcryptjs');
const rateLimit = require('express-rate-limit');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { redirectIfAuthed } = require('../middleware/auth');
const { csrfProtect } = require('../middleware/csrf');
const { logAction } = require('../utils/audit');

const router = express.Router();

const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 10,
  standardHeaders: true,
  legacyHeaders: false,
  message: 'Too many login attempts. Try again later.',
});

router.get('/login', redirectIfAuthed, (req, res) => {
  res.render('login', { error: null });
});

router.post(
  '/login',
  redirectIfAuthed,
  loginLimiter,
  [
    body('username').trim().notEmpty(),
    body('password').notEmpty(),
  ],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('login', { error: 'Username and password are required.' });
    }

    const { username, password } = req.body;
    const user = db
      .prepare('SELECT * FROM admin_users WHERE username = ?')
      .get(username);

    // Always run bcrypt.compareSync to keep response timing similar whether
    // or not the user exists, reducing username enumeration via timing.
    const hash = user ? user.password_hash : '$2a$10$invalidsaltinvalidsaltinvalidsaltinva';
    const valid = bcrypt.compareSync(password, hash);

    if (!user || !valid) {
      logAction(req, 'login_failed', 'admin_user', user ? user.id : null, { username });
      return res.status(401).render('login', { error: 'Invalid credentials.' });
    }

    req.session.regenerate((err) => {
      if (err) return res.status(500).render('login', { error: 'Login failed. Try again.' });
      req.session.adminUserId = user.id;
      req.session.username = user.username;
      db.prepare("UPDATE admin_users SET last_login_at = datetime('now') WHERE id = ?").run(user.id);
      logAction(req, 'login_success', 'admin_user', user.id, { username });
      res.redirect('/');
    });
  }
);

router.post('/logout', csrfProtect, (req, res) => {
  const userId = req.session.adminUserId;
  req.session.destroy(() => {
    res.clearCookie('sentruo.admin.sid');
    res.redirect('/login');
  });
  if (userId) logAction(req, 'logout', 'admin_user', userId, {});
});

module.exports = router;
