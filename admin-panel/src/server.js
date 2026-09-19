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
const apiRoutes = require('./routes/api');

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

app.use(ipAllowlist);

app.use(
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

app.use(csrfToken);

app.use((req, res, next) => {
  res.locals.isAuthed = Boolean(req.session && req.session.adminUserId);
  res.locals.username = req.session ? req.session.username : null;
  next();
});

app.use('/', authRoutes);
app.use('/', dashboardRoutes);
app.use('/plans', planRoutes);
app.use('/customers', customerRoutes);
app.use('/licenses', licenseRoutes);

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
