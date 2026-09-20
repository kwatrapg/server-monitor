const express = require('express');
const bcrypt = require('bcryptjs');
const rateLimit = require('express-rate-limit');
const session = require('express-session');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const config = require('../config');
const SqliteSessionStore = require('../db/sessionStore');
const { requireCustomerAuth, redirectIfCustomerAuthed } = require('../middleware/customerAuth');
const { csrfToken, csrfProtect } = require('../middleware/csrf');
const { generateLicenseKey } = require('../utils/licenseKey');
const { logAction } = require('../utils/audit');
const { isValidGstFormat, stateForGst, statesMatch } = require('../utils/gstStates');

const router = express.Router();

// Fully separate session (own cookie, own store instance) from the admin
// session — a customer and an admin can be signed in from the same browser
// without either session clobbering the other.
router.use(
  session({
    store: new SqliteSessionStore(),
    name: 'sentruo.customer.sid',
    secret: config.sessionSecret,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: config.env === 'production',
      maxAge: 8 * 60 * 60 * 1000,
    },
  })
);

router.use(csrfToken);

router.use((req, res, next) => {
  res.locals.isCustomerAuthed = Boolean(req.session && req.session.customerId);
  res.locals.customerName = req.session ? req.session.customerName : null;
  next();
});

const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 20,
  standardHeaders: true,
  legacyHeaders: false,
});

// --- Signup -----------------------------------------------------------

router.get('/signup', redirectIfCustomerAuthed, (req, res) => {
  res.render('portal/signup', { error: null });
});

router.post(
  '/signup',
  redirectIfCustomerAuthed,
  authLimiter,
  [
    body('name').trim().isLength({ min: 1, max: 200 }),
    body('email').trim().isEmail().normalizeEmail(),
    body('password').isLength({ min: 12 }),
  ],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('portal/signup', {
        error: 'Please enter your name, a valid email, and a password of at least 12 characters.',
      });
    }

    const { name, email, password } = req.body;
    const existing = db.prepare('SELECT id, password_hash FROM saas_customers WHERE email = ?').get(email);
    if (existing && existing.password_hash) {
      return res.status(400).render('portal/signup', { error: 'An account with this email already exists — please log in.' });
    }

    const hash = bcrypt.hashSync(password, 12);
    let customerId;
    if (existing) {
      // A customer record already exists (e.g. created by an admin) with no
      // portal password yet — claim it rather than erroring on the unique email.
      db.prepare('UPDATE saas_customers SET name = ?, password_hash = ? WHERE id = ?').run(name, hash, existing.id);
      customerId = existing.id;
    } else {
      const result = db.prepare('INSERT INTO saas_customers (name, email, password_hash) VALUES (?, ?, ?)').run(name, email, hash);
      customerId = result.lastInsertRowid;
    }

    req.session.regenerate((err) => {
      if (err) return res.status(500).render('portal/signup', { error: 'Could not create your account. Please try again.' });
      req.session.customerId = customerId;
      req.session.customerName = name;
      logAction(req, 'signup', 'customer', customerId, { email });
      res.redirect('/portal/plans');
    });
  }
);

// --- Login / logout -----------------------------------------------------

router.get('/login', redirectIfCustomerAuthed, (req, res) => {
  res.render('portal/login', { error: null });
});

router.post(
  '/login',
  redirectIfCustomerAuthed,
  authLimiter,
  [body('email').trim().isEmail().normalizeEmail(), body('password').notEmpty()],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('portal/login', { error: 'Enter a valid email and password.' });
    }

    const { email, password } = req.body;
    const customer = db.prepare('SELECT * FROM saas_customers WHERE email = ?').get(email);
    const hash = customer && customer.password_hash ? customer.password_hash : '$2a$10$invalidsaltinvalidsaltinvalidsaltinva';
    const valid = bcrypt.compareSync(password, hash);

    if (!customer || !customer.password_hash || !valid) {
      logAction(req, 'login_failed', 'customer', customer ? customer.id : null, { email });
      return res.status(401).render('portal/login', { error: 'Invalid email or password.' });
    }

    req.session.regenerate((err) => {
      if (err) return res.status(500).render('portal/login', { error: 'Login failed. Please try again.' });
      req.session.customerId = customer.id;
      req.session.customerName = customer.name;
      logAction(req, 'login_success', 'customer', customer.id, { email });
      res.redirect('/portal/dashboard');
    });
  }
);

