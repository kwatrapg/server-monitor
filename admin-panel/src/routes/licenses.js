const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { requireAuth } = require('../middleware/auth');
const { csrfProtect } = require('../middleware/csrf');
const { generateLicenseKey } = require('../utils/licenseKey');
const { logAction } = require('../utils/audit');

const router = express.Router();

const validateLicense = [
  body('customer_id').isInt({ min: 1 }),
  body('plan_id').isInt({ min: 1 }),
  body('domain').trim().isLength({ max: 255 }).optional({ checkFalsy: true }),
  body('activation_limit').isInt({ min: 1, max: 1000 }),
  body('expires_at').optional({ checkFalsy: true }).isISO8601(),
];

function loadFormOptions() {
  return {
    customers: db.prepare('SELECT id, name, email FROM saas_customers ORDER BY name').all(),
    plans: db.prepare('SELECT id, name FROM saas_plans WHERE is_active = 1 ORDER BY name').all(),
  };
}

router.get('/', requireAuth, (req, res) => {
  const licenses = db
    .prepare(
      `SELECT l.*, c.name AS customer_name, c.email AS customer_email, p.name AS plan_name
       FROM saas_licenses l
       JOIN saas_customers c ON c.id = l.customer_id
       JOIN saas_plans p ON p.id = l.plan_id
       ORDER BY l.created_at DESC`
    )
    .all();
  res.render('licenses/list', { licenses });
});

router.get('/new', requireAuth, (req, res) => {
  res.render('licenses/form', { license: null, errors: [], ...loadFormOptions() });
});

router.post('/', requireAuth, validateLicense, csrfProtect, (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).render('licenses/form', { license: req.body, errors: errors.array(), ...loadFormOptions() });
  }

  const { customer_id, plan_id, domain, activation_limit, expires_at } = req.body;
  const licenseKey = generateLicenseKey();

  const result = db
    .prepare(
      `INSERT INTO saas_licenses (license_key, customer_id, plan_id, domain, activation_limit, expires_at)
       VALUES (?, ?, ?, ?, ?, ?)`
    )
    .run(licenseKey, customer_id, plan_id, domain || '', activation_limit, expires_at || null);

  logAction(req, 'create', 'license', result.lastInsertRowid, { licenseKey });
  res.redirect('/licenses');
});

router.get('/:id/edit', requireAuth, (req, res) => {
  const license = db.prepare('SELECT * FROM saas_licenses WHERE id = ?').get(req.params.id);
  if (!license) return res.status(404).send('License not found');
  const activations = db
    .prepare('SELECT * FROM saas_license_activations WHERE license_id = ? ORDER BY last_seen_at DESC')
    .all(req.params.id);
  res.render('licenses/form', { license, errors: [], activations, ...loadFormOptions() });
});

router.post('/:id', requireAuth, validateLicense, csrfProtect, (req, res) => {
  const errors = validationResult(req);
  const existing = db.prepare('SELECT * FROM saas_licenses WHERE id = ?').get(req.params.id);
  if (!existing) return res.status(404).send('License not found');

  if (!errors.isEmpty()) {
    return res.status(400).render('licenses/form', { license: { ...req.body, id: req.params.id }, errors: errors.array(), ...loadFormOptions() });
  }

  const { customer_id, plan_id, domain, activation_limit, expires_at } = req.body;
  db.prepare(
    `UPDATE saas_licenses SET
       customer_id = ?, plan_id = ?, domain = ?, activation_limit = ?, expires_at = ?,
       updated_at = datetime('now')
     WHERE id = ?`
  ).run(customer_id, plan_id, domain || '', activation_limit, expires_at || null, req.params.id);

  logAction(req, 'update', 'license', req.params.id, {});
  res.redirect('/licenses');
});

router.post('/:id/status', requireAuth, csrfProtect, (req, res) => {
  const { status } = req.body;
  if (!['active', 'suspended', 'revoked'].includes(status)) {
    return res.status(400).send('Invalid status');
  }
  const result = db.prepare("UPDATE saas_licenses SET status = ?, updated_at = datetime('now') WHERE id = ?").run(status, req.params.id);
  if (result.changes === 0) return res.status(404).send('License not found');
  logAction(req, `status_${status}`, 'license', req.params.id, {});
  res.redirect('/licenses');
});

router.post('/:id/regenerate-key', requireAuth, csrfProtect, (req, res) => {
  const license = db.prepare('SELECT * FROM saas_licenses WHERE id = ?').get(req.params.id);
  if (!license) return res.status(404).send('License not found');
  const newKey = generateLicenseKey();
  db.prepare("UPDATE saas_licenses SET license_key = ?, updated_at = datetime('now') WHERE id = ?").run(newKey, req.params.id);
  db.prepare('DELETE FROM saas_license_activations WHERE license_id = ?').run(req.params.id);
  logAction(req, 'regenerate_key', 'license', req.params.id, {});
  res.redirect(`/licenses/${req.params.id}/edit`);
});

module.exports = router;
