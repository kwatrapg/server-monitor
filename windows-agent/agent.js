#!/usr/bin/env node
/**
 * Sentruo monitoring agent for Windows.
 *
 * First run (no saved config): interactively prompts for the Gateway
 * Address and Server Key shown in the "Install Monitoring Agent" dialog
 * when a Windows server is added, copies itself into a stable install
 * location, registers a scheduled task to run itself every minute, then
 * submits one reading immediately.
 *
 * Subsequent runs (the scheduled task invoking the installed copy): read
 * the saved config, collect one reading, sign and submit it, and exit.
 *
 * Wire protocol matches the Linux agent (assets/agent.sh) exactly: fields
 * are packed as consecutive "{field}value{/field}" tags, base64url-encoded,
 * and POSTed as `data=<payload>` with an HMAC-SHA256 signature (over
 * "<timestamp>.<payload>", keyed by the deployment HMAC secret, falling
 * back to the per-server key) in X-Agent-Timestamp / X-Agent-Signature
 * headers. See agent.php.
 */

'use strict';

const fs = require('fs');
const os = require('os');
const path = require('path');
const http = require('http');
const https = require('https');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const readline = require('readline');
const si = require('systeminformation');

const AGENT_VERSION = '1.0';
const INSTALL_DIR = path.join(process.env.ProgramData || 'C:\\ProgramData', 'Sentruo');
const CONFIG_PATH = path.join(INSTALL_DIR, 'config.json');
const LOG_PATH = path.join(INSTALL_DIR, 'agent.log');
const INSTALLED_EXE = path.join(INSTALL_DIR, 'sentruo-agent-windows.exe');
const TASK_NAME = 'SentruoAgent';

function log(line) {
  const msg = `[${new Date().toISOString()}] ${line}`;
  console.log(msg);
  try { fs.appendFileSync(LOG_PATH, msg + os.EOL); } catch (_) { /* best effort */ }
}

function prompt(question) {
  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  return new Promise((resolve) => rl.question(question, (answer) => {
    rl.close();
    resolve(answer.trim());
  }));
}

function isAdmin() {
  try {
    // Standard trick: only an elevated process can query the SYSTEM session list.
    execFileSync('net', ['session'], { stdio: 'ignore' });
    return true;
  } catch (_) {
    return false;
  }
}

function loadConfig() {
  try {
    return JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'));
  } catch (_) {
    return null;
  }
}

function saveConfig(config) {
  fs.mkdirSync(INSTALL_DIR, { recursive: true });
  fs.writeFileSync(CONFIG_PATH, JSON.stringify(config, null, 2), { mode: 0o600 });
}

async function runSetup() {
  console.log('--------------------------------');
  console.log(' Sentruo Windows Agent Setup');
  console.log('--------------------------------');
  console.log('Enter the values shown in the app when you add this Windows server');
  console.log('("Install Monitoring Agent" dialog, step 3).\n');

  if (!isAdmin()) {
    console.log('This needs to run as Administrator the first time, so it can install');
    console.log('itself to run automatically. Right-click the exe and choose');
    console.log('"Run as administrator", then try again.\n');
    process.exitCode = 1;
    await prompt('Press Enter to exit...');
    return;
  }

  let gateway = '';
  while (!/^https?:\/\/.+/i.test(gateway)) {
    gateway = await prompt('Gateway Address: ');
  }

  let serverKey = '';
  while (!/^[A-Za-z0-9]{16,64}$/.test(serverKey)) {
    serverKey = await prompt('Server Key: ');
  }

  const hmacSecret = await prompt('Deployment HMAC secret (leave blank if none): ');

  fs.mkdirSync(INSTALL_DIR, { recursive: true });

  // Copy this running exe into the stable install location so the
  // scheduled task keeps working regardless of where it was launched from.
  const runningExe = process.execPath;
  if (path.resolve(runningExe).toLowerCase() !== path.resolve(INSTALLED_EXE).toLowerCase()) {
    fs.copyFileSync(runningExe, INSTALLED_EXE);
  }

  saveConfig({ gateway, serverKey, hmacSecret: hmacSecret || undefined });

  try {
    execFileSync('schtasks', [
      '/create', '/tn', TASK_NAME,
      '/tr', `"${INSTALLED_EXE}"`,
      '/sc', 'minute', '/mo', '1',
      '/ru', 'SYSTEM', '/rl', 'highest', '/f',
    ], { stdio: 'ignore' });
  } catch (err) {
    console.log('Could not register the scheduled task automatically: ' + err.message);
    console.log('Create it manually so the agent keeps reporting every minute:');
    console.log(`  schtasks /create /tn "${TASK_NAME}" /tr "\\"${INSTALLED_EXE}\\"" /sc minute /mo 1 /ru SYSTEM /rl highest /f`);
  }

  console.log('\nInstalled to: ' + INSTALLED_EXE);
  console.log('Scheduled task "' + TASK_NAME + '" set to run every minute.');
  console.log('Submitting first reading now...\n');

  await report(loadConfig());
  console.log('\nDone. The agent will keep reporting automatically. You can close this window.');
  await prompt('Press Enter to exit...');
}

// --- metrics ---------------------------------------------------------

function tag(name, value) {
  return `{${name}}${value == null ? '' : value}{/${name}}`;
}

