// Indian GST state codes — the first two digits of a GSTIN encode the state
// it was registered in. Used to verify a GST number's state actually matches
// the customer's billing address state, rather than trusting a free-text
// field that could just be typed to match.
const GST_STATE_CODES = {
  '01': 'Jammu and Kashmir',
  '02': 'Himachal Pradesh',
  '03': 'Punjab',
  '04': 'Chandigarh',
  '05': 'Uttarakhand',
  '06': 'Haryana',
  '07': 'Delhi',
  '08': 'Rajasthan',
  '09': 'Uttar Pradesh',
  '10': 'Bihar',
  '11': 'Sikkim',
  '12': 'Arunachal Pradesh',
  '13': 'Nagaland',
  '14': 'Manipur',
  '15': 'Mizoram',
  '16': 'Tripura',
  '17': 'Meghalaya',
  '18': 'Assam',
  '19': 'West Bengal',
  '20': 'Jharkhand',
  '21': 'Odisha',
  '22': 'Chhattisgarh',
  '23': 'Madhya Pradesh',
  '24': 'Gujarat',
  '25': 'Daman and Diu',
  '26': 'Dadra and Nagar Haveli and Daman and Diu',
  '27': 'Maharashtra',
  '28': 'Andhra Pradesh (Old)',
  '29': 'Karnataka',
  '30': 'Goa',
  '31': 'Lakshadweep',
  '32': 'Kerala',
  '33': 'Tamil Nadu',
  '34': 'Puducherry',
  '35': 'Andaman and Nicobar Islands',
  '36': 'Telangana',
  '37': 'Andhra Pradesh',
  '38': 'Ladakh',
  '97': 'Other Territory',
  '99': 'Centre Jurisdiction',
};

const GSTIN_PATTERN = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[A-Z0-9]{1}$/;

function isValidGstFormat(gstNumber) {
  return GSTIN_PATTERN.test(String(gstNumber || '').toUpperCase().trim());
}

/** Returns the registered state name for a GSTIN's embedded state code, or null if unknown. */
function stateForGst(gstNumber) {
  const code = String(gstNumber || '').trim().slice(0, 2);
  return GST_STATE_CODES[code] || null;
}

/** Loose match: case/whitespace-insensitive, and tolerant of "and"/"&" and extra spacing. */
function statesMatch(a, b) {
  const normalize = (s) =>
    String(s || '')
      .toLowerCase()
      .replace(/&/g, 'and')
      .replace(/[^a-z]+/g, ' ')
      .trim();
  return normalize(a) === normalize(b);
}

module.exports = { GST_STATE_CODES, isValidGstFormat, stateForGst, statesMatch };
