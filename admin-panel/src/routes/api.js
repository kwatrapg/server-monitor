const express = require('express');
const rateLimit = require('express-rate-limit');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { signPayload } = require('../utils/licenseKey');

const router = express.Router();

const verifyLimiter = rateLimit({
  windowMs: 60 * 1000,
  limit: 30,
  standardHeaders: true,
  legacyHeaders: false,
});

// Public endpoint: called by a customer's monitor instance to check whether
// its license is valid. Response is HMAC-signed so callers can verify it
// really came from this server (see LICENSE_HMAC_SECRET).
router.post(
  '/v1/license/verify',
  verifyLimiter,
  [
    body('license_key').trim().notEmpty().isLength({ max: 64 }),
    body('domain').optional({ checkFalsy: true }).trim().isLength({ max: 255 }),
    body('fingerprint').optional({ checkFalsy: true }).trim().isLength({ max: 128 }),
  ],
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json(signPayload({ valid: false, reason: 'invalid_request', checked_at: new Date().toISOString() }));
    }

    const { license_key, domain, fingerprint } = req.body;
    const license = db
      .prepare(
        `SELECT l.*, p.slug AS plan_slug, p.name AS plan_name, p.max_servers, p.max_websites, p.max_checks
         FROM saas_licenses l JOIN saas_plans p ON p.id = l.plan_id
         WHERE l.license_key = ?`
      )
      .get(license_key);

    const checkedAt = new Date().toISOString();

    if (!license) {
      return res.status(404).json(signPayload({ valid: false, reason: 'not_found', checked_at: checkedAt }));
    }

    if (license.status !== 'active') {
      return res.status(200).json(signPayload({ valid: false, reason: license.status, checked_at: checkedAt }));
    }

    if (license.expires_at && new Date(license.expires_at).getTime() < Date.now()) {
      db.prepare("UPDATE saas_licenses SET status = 'expired', updated_at = datetime('now') WHERE id = ?").run(license.id);
      return res.status(200).json(signPayload({ valid: false, reason: 'expired', checked_at: checkedAt }));
    }

    if (license.domain && domain && license.domain.toLowerCase() !== domain.toLowerCase()) {
      return res.status(200).json(signPayload({ valid: false, reason: 'domain_mismatch', checked_at: checkedAt }));
    }

    if (fingerprint) {
      const existing = db
        .prepare('SELECT * FROM saas_license_activations WHERE license_id = ? AND fingerprint = ?')
        .get(license.id, fingerprint);

      if (existing) {
        db.prepare("UPDATE saas_license_activations SET last_seen_at = datetime('now'), ip = ? WHERE id = ?").run(req.ip, existing.id);
      } else {
        const activeCount = db
          .prepare('SELECT COUNT(*) AS n FROM saas_license_activations WHERE license_id = ?')
          .get(license.id).n;
        if (activeCount >= license.activation_limit) {
          return res.status(200).json(signPayload({ valid: false, reason: 'activation_limit_reached', checked_at: checkedAt }));
        }
        db.prepare(
          'INSERT INTO saas_license_activations (license_id, fingerprint, domain, ip) VALUES (?, ?, ?, ?)'
        ).run(license.id, fingerprint, domain || '', req.ip);
      }
    }

    db.prepare("UPDATE saas_licenses SET last_verified_at = datetime('now') WHERE id = ?").run(license.id);

    return res.status(200).json(
      signPayload({
        valid: true,
        plan: license.plan_slug,
        plan_name: license.plan_name,
        limits: {
          max_servers: license.max_servers,
          max_websites: license.max_websites,
          max_checks: license.max_checks,
        },
        expires_at: license.expires_at,
        checked_at: checkedAt,
      })
    );
  }
);

module.exports = router;
