// Supported payment gateway providers and the credential fields each one
// needs. This is credential storage + enable/disable only — it does not
// implement checkout or webhook handling.
const PROVIDERS = {
  razorpay: {
    label: 'Razorpay',
    fields: [
      { name: 'key_id', label: 'Key ID' },
      { name: 'key_secret', label: 'Key Secret', secret: true },
      { name: 'webhook_secret', label: 'Webhook Secret', secret: true },
    ],
  },
  stripe: {
    label: 'Stripe',
    fields: [
      { name: 'publishable_key', label: 'Publishable Key' },
      { name: 'secret_key', label: 'Secret Key', secret: true },
      { name: 'webhook_secret', label: 'Webhook Signing Secret', secret: true },
    ],
  },
  paypal: {
    label: 'PayPal',
    fields: [
      { name: 'client_id', label: 'Client ID' },
      { name: 'client_secret', label: 'Client Secret', secret: true },
    ],
  },
  payu: {
    label: 'PayU',
    fields: [
      { name: 'merchant_key', label: 'Merchant Key' },
      { name: 'salt', label: 'Salt', secret: true },
    ],
  },
};

module.exports = { PROVIDERS };
