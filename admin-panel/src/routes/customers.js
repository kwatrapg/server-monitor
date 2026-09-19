const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { requireAuth } = require('../middleware/auth');
const { csrfProtect } = require('../middleware/csrf');
const { logAction } = require('../utils/audit');

const router = express.Router();

const validateCustomer = [
  body('name').trim().isLength({ min: 1, max: 200 }),
  body('email').trim().isEmail().normalizeEmail(),
  body('company').trim().isLength({ max: 200 }).optional({ checkFalsy: true }),
  body('notes').trim().isLength({ max: 2000 }).optional({ checkFalsy: true }),
];

router.get('/', requireAuth, (req, res) => {
  const customers = db.prepare('SELECT * FROM saas_customers ORDER BY created_at DESC').all();
  res.render('customers/list', { customers });
});

router.get('/new', requireAuth, (req, res) => {
  res.render('customers/form', { customer: null, errors: [] });
});

router.post('/', requireAuth, validateCustomer, csrfProtect, (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).render('customers/form', { customer: req.body, errors: errors.array() });
  }
  const { name, email, company, notes } = req.body;
  try {
    const result = db
      .prepare('INSERT INTO saas_customers (name, email, company, notes) VALUES (?, ?, ?, ?)')
      .run(name, email, company || '', notes || '');
    logAction(req, 'create', 'customer', result.lastInsertRowid, { email });
    res.redirect('/customers');
  } catch (err) {
    res.status(400).render('customers/form', {
      customer: req.body,
      errors: [{ msg: 'A customer with this email already exists.' }],
    });
  }
});

router.get('/:id/edit', requireAuth, (req, res) => {
  const customer = db.prepare('SELECT * FROM saas_customers WHERE id = ?').get(req.params.id);
  if (!customer) return res.status(404).send('Customer not found');
  const licenses = db
    .prepare(
      `SELECT l.*, p.name AS plan_name FROM saas_licenses l
       JOIN saas_plans p ON p.id = l.plan_id
       WHERE l.customer_id = ? ORDER BY l.created_at DESC`
    )
    .all(req.params.id);
  res.render('customers/form', { customer, errors: [], licenses });
});

router.post('/:id', requireAuth, validateCustomer, csrfProtect, (req, res) => {
  const errors = validationResult(req);
  const existing = db.prepare('SELECT * FROM saas_customers WHERE id = ?').get(req.params.id);
  if (!existing) return res.status(404).send('Customer not found');

  if (!errors.isEmpty()) {
    return res.status(400).render('customers/form', { customer: { ...req.body, id: req.params.id }, errors: errors.array() });
  }

  const { name, email, company, notes } = req.body;
  db.prepare(
    `UPDATE saas_customers SET name = ?, email = ?, company = ?, notes = ?, updated_at = datetime('now') WHERE id = ?`
  ).run(name, email, company || '', notes || '', req.params.id);

  logAction(req, 'update', 'customer', req.params.id, { email });
  res.redirect('/customers');
});

router.post('/:id/delete', requireAuth, csrfProtect, (req, res) => {
  const inUse = db
    .prepare('SELECT COUNT(*) AS n FROM saas_licenses WHERE customer_id = ?')
    .get(req.params.id).n;
  if (inUse > 0) {
    return res.status(400).send('Cannot delete a customer that has licenses. Remove their licenses first.');
  }
  db.prepare('DELETE FROM saas_customers WHERE id = ?').run(req.params.id);
  logAction(req, 'delete', 'customer', req.params.id, {});
  res.redirect('/customers');
});

module.exports = router;
