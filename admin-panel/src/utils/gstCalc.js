const { statesMatch } = require('./gstStates');

/**
 * Computes an invoice's tax breakdown.
 *
 * GST only applies when the customer has a valid GST invoice on file
 * (wantsGst + gstState) — otherwise this is a plain receipt with no tax
 * split. When it does apply:
 *   - company state === customer's GST state -> intra-state -> CGST + SGST,
 *     each half of gstRatePercent (e.g. 18% -> 9% + 9%).
 *   - different states -> inter-state -> IGST at the full gstRatePercent.
 *
 * priceCents is the plan's price. gstType is 'inclusive' (priceCents already
 * contains the tax) or 'exclusive' (tax is added on top).
 *
 * Returns cent amounts (rounded) plus the rates used, so the caller can
 * store an immutable snapshot on the invoice.
 */
function calculateInvoice({ priceCents, gstType, gstRatePercent, wantsGst, companyState, customerGstState }) {
  const applyGst = Boolean(wantsGst && customerGstState);

  if (!applyGst) {
    return {
      taxType: 'none',
      cgstRate: 0,
      sgstRate: 0,
      igstRate: 0,
      subtotalCents: priceCents,
      cgstCents: 0,
      sgstCents: 0,
      igstCents: 0,
      totalCents: priceCents,
    };
  }

  const rate = Number(gstRatePercent) || 0;
  let subtotalCents;
  let taxCents;

  if (gstType === 'inclusive') {
    // priceCents is the final, tax-included amount.
    subtotalCents = Math.round(priceCents / (1 + rate / 100));
    taxCents = priceCents - subtotalCents;
  } else {
    subtotalCents = priceCents;
    taxCents = Math.round(priceCents * (rate / 100));
  }

  const sameState = statesMatch(companyState, customerGstState);
  const totalCents = subtotalCents + taxCents;

  if (sameState) {
    const cgstCents = Math.round(taxCents / 2);
    const sgstCents = taxCents - cgstCents; // avoids a 1-cent rounding gap
    return {
      taxType: 'cgst_sgst',
      cgstRate: rate / 2,
      sgstRate: rate / 2,
      igstRate: 0,
      subtotalCents,
      cgstCents,
      sgstCents,
      igstCents: 0,
      totalCents,
    };
  }

  return {
    taxType: 'igst',
    cgstRate: 0,
    sgstRate: 0,
    igstRate: rate,
    subtotalCents,
    cgstCents: 0,
    sgstCents: 0,
    igstCents: taxCents,
    totalCents,
  };
}

module.exports = { calculateInvoice };
