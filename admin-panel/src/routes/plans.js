const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { requireAuth } = require('../middleware/auth');
const { csrfProtect } = require('../middleware/csrf');
const { logAction } = require('../utils/audit');
const { toCents } = require('../utils/money');
const { CURRENCIES, CURRENCY_CODES } = require('../utils/currencies');
const { getSetting } = require('../utils/settings');

const router = express.Router();

function slugify(name) {
  return name
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');
}

const validatePlan = [
  body('name').trim().isLength({ min: 1, max: 120 }),
  body('description').trim().isLength({ max: 2000 }).optional({ checkFalsy: true }),
  body('price').isFloat({ min: 0 }),
  body('currency').trim().toUpperCase().isIn(CURRENCY_CODES),
  body('billing_interval').isIn(['monthly', 'yearly', 'lifetime']),
  body('max_servers').isInt({ min: 0 }),
  body('max_websites').isInt({ min: 0 }),
  body('max_checks').isInt({ min: 0 }),
  body('trial_days').optional({ checkFalsy: true }).isInt({ min: 1, max: 365 }),
];

router.get('/', requireAuth, (req, res) => {
  const plans = db.prepare('SELECT * FROM saas_plans ORDER BY price_cents ASC').all();
  res.render('plans/list', { plans });
});

router.get('/new', requireAuth, (req, res) => {
  res.render('plans/form', { plan: null, errors: [], currencies: CURRENCIES, defaultCurrency: getSetting('currency', 'USD') });
});

router.post('/', requireAuth, validatePlan, csrfProtect, (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).render('plans/form', { plan: req.body, errors: errors.array(), currencies: CURRENCIES, defaultCurrency: getSetting('currency', 'USD') });
  }

  const { name, description, price, currency, billing_interval, max_servers, max_websites, max_checks, trial_days } = req.body;
  const isDemo = req.body.is_demo ? 1 : 0;
  const slug = slugify(name);

  try {
    const result = db
      .prepare(
        `INSERT INTO saas_plans (name, slug, description, price_cents, currency, billing_interval, max_servers, max_websites, max_checks, is_demo, trial_days)
         VALUES (@name, @slug, @description, @price_cents, @currency, @billing_interval, @max_servers, @max_websites, @max_checks, @is_demo, @trial_days)`
      )
      .run({
        name,
        slug,
        description: description || '',
        price_cents: toCents(price),
        currency: currency.toUpperCase(),
        billing_interval,
        max_servers: parseInt(max_servers, 10),
        max_websites: parseInt(max_websites, 10),
        max_checks: parseInt(max_checks, 10),
        is_demo: isDemo,
        trial_days: isDemo && trial_days ? parseInt(trial_days, 10) : null,
      });
    logAction(req, 'create', 'plan', result.lastInsertRowid, { name });
    res.redirect('/plans');
  } catch (err) {
    res.status(400).render('plans/form', {
      plan: req.body,
      errors: [{ msg: 'A plan with a similar name already exists.' }],
      currencies: CURRENCIES,
      defaultCurrency: getSetting('currency', 'USD'),
    });
  }
});

router.get('/:id/edit', requireAuth, (req, res) => {
  const plan = db.prepare('SELECT * FROM saas_plans WHERE id = ?').get(req.params.id);
  if (!plan) return res.status(404).send('Plan not found');
  res.render('plans/form', { plan, errors: [], currencies: CURRENCIES });
});

router.post('/:id', requireAuth, validatePlan, csrfProtect, (req, res) => {
  const errors = validationResult(req);
  const existing = db.prepare('SELECT * FROM saas_plans WHERE id = ?').get(req.params.id);
  if (!existing) return res.status(404).send('Plan not found');

  if (!errors.isEmpty()) {
    return res.status(400).render('plans/form', { plan: { ...req.body, id: req.params.id }, errors: errors.array(), currencies: CURRENCIES, defaultCurrency: getSetting('currency', 'USD') });
  }

  const { name, description, price, currency, billing_interval, max_servers, max_websites, max_checks, is_active, trial_days } = req.body;
  const isDemo = req.body.is_demo ? 1 : 0;

  db.prepare(
    `UPDATE saas_plans SET
       name = @name, description = @description, price_cents = @price_cents,
       currency = @currency, billing_interval = @billing_interval,
       max_servers = @max_servers, max_websites = @max_websites, max_checks = @max_checks,
       is_active = @is_active, is_demo = @is_demo, trial_days = @trial_days, updated_at = datetime('now')
     WHERE id = @id`
  ).run({
    id: req.params.id,
    name,
    description: description || '',
    price_cents: toCents(price),
    currency: currency.toUpperCase(),
    billing_interval,
    max_servers: parseInt(max_servers, 10),
    max_websites: parseInt(max_websites, 10),
    max_checks: parseInt(max_checks, 10),
    is_active: is_active ? 1 : 0,
    is_demo: isDemo,
    trial_days: isDemo && trial_days ? parseInt(trial_days, 10) : null,
  });

  logAction(req, 'update', 'plan', req.params.id, { name });
  res.redirect('/plans');
});

router.post('/:id/delete', requireAuth, csrfProtect, (req, res) => {
  const inUse = db
    .prepare('SELECT COUNT(*) AS n FROM saas_licenses WHERE plan_id = ?')
    .get(req.params.id).n;
  if (inUse > 0) {
    return res.status(400).send('Cannot delete a plan that has licenses assigned to it. Deactivate it instead.');
  }
  db.prepare('DELETE FROM saas_plans WHERE id = ?').run(req.params.id);
  logAction(req, 'delete', 'plan', req.params.id, {});
  res.redirect('/plans');
});

module.exports = router;
