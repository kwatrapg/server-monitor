#!/usr/bin/env node
// Bootstrap the first admin user (or add another one).
// Usage: npm run create-admin -- --username=admin --email=admin@example.com --password=changeme

const bcrypt = require('bcryptjs');
const readline = require('readline');
const db = require('../src/db');

function parseArgs() {
  const args = {};
  for (const arg of process.argv.slice(2)) {
    const match = arg.match(/^--([^=]+)=(.*)$/);
    if (match) args[match[1]] = match[2];
  }
  return args;
}

function prompt(question) {
  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  return new Promise((resolve) => rl.question(question, (answer) => {
    rl.close();
    resolve(answer.trim());
  }));
}

async function main() {
  const args = parseArgs();
  const username = args.username || (await prompt('Username: '));
  const email = args.email || (await prompt('Email: '));
  const password = args.password || (await prompt('Password (min 12 chars): '));

  if (!username || !email || !password) {
    console.error('username, email and password are all required.');
    process.exit(1);
  }
  if (password.length < 12) {
    console.error('Password must be at least 12 characters.');
    process.exit(1);
  }

  const hash = bcrypt.hashSync(password, 12);

  try {
    db.prepare('INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)').run(
      username,
      email,
      hash
    );
    console.log(`Admin user "${username}" created.`);
  } catch (err) {
    if (String(err.message).includes('UNIQUE')) {
      console.error('A user with that username or email already exists.');
    } else {
      console.error(err.message);
    }
    process.exit(1);
  }
}

main();
