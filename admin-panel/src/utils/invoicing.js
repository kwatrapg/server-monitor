const db = require('../db');
const { getSetting } = require('./settings');
const { calculateInvoice } = require('./gstCalc');

/**
 * Creates an invoice for a customer's purchase of a plan, snapshotting the
 * customer's current billing/GST details and the company's current invoice
 * settings — later edits to either never change an already-issued invoice.
 */
function createInvoiceForPurchase({ customer, plan, licenseId }) {
  const gstRate = parseFloat(getSetting('invoice_gst_rate', '18')) || 0;
  const companyName = getSetting('invoice_company_name', '');
  const companyAddress = getSetting('invoice_company_address', '');
  const companyState = getSetting('invoice_company_state', '');
  const companyGstNumber = getSetting('invoice_gst_number', '');

  const calc = calculateInvoice({
    priceCents: plan.price_cents,
    gstType: plan.gst_type,
    gstRatePercent: gstRate,
    wantsGst: customer.wants_gst_invoice,
    companyState,
    customerGstState: customer.gst_state,
  });

  const result = db
    .prepare(
      `INSERT INTO invoices (
         invoice_number, customer_id, license_id, plan_name,
         customer_name, customer_email, billing_address, billing_city, billing_state, billing_country,
         gst_number, gst_address, gst_state,
         company_name, company_address, company_state, company_gst_number,
         currency, gst_type, tax_type, cgst_rate, sgst_rate, igst_rate,
         subtotal_cents, cgst_cents, sgst_cents, igst_cents, total_cents
       ) VALUES (
         @invoiceNumber, @customerId, @licenseId, @planName,
         @customerName, @customerEmail, @billingAddress, @billingCity, @billingState, @billingCountry,
         @gstNumber, @gstAddress, @gstState,
         @companyName, @companyAddress, @companyState, @companyGstNumber,
         @currency, @gstType, @taxType, @cgstRate, @sgstRate, @igstRate,
         @subtotalCents, @cgstCents, @sgstCents, @igstCents, @totalCents
       )`
    )
    .run({
      invoiceNumber: 'PENDING', // replaced below once we have the row's id
      customerId: customer.id,
      licenseId: licenseId || null,
      planName: plan.name,
      customerName: customer.name,
      customerEmail: customer.email,
      billingAddress: customer.billing_address || '',
      billingCity: customer.billing_city || '',
      billingState: customer.billing_state || '',
      billingCountry: customer.billing_country || '',
      gstNumber: customer.wants_gst_invoice ? customer.gst_number || '' : '',
      gstAddress: customer.wants_gst_invoice ? customer.gst_address || '' : '',
      gstState: customer.wants_gst_invoice ? customer.gst_state || '' : '',
      companyName,
      companyAddress,
      companyState,
      companyGstNumber,
      currency: plan.currency,
      gstType: plan.gst_type || 'exclusive',
      taxType: calc.taxType,
      cgstRate: calc.cgstRate,
      sgstRate: calc.sgstRate,
      igstRate: calc.igstRate,
      subtotalCents: calc.subtotalCents,
      cgstCents: calc.cgstCents,
      sgstCents: calc.sgstCents,
      igstCents: calc.igstCents,
      totalCents: calc.totalCents,
    });

  const invoiceNumber = `INV-${String(result.lastInsertRowid).padStart(6, '0')}`;
  db.prepare('UPDATE invoices SET invoice_number = ? WHERE id = ?').run(invoiceNumber, result.lastInsertRowid);

  return result.lastInsertRowid;
}

module.exports = { createInvoiceForPurchase };
