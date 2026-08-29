#!/usr/bin/env bash
# Sentruo — lightweight static security/quality gate. Run in CI and pre-deploy.
set -uo pipefail
cd "$(dirname "$0")/.."
fail=0
say() { printf '%s\n' "$*"; }
bad() { printf '  FAIL: %s\n' "$*"; fail=1; }

say "== php -l on every PHP file =="
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null 2>&1 || bad "syntax error: $f"
done < <(find . -path ./vendor -prune -o -name '*.php' -print0)

say "== no phpinfo() in the tree =="
grep -RnI --include='*.php' --exclude-dir=vendor 'phpinfo\s*(' . && bad "phpinfo() found" || true

say "== no debug endpoints / stray a.php =="
[ -f a.php ] && bad "a.php present" || true

say "== no request data flowing straight into require/include =="
grep -RnE --include='*.php' --exclude-dir=vendor \
  '(require|include)(_once)?\s*\(?\s*.*\$_(GET|POST|REQUEST|COOKIE)' . && bad "dynamic include from request input" || true

say "== no rand()/mt_rand() for security tokens =="
grep -RnE --include='*.php' --exclude-dir=vendor \
  '(resetkey|serverkey|pagekey|token|secret|csrf)[^;]*\b(rand|mt_rand)\s*\(' . && bad "weak PRNG near a token" || true

say "== no sha1()/md5() used as a password hash =="
grep -RnE --include='*.php' --exclude-dir=vendor \
  '(password|passwd|pwd)[^;]*\b(sha1|md5)\s*\(|\b(sha1|md5)\s*\(\s*\$(password|pass|pwd)' . && bad "sha1/md5 password hashing" || true

say "== no committed .sql dumps outside database/ =="
find . -path ./vendor -prune -o -path ./database -prune -o -name '*.sql' -print | grep . && bad ".sql outside database/" || true

say "== no plaintext secrets in tracked config =="
grep -RnE --include='*.php' --exclude-dir=vendor \
  '(password|secret|api_?key)\s*=>\s*["'\''][^"'\''$]{6,}["'\'']' config.php 2>/dev/null && bad "literal secret in config.php" || true

say "== .env is git-ignored =="
git check-ignore -q .env || bad ".env is NOT git-ignored"

say "== composer audit =="
if command -v composer >/dev/null 2>&1; then
  composer audit --no-interaction || bad "composer audit reported advisories"
else
  say "  (composer not installed — skipped)"
fi

if [ "$fail" -ne 0 ]; then say ""; say "CI CHECKS FAILED"; exit 1; fi
say ""; say "All checks passed."
