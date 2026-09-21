# Sentruo Windows Agent

Source for `assets/sentruo-agent-windows.exe`, the Windows counterpart to the
Linux agent (`assets/agent.sh`). It's the file linked from the "Install
Monitoring Agent" dialog shown when a Windows server is added.

## What it does

- **First run** (no saved config): prompts for the *Gateway Address* and
  *Server Key* shown in that dialog, copies itself to
  `%ProgramData%\Sentruo\sentruo-agent-windows.exe`, registers a Scheduled
  Task (`SentruoAgent`) to run every minute, and submits one reading
  immediately. Must be run as Administrator (needed to install the task to
  run as SYSTEM, mirroring the Linux installer's root requirement).
- **Every run after that** (the Scheduled Task invoking the installed copy):
  reads the saved config, collects one reading via `systeminformation`, and
  POSTs it to the gateway. No prompts, no window lingering.
- `sentruo-agent-windows.exe --uninstall`: removes the scheduled task and
  `%ProgramData%\Sentruo`.

## Wire protocol

Identical to `assets/agent.sh` / `agent.php`: fields are packed as
consecutive `{field}value{/field}` tags, base64url-encoded, and POSTed as
`data=<payload>` with an HMAC-SHA256 signature over `<timestamp>.<payload>`
in `X-Agent-Timestamp` / `X-Agent-Signature` headers (keyed by the optional
deployment HMAC secret, else the server key). Field names/shapes match what
`Server::quickStats()` and `template/pages/servers/manage-windows.php`
already expect on the PHP side (`cpu_load`, `filesystems`, `net_stats`,
`processes`, etc., as JSON-encoded tag values) — that server-side parsing
already existed; only the client was missing.

## Building

```
cd windows-agent
npm install
npx @yao-pkg/pkg . --target node18-win-x64 --compress GZip --output ../assets/sentruo-agent-windows.exe
```

(`@yao-pkg/pkg` is the maintained fork of the now-archived `pkg`; it bundles
the script and a Node 18 runtime into one native PE executable, cross-built
from any OS — no Windows or Wine needed to build it, only to run it.)

The output is ~40MB because it embeds the whole Node runtime; that's the
cost of a single, dependency-free .exe end users can just double-click.

## Not yet done

Alerting (`Server::processAll()`) only evaluates the Linux branch today —
Windows servers report data and render fine, but CPU/RAM/disk/etc. alerts
won't fire for them yet. Out of scope for this change; flagging for later.