async function collect() {
  const [osInfo, cpu, currentLoad, mem, time, fsSize, diskLayout,
    networkInterfaces, networkStats, defaultInterface, processes,
    systemInfo, baseboard, bios, inetLatency] = await Promise.all([
    si.osInfo(), si.cpu(), si.currentLoad(), si.mem(), si.time(),
    si.fsSize(), si.diskLayout(), si.networkInterfaces(), si.networkStats(),
    si.networkInterfaceDefault().catch(() => ''),
    si.processes(), si.system().catch(() => ({})),
    si.baseboard().catch(() => ({})), si.bios().catch(() => ({})),
    si.inetLatency().catch(() => ''),
  ]);

  const swapUsage = (mem.swaptotal || 0) - (mem.swapfree || 0);

  const netStats = (networkStats || []).map((n) => ({
    iface: n.iface, rx: n.rx_bytes, tx: n.tx_bytes, rx_sec: n.rx_sec, tx_sec: n.tx_sec,
  }));

  const netIfaces = (networkInterfaces || []).map((n) => ({
    iface: n.iface, ip4: n.ip4, ip6: n.ip6, mac: n.mac,
  }));

  const filesystems = (fsSize || []).map((f) => ({
    fs: f.fs, type: f.type, size: f.size, used: f.used, use: f.use,
  }));

  const disks = (diskLayout || []).map((d) => ({
    type: d.type, name: d.name, size: d.size, serialNum: d.serialNum,
  }));

  const procList = (processes.list || []).map((p) => ({
    pid: p.pid, name: p.name, command: p.command, pcpus: p.pcpus, pcpuu: p.pcpuu,
    pmem: p.pmem, started: p.started,
  }));

  return {
    agent_version: AGENT_VERSION,
    hostname: osInfo.hostname,
    kernel: osInfo.kernel,
    time: Math.floor(Date.now() / 1000),
    os: `${osInfo.distro} ${osInfo.release}`.trim(),
    os_arch: osInfo.arch,
    cpu_model: cpu.brand,
    cpu_cores: cpu.cores,
    cpu_speed: cpu.speed,
    cpu_load: JSON.stringify({ currentload: currentLoad.currentLoad, avgload: currentLoad.avgLoad }),
    ram_total: mem.total,
    ram_free: mem.free,
    ram_usage: mem.used,
    swap_total: mem.swaptotal,
    swap_usage: swapUsage,
    default_interface: defaultInterface,
    net_interfaces: JSON.stringify(netIfaces),
    net_stats: JSON.stringify(netStats),
    filesystems: JSON.stringify(filesystems),
    disk_layout: JSON.stringify(disks),
    system: JSON.stringify({ manufacturer: systemInfo.manufacturer || '', model: systemInfo.model || '' }),
    baseboard: JSON.stringify({ manufacturer: baseboard.manufacturer || '', model: baseboard.model || '' }),
    bios: JSON.stringify({ vendor: bios.vendor || '', version: bios.version || '', releaseDate: bios.releaseDate || '' }),
    ping_latency: inetLatency,
    uptime: time.uptime,
    processes: JSON.stringify({ list: procList }),
  };
}

function buildPayload(fields, serverKey, gateway) {
  let body = '';
  body += tag('agent_version', fields.agent_version);
  body += tag('serverkey', serverKey);
  body += tag('gateway', gateway);
  for (const key of Object.keys(fields)) {
    if (key === 'agent_version') continue;
    body += tag(key, fields[key]);
  }
  return body;
}

function postSigned(gateway, payloadB64url, signingKey) {
  return new Promise((resolve, reject) => {
    const ts = Math.floor(Date.now() / 1000).toString();
    const signature = crypto.createHmac('sha256', signingKey)
      .update(`${ts}.${payloadB64url}`)
      .digest('hex');

    const body = `data=${payloadB64url}`;
    const url = new URL(gateway);
    const transport = url.protocol === 'https:' ? https : http;

    const req = transport.request(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(body),
        'X-Agent-Timestamp': ts,
        'X-Agent-Signature': signature,
      },
      timeout: 30000,
    }, (res) => {
      let responseBody = '';
      res.on('data', (chunk) => { responseBody += chunk; });
      res.on('end', () => resolve({ status: res.statusCode, body: responseBody }));
    });

    req.on('timeout', () => req.destroy(new Error('Request timed out')));
    req.on('error', reject);
    req.write(body);
    req.end();
  });
}

async function report(config) {
  if (!config || !config.gateway || !config.serverKey) {
    log('No configuration found. Run this exe once interactively first.');
    process.exitCode = 1;
    return;
  }

  try {
    const fields = await collect();
    const rawPayload = buildPayload(fields, config.serverKey, config.gateway);
    const payloadB64url = Buffer.from(rawPayload, 'utf8').toString('base64')
      .replace(/\+/g, '-').replace(/\//g, '_');
    const signingKey = config.hmacSecret || config.serverKey;

    const res = await postSigned(config.gateway, payloadB64url, signingKey);
    if (res.status === 204 || res.status === 200) {
      log(`Reading submitted OK (HTTP ${res.status}).`);
    } else {
      log(`Gateway rejected the reading: HTTP ${res.status} ${res.body}`);
    }
  } catch (err) {
    log('Failed to submit reading: ' + err.message);
  }
}

function runUninstall() {
  if (!isAdmin()) {
    console.log('Run as Administrator to uninstall (needed to remove the scheduled task).');
    process.exitCode = 1;
    return;
  }
  try {
    execFileSync('schtasks', ['/delete', '/tn', TASK_NAME, '/f'], { stdio: 'ignore' });
  } catch (_) { /* task may not exist */ }
  try { fs.rmSync(INSTALL_DIR, { recursive: true, force: true }); } catch (_) { /* best effort */ }
  console.log('Sentruo agent uninstalled: scheduled task removed and ' + INSTALL_DIR + ' deleted.');
}

// --- entry point -------------------------------------------------------

async function main() {
  if (process.argv.includes('--uninstall')) {
    runUninstall();
    return;
  }

  const config = loadConfig();
  if (config) {
    await report(config);
  } else {
    await runSetup();
  }
}

main();
