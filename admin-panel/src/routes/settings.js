const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { requireAuth } = require('../middleware/auth');
const { csrfProtect } = require('../middleware/csrf');
const { getSetting, setSetting } = require('../utils/settings');
const { encryptSecret, decryptSecret } = require('../utils/crypto');
const { PROVIDERS } = require('../utils/paymentGateways');
const { logAction } = require('../utils/audit');

const router = express.Router();

router.get('/', requireAuth, (req, res) => {
  res.render('settings/general', {
    settings: {
      site_name: getSetting('site_name', 'Sentruo Admin'),
      support_email: getSetting('support_email', ''),
      currency: getSetting('currency', 'USD'),
    },
    errors: [],
  });
});

router.post(
  '/',
  requireAuth,
  [
    body('site_name').trim().isLength({ min: 1, max: 120 }),
    body('support_email').trim().optional({ checkFalsy: true }).isEmail(),
    body('currency').trim().isLength({ min: 3, max: 8 }),
  ],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('settings/general', { settings: req.body, errors: errors.array() });
    }
    setSetting('site_name', req.body.site_name);
    setSetting('support_email', req.body.support_email || '');
    setSetting('currency', req.body.currency.toUpperCase());
    logAction(req, 'update', 'settings', null, {});
    res.redirect('/settings');
  }
);

function loadGateways() {
  const rows = db.prepare('SELECT * FROM payment_gateways').all();
  const byProvider = Object.fromEntries(rows.map((r) => [r.provider, r]));

  return Object.entries(PROVIDERS).map(([key, meta]) => {
    const row = byProvider[key];
    let config = {};
    if (row && row.config_encrypted) {
      try { config = JSON.parse(decryptSecret(row.config_encrypted)); } catch (e) { config = {}; }
    }
    return {
      key,
      label: meta.label,
      fields: meta.fields,
      enabled: row ? Boolean(row.enabled) : false,
      mode: row ? row.mode : 'test',
      config, // decrypted, only ever rendered as masked placeholders in the view
    };
  });
}

router.get('/payment-gateways', requireAuth, (req, res) => {
  res.render('settings/payment-gateways', { gateways: loadGateways() });
});

router.post('/payment-gateways/:provider', requireAuth, csrfProtect, (req, res) => {
  const provider = req.params.provider;
  const meta = PROVIDERS[provider];
  if (!meta) return res.status(404).send('Unknown payment gateway');

  const existing = db.prepare('SELECT * FROM payment_gateways WHERE provider = ?').get(provider);
  let existingConfig = {};
  if (existing && existing.config_encrypted) {
    try { existingConfig = JSON.parse(decryptSecret(existing.config_encrypted)); } catch (e) { existingConfig = {}; }
  }

  const newConfig = {};
  for (const field of meta.fields) {
    const posted = (req.body[field.name] || '').trim();
    // Blank means "leave unchanged" for already-saved secret fields — the
    // form never pre-fills the real secret, only a masked placeholder.
    newConfig[field.name] = posted !== '' ? posted : (existingConfig[field.name] || '');
  }

  const enabled = req.body.enabled ? 1 : 0;
  const mode = req.body.mode === 'live' ? 'live' : 'test';
  const encrypted = encryptSecret(JSON.stringify(newConfig));

  db.prepare(
    `INSERT INTO payment_gateways (provider, enabled, mode, config_encrypted, updated_at)
     VALUES (@provider, @enabled, @mode, @config, datetime('now'))
     ON CONFLICT(provider) DO UPDATE SET
       enabled = excluded.enabled, mode = excluded.mode,
       config_encrypted = excluded.config_encrypted, updated_at = excluded.updated_at`
  ).run({ provider, enabled, mode, config: encrypted });

  logAction(req, 'update', 'payment_gateway', null, { provider, enabled: Boolean(enabled), mode });
  res.redirect('/settings/payment-gateways');
});

router.get('/invoice', requireAuth, (req, res) => {
  res.render('settings/invoice', {
    settings: {
      invoice_company_name: getSetting('invoice_company_name', ''),
      invoice_gst_number: getSetting('invoice_gst_number', ''),
      invoice_gst_rate: getSetting('invoice_gst_rate', '18'),
      invoice_company_address: getSetting('invoice_company_address', ''),
      invoice_company_state: getSetting('invoice_company_state', ''),
      invoice_other_details: getSetting('invoice_other_details', ''),
    },
    errors: [],
  });
});

router.post(
  '/invoice',
  requireAuth,
  [
    body('invoice_company_name').trim().isLength({ min: 1, max: 200 }),
    body('invoice_gst_number').trim().optional({ checkFalsy: true }).isLength({ max: 30 }),
    body('invoice_gst_rate').trim().isFloat({ min: 0, max: 100 }),
    body('invoice_company_address').trim().isLength({ max: 500 }).optional({ checkFalsy: true }),
    body('invoice_company_state').trim().isLength({ max: 100 }).optional({ checkFalsy: true }),
    body('invoice_other_details').trim().isLength({ max: 2000 }).optional({ checkFalsy: true }),
  ],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('settings/invoice', { settings: req.body, errors: errors.array() });
    }
    setSetting('invoice_company_name', req.body.invoice_company_name);
    setSetting('invoice_gst_number', req.body.invoice_gst_number || '');
    setSetting('invoice_gst_rate', req.body.invoice_gst_rate);
    setSetting('invoice_company_address', req.body.invoice_company_address || '');
    setSetting('invoice_company_state', req.body.invoice_company_state || '');
    setSetting('invoice_other_details', req.body.invoice_other_details || '');
    logAction(req, 'update', 'invoice_settings', null, {});
    res.redirect('/settings/invoice');
  }
);

module.exports = router;
