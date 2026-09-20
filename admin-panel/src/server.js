const path = require('path');
const express = require('express');
const helmet = require('helmet');
const session = require('express-session');

const config = require('./config');
require('./db'); // ensures schema is created before routes load
const SqliteSessionStore = require('./db/sessionStore');

const { ipAllowlist } = require('./middleware/auth');
const { csrfToken } = require('./middleware/csrf');

const authRoutes = require('./routes/auth');
const dashboardRoutes = require('./routes/dashboard');
const planRoutes = require('./routes/plans');
const customerRoutes = require('./routes/customers');
const licenseRoutes = require('./routes/licenses');
const settingsRoutes = require('./routes/settings');
const adminUserRoutes = require('./routes/adminusers');
const apiRoutes = require('./routes/api');
const portalRoutes = require('./routes/portal');

const app = express();

if (config.trustProxy) app.set('trust proxy', 1);

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(
  helmet({
    // In production, HSTS + upgrade-insecure-requests assume this app is served
    // over HTTPS (e.g. behind a TLS-terminating reverse proxy). Off otherwise,
    // so it doesn't force HTTPS while testing over plain HTTP.
    hsts: config.env === 'production',
    contentSecurityPolicy: {
      useDefaults: true,
      directives: {
        upgradeInsecureRequests: config.env === 'production' ? [] : null,
      },
    },
  })
);
app.use(express.urlencoded({ extended: false, limit: '100kb' }));
app.use(express.json({ limit: '100kb' }));
app.use('/public', express.static(path.join(__dirname, 'public')));

// Public license-verification API: no session/CSRF, called by remote installs.
app.use('/api', apiRoutes);

// Customer self-service portal: its own session/cookie/CSRF, deliberately NOT
// behind the admin-only ipAllowlist — customers sign up from anywhere.
app.use('/portal', portalRoutes);

// --- Admin app: everything below requires the admin session + (optionally) an IP allowlist. ---
const adminApp = express.Router();

adminApp.use(ipAllowlist);

adminApp.use(
  session({
    store: new SqliteSessionStore(),
    name: 'sentruo.admin.sid',
    secret: config.sessionSecret,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: config.env === 'production',
      maxAge: 8 * 60 * 60 * 1000, // 8 hours
    },
  })
);

adminApp.use(csrfToken);

adminApp.use((req, res, next) => {
  res.locals.isAuthed = Boolean(req.session && req.session.adminUserId);
  res.locals.username = req.session ? req.session.username : null;
  next();
});

adminApp.use('/', authRoutes);
adminApp.use('/', dashboardRoutes);
adminApp.use('/plans', planRoutes);
adminApp.use('/customers', customerRoutes);
adminApp.use('/licenses', licenseRoutes);
adminApp.use('/settings', settingsRoutes);
adminApp.use('/admin-users', adminUserRoutes);

app.use('/', adminApp);

app.use((req, res) => {
  res.status(404).send('Not found');
});

// eslint-disable-next-line no-unused-vars
app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).send('Internal server error');
});

app.listen(config.port, () => {
  console.log(`Sentruo Admin Panel listening on port ${config.port} (${config.env})`);
});