router.post('/logout', csrfProtect, (req, res) => {
  req.session.destroy(() => {
    res.clearCookie('sentruo.customer.sid');
    res.redirect('/portal/login');
  });
});

// --- Plans & checkout -----------------------------------------------------

router.get('/plans', requireCustomerAuth, (req, res) => {
  const plans = db.prepare('SELECT * FROM saas_plans WHERE is_active = 1 ORDER BY price_cents ASC').all();
  const usedDemo = hasUsedDemo(req.session.customerId);
  res.render('portal/plans', { plans, usedDemo });
});

function hasUsedDemo(customerId) {
  const row = db
    .prepare(
      `SELECT 1 FROM saas_licenses l JOIN saas_plans p ON p.id = l.plan_id
       WHERE l.customer_id = ? AND p.is_demo = 1 LIMIT 1`
    )
    .get(customerId);
  return Boolean(row);
}

router.get('/checkout/:planId', requireCustomerAuth, (req, res) => {
  const plan = db.prepare('SELECT * FROM saas_plans WHERE id = ? AND is_active = 1').get(req.params.planId);
  if (!plan) return res.status(404).send('Plan not found');
  const gateways = db.prepare('SELECT provider FROM payment_gateways WHERE enabled = 1').all().map((r) => r.provider);
  const alreadyUsedDemo = plan.is_demo ? hasUsedDemo(req.session.customerId) : false;
  res.render('portal/checkout', { plan, gateways, alreadyUsedDemo });
});

function computeExpiry(interval) {
  const d = new Date();
  if (interval === 'monthly') d.setMonth(d.getMonth() + 1);
  else if (interval === 'yearly') d.setFullYear(d.getFullYear() + 1);
  else return null; // lifetime
  return d.toISOString().slice(0, 10);
}

// No live payment gateway is wired up to an actual checkout/webhook flow yet
// (see admin-panel/README.md) — this records the purchase and issues the
// license immediately. Swap this handler for a real gateway confirmation
// (e.g. verify a Razorpay payment signature) once that integration exists.
//
// Demo plans skip payment entirely and don't self-activate: they're created
// as 'pending_approval' (no expiry yet) for an admin to approve or reject —
// see POST /licenses/:id/approve-demo. A customer may only ever have one
// demo license, checked here server-side (not just hidden in the UI).
router.post('/checkout/:planId', requireCustomerAuth, csrfProtect, (req, res) => {
  const plan = db.prepare('SELECT * FROM saas_plans WHERE id = ? AND is_active = 1').get(req.params.planId);
  if (!plan) return res.status(404).send('Plan not found');

  if (plan.is_demo) {
    if (hasUsedDemo(req.session.customerId)) {
      return res.status(400).send('You have already used your one-time demo. Please choose a paid plan.');
    }
    const licenseKey = generateLicenseKey();
    const result = db
      .prepare(
        `INSERT INTO saas_licenses (license_key, customer_id, plan_id, activation_limit, status, amount_cents, payment_status, payment_method)
         VALUES (?, ?, ?, 1, 'pending_approval', 0, 'n/a', 'demo')`
      )
      .run(licenseKey, req.session.customerId, plan.id);

    logAction(req, 'demo_requested', 'license', result.lastInsertRowid, { customerId: req.session.customerId, plan: plan.slug });
    return res.redirect(`/portal/dashboard?new=${result.lastInsertRowid}`);
  }

  const licenseKey = generateLicenseKey();
  const expiresAt = computeExpiry(plan.billing_interval);

  const result = db
    .prepare(
      `INSERT INTO saas_licenses (license_key, customer_id, plan_id, activation_limit, expires_at, amount_cents, payment_status, payment_method)
       VALUES (?, ?, ?, 1, ?, ?, 'paid', 'manual')`
    )
    .run(licenseKey, req.session.customerId, plan.id, expiresAt, plan.price_cents);

  logAction(req, 'checkout', 'license', result.lastInsertRowid, { customerId: req.session.customerId, plan: plan.slug });
  res.redirect(`/portal/dashboard?new=${result.lastInsertRowid}`);
});

