const express = require('express');
const db = require('../db');
const { requireAuth } = require('../middleware/auth');

const router = express.Router();

router.get('/', requireAuth, (req, res) => {
  const stats = {
    customers: db.prepare('SELECT COUNT(*) AS n FROM saas_customers').get().n,
    plans: db.prepare('SELECT COUNT(*) AS n FROM saas_plans WHERE is_active = 1').get().n,
    activeLicenses: db
      .prepare("SELECT COUNT(*) AS n FROM saas_licenses WHERE status = 'active'")
      .get().n,
    expiringSoon: db
      .prepare(
        `SELECT COUNT(*) AS n FROM saas_licenses
         WHERE status = 'active' AND expires_at IS NOT NULL
         AND datetime(expires_at) <= datetime('now', '+7 days')`
      )
      .get().n,
  };

  const recentLicenses = db
    .prepare(
      `SELECT l.*, c.name AS customer_name, p.name AS plan_name
       FROM saas_licenses l
       JOIN saas_customers c ON c.id = l.customer_id
       JOIN saas_plans p ON p.id = l.plan_id
       ORDER BY l.created_at DESC LIMIT 10`
    )
    .all();

  res.render('dashboard', { stats, recentLicenses });
});

module.exports = router;
