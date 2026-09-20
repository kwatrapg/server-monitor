// Admins always enter/see a final amount (e.g. 100 = $100.00 or ₹100.00,
// depending on currency) — cents are strictly a storage/precision detail.
function toCents(amount) {
  return amount !== undefined && amount !== null && amount !== ''
    ? Math.round(parseFloat(amount) * 100)
    : null;
}

function fromCents(cents) {
  return cents == null ? '' : (cents / 100).toFixed(2);
}

module.exports = { toCents, fromCents };