// --- Dashboard -----------------------------------------------------

router.get('/dashboard', requireCustomerAuth, (req, res) => {
  const licenses = db
    .prepare(
      `SELECT l.*, p.name AS plan_name, p.max_servers, p.max_websites, p.max_checks, p.currency
       FROM saas_licenses l JOIN saas_plans p ON p.id = l.plan_id
       WHERE l.customer_id = ? ORDER BY l.created_at DESC`
    )
    .all(req.session.customerId);

  const activationCounts = {};
  if (licenses.length) {
    const ids = licenses.map((l) => l.id);
    const rows = db
      .prepare(`SELECT license_id, COUNT(*) AS n FROM saas_license_activations WHERE license_id IN (${ids.map(() => '?').join(',')}) GROUP BY license_id`)
      .all(...ids);
    rows.forEach((r) => { activationCounts[r.license_id] = r.n; });
  }

  res.render('portal/dashboard', {
    licenses,
    activationCounts,
    highlightId: req.query.new ? Number(req.query.new) : null,
  });
});

// --- Billing -----------------------------------------------------

router.get('/billing', requireCustomerAuth, (req, res) => {
  const customer = db.prepare('SELECT * FROM saas_customers WHERE id = ?').get(req.session.customerId);
  res.render('portal/billing', { customer, errors: [] });
});

router.post(
  '/billing',
  requireCustomerAuth,
  [
    body('billing_name').trim().isLength({ min: 1, max: 200 }),
    body('billing_address').trim().isLength({ min: 1, max: 500 }),
    body('billing_city').trim().isLength({ min: 1, max: 100 }),
    body('billing_state').trim().isLength({ min: 1, max: 100 }),
    body('billing_zip').trim().isLength({ min: 1, max: 20 }),
    body('billing_country').trim().isLength({ min: 1, max: 100 }),
    body('gst_number').optional({ checkFalsy: true }).trim().isLength({ max: 15 }),
    body('gst_address').optional({ checkFalsy: true }).trim().isLength({ max: 500 }),
  ],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req).array();
    const wantsGst = Boolean(req.body.wants_gst_invoice);
    const { billing_name, billing_address, billing_city, billing_state, billing_zip, billing_country, gst_address } = req.body;
    const gst_number = String(req.body.gst_number || '').toUpperCase().trim();
    let gst_state = '';

    if (wantsGst) {
      if (!gst_number || !gst_address) {
        errors.push({ msg: 'GST number and GST address are required for a GST invoice.' });
      } else if (!isValidGstFormat(gst_number)) {
        errors.push({ msg: 'That does not look like a valid 15-character GST number (GSTIN).' });
      } else {
        gst_state = stateForGst(gst_number);
        if (!gst_state) {
          errors.push({ msg: 'The state code in this GST number is not recognized.' });
        } else if (!statesMatch(gst_state, billing_state)) {
          errors.push({ msg: `This GST number is registered in ${gst_state}, which doesn't match your billing address state (${billing_state || 'not set'}).` });
        }
      }
    }

    if (errors.length > 0) {
      return res.status(400).render('portal/billing', {
        customer: { id: req.session.customerId, ...req.body, wants_gst_invoice: wantsGst ? 1 : 0 },
        errors,
      });
    }

    db.prepare(
      `UPDATE saas_customers SET
         billing_name = ?, billing_address = ?, billing_city = ?, billing_state = ?, billing_zip = ?, billing_country = ?,
         wants_gst_invoice = ?, gst_number = ?, gst_address = ?, gst_state = ?,
         updated_at = datetime('now')
       WHERE id = ?`
    ).run(
      billing_name,
      billing_address,
      billing_city,
      billing_state,
      billing_zip,
      billing_country,
      wantsGst ? 1 : 0,
      wantsGst ? gst_number : '',
      wantsGst ? gst_address : '',
      wantsGst ? gst_state : '',
      req.session.customerId
    );

    logAction(req, 'update', 'customer_billing', req.session.customerId, {});
    res.redirect('/portal/billing');
  }
);

module.exports = router;
