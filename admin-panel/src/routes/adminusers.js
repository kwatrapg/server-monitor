const express = require('express');
const bcrypt = require('bcryptjs');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { requireAuth } = require('../middleware/auth');
const { csrfProtect } = require('../middleware/csrf');
const { logAction } = require('../utils/audit');

const router = express.Router();

function activeAdminCount(excludeId = null) {
  const row = excludeId
    ? db.prepare('SELECT COUNT(*) AS n FROM admin_users WHERE paused = 0 AND id != ?').get(excludeId)
    : db.prepare('SELECT COUNT(*) AS n FROM admin_users WHERE paused = 0').get();
  return row.n;
}

router.get('/', requireAuth, (req, res) => {
  const users = db.prepare('SELECT id, username, email, role, paused, created_at, last_login_at FROM admin_users ORDER BY created_at ASC').all();
  res.render('adminusers/list', { users, currentUserId: req.session.adminUserId });
});

router.get('/new', requireAuth, (req, res) => {
  res.render('adminusers/form', { user: null, errors: [] });
});

const validateNewUser = [
  body('username').trim().isLength({ min: 3, max: 60 }).matches(/^[a-zA-Z0-9._-]+$/),
  body('email').trim().isEmail().normalizeEmail(),
  body('password').isLength({ min: 12 }),
];

router.post('/', requireAuth, validateNewUser, csrfProtect, (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).render('adminusers/form', { user: req.body, errors: errors.array() });
  }
  const { username, email, password } = req.body;
  const hash = bcrypt.hashSync(password, 12);
  try {
    const result = db
      .prepare('INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)')
      .run(username, email, hash);
    logAction(req, 'create', 'admin_user', result.lastInsertRowid, { username });
    res.redirect('/admin-users');
  } catch (err) {
    res.status(400).render('adminusers/form', {
      user: req.body,
      errors: [{ msg: 'A user with that username or email already exists.' }],
    });
  }
});

router.get('/:id/edit', requireAuth, (req, res) => {
  const user = db.prepare('SELECT id, username, email, role, paused FROM admin_users WHERE id = ?').get(req.params.id);
  if (!user) return res.status(404).send('User not found');
  res.render('adminusers/form', { user, errors: [] });
});

router.post(
  '/:id',
  requireAuth,
  [
    body('username').trim().isLength({ min: 3, max: 60 }).matches(/^[a-zA-Z0-9._-]+$/),
    body('email').trim().isEmail().normalizeEmail(),
    body('password').optional({ checkFalsy: true }).isLength({ min: 12 }),
  ],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req);
    const existing = db.prepare('SELECT * FROM admin_users WHERE id = ?').get(req.params.id);
    if (!existing) return res.status(404).send('User not found');

    if (!errors.isEmpty()) {
      return res.status(400).render('adminusers/form', { user: { ...req.body, id: req.params.id }, errors: errors.array() });
    }

    const { username, email, password } = req.body;
    try {
      if (password) {
        db.prepare('UPDATE admin_users SET username = ?, email = ?, password_hash = ? WHERE id = ?')
          .run(username, email, bcrypt.hashSync(password, 12), req.params.id);
      } else {
        db.prepare('UPDATE admin_users SET username = ?, email = ? WHERE id = ?')
          .run(username, email, req.params.id);
      }
      logAction(req, 'update', 'admin_user', req.params.id, { username });
      res.redirect('/admin-users');
    } catch (err) {
      res.status(400).render('adminusers/form', {
        user: { ...req.body, id: req.params.id },
        errors: [{ msg: 'A user with that username or email already exists.' }],
      });
    }
  }
);

router.post('/:id/pause', requireAuth, csrfProtect, (req, res) => {
  const id = Number(req.params.id);
  const user = db.prepare('SELECT * FROM admin_users WHERE id = ?').get(id);
  if (!user) return res.status(404).send('User not found');

  if (!user.paused && activeAdminCount(id) === 0) {
    return res.status(400).send('Cannot pause the last active admin account.');
  }

  const newPaused = user.paused ? 0 : 1;
  db.prepare('UPDATE admin_users SET paused = ? WHERE id = ?').run(newPaused, id);
  logAction(req, newPaused ? 'pause' : 'unpause', 'admin_user', id, {});
  res.redirect('/admin-users');
});

router.post('/:id/delete', requireAuth, csrfProtect, (req, res) => {
  const id = Number(req.params.id);
  if (id === req.session.adminUserId) {
    return res.status(400).send('You cannot delete your own account while signed in as it.');
  }
  const totalCount = db.prepare('SELECT COUNT(*) AS n FROM admin_users').get().n;
  if (totalCount <= 1) {
    return res.status(400).send('Cannot delete the only admin account.');
  }
  db.prepare('DELETE FROM admin_users WHERE id = ?').run(id);
  logAction(req, 'delete', 'admin_user', id, {});
  res.redirect('/admin-users');
});

module.exports = router;
