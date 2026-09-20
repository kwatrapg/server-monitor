const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../db');
const { requireAuth } = require('../middleware/auth');
const { csrfProtect } = require('../middleware/csrf');
const { logAction } = require('../utils/audit');

const router = express.Router();

router.get('/', requireAuth, (req, res) => {
  const invoices = db
    .prepare(
      `SELECT i.*, c.email AS customer_current_email
       FROM invoices i
       JOIN saas_customers c ON c.id = i.customer_id
       ORDER BY i.created_at DESC`
    )
    .all();
  res.render('invoices/list', { invoices });
});

router.get('/:id', requireAuth, (req, res) => {
  const invoice = db.prepare('SELECT * FROM invoices WHERE id = ?').get(req.params.id);
  if (!invoice) return res.status(404).send('Invoice not found');
  res.render('invoices/form', { invoice, errors: [] });
});

// Editable: status/notes always; the financial breakdown can also be
// corrected (e.g. a wrong GST classification), in which case the totals
// are recomputed from the edited subtotal/rates rather than trusted as
// entered, so cgst+sgst+subtotal always adds up to total on save.
router.post(
  '/:id',
  requireAuth,
  [
    body('status').isIn(['paid', 'void', 'refunded']),
    body('notes').trim().isLength({ max: 2000 }).optional({ checkFalsy: true }),
    body('subtotal').isFloat({ min: 0 }),
    body('tax_type').isIn(['none', 'cgst_sgst', 'igst']),
    body('cgst_rate').isFloat({ min: 0, max: 100 }),
    body('sgst_rate').isFloat({ min: 0, max: 100 }),
    body('igst_rate').isFloat({ min: 0, max: 100 }),
  ],
  csrfProtect,
  (req, res) => {
    const errors = validationResult(req);
    const invoice = db.prepare('SELECT * FROM invoices WHERE id = ?').get(req.params.id);
    if (!invoice) return res.status(404).send('Invoice not found');

    if (!errors.isEmpty()) {
      return res.status(400).render('invoices/form', { invoice: { ...invoice, ...req.body }, errors: errors.array() });
    }

    const subtotalCents = Math.round(parseFloat(req.body.subtotal) * 100);
    const taxType = req.body.tax_type;
    const cgstRate = taxType === 'cgst_sgst' ? parseFloat(req.body.cgst_rate) : 0;
    const sgstRate = taxType === 'cgst_sgst' ? parseFloat(req.body.sgst_rate) : 0;
    const igstRate = taxType === 'igst' ? parseFloat(req.body.igst_rate) : 0;
    const cgstCents = Math.round((subtotalCents * cgstRate) / 100);
    const sgstCents = Math.round((subtotalCents * sgstRate) / 100);
    const igstCents = Math.round((subtotalCents * igstRate) / 100);
    const totalCents = subtotalCents + cgstCents + sgstCents + igstCents;

    db.prepare(
      `UPDATE invoices SET
         status = ?, notes = ?, subtotal_cents = ?, tax_type = ?,
         cgst_rate = ?, sgst_rate = ?, igst_rate = ?,
         cgst_cents = ?, sgst_cents = ?, igst_cents = ?, total_cents = ?,
         updated_at = datetime('now')
       WHERE id = ?`
    ).run(
      req.body.status,
      req.body.notes || '',
      subtotalCents,
      taxType,
      cgstRate,
      sgstRate,
      igstRate,
      cgstCents,
      sgstCents,
      igstCents,
      totalCents,
      req.params.id
    );

    logAction(req, 'update', 'invoice', req.params.id, { status: req.body.status });
    res.redirect(`/invoices/${req.params.id}`);
  }
);

module.exports = router;
