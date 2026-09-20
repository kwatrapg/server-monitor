// Curated list of currencies offered in dropdowns (Settings > General,
// Plans). Not exhaustive — add more here if you need one that's missing.
const CURRENCIES = [
  { code: 'USD', label: 'USD — US Dollar ($)' },
  { code: 'INR', label: 'INR — Indian Rupee (₹)' },
  { code: 'EUR', label: 'EUR — Euro (€)' },
  { code: 'GBP', label: 'GBP — British Pound (£)' },
  { code: 'AUD', label: 'AUD — Australian Dollar (A$)' },
  { code: 'CAD', label: 'CAD — Canadian Dollar (C$)' },
  { code: 'SGD', label: 'SGD — Singapore Dollar (S$)' },
  { code: 'AED', label: 'AED — UAE Dirham' },
  { code: 'JPY', label: 'JPY — Japanese Yen (¥)' },
];

const CURRENCY_CODES = CURRENCIES.map((c) => c.code);

module.exports = { CURRENCIES, CURRENCY_CODES };
