#!/bin/bash
# Rebuilds assets/install.sh and assets/agent.sh as self-decrypting stubs
# from the plaintext sources in assets/install.sh-bak and assets/agent.sh-bak.
#
# Edit the -bak files (plain bash), then re-run this to regenerate the
# deployed files. Each stub embeds a random passphrase and its own
# AES-256-CBC encrypted body, so `cat`/`less` on the deployed .sh files
# shows only ciphertext - not a strong cryptographic barrier (the key
# ships in the same file), but it stops casual reading of the source.
set -euo pipefail

ASSETS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../assets" && pwd)"

make_stub() {
	local src="$1" out="$2" pass
	pass=$(openssl rand -base64 32)
	local ciphertext
	ciphertext=$(openssl enc -aes-256-cbc -pbkdf2 -iter 100000 -salt -a -A -pass "pass:${pass}" -in "$src")

	cat > "$out" <<STUB
#!/bin/bash
_p='${pass}'
_b='${ciphertext}'
_s=\$(printf '%s' "\$_b" | openssl enc -aes-256-cbc -d -pbkdf2 -iter 100000 -a -A -pass "pass:\$_p" 2>/dev/null) || { echo "corrupt agent payload" >&2; exit 1; }
eval "\$_s"
STUB
	chmod 755 "$out"
}

make_stub "$ASSETS_DIR/install.sh-bak" "$ASSETS_DIR/install.sh"
make_stub "$ASSETS_DIR/agent.sh-bak" "$ASSETS_DIR/agent.sh"

echo "Rebuilt $ASSETS_DIR/install.sh and $ASSETS_DIR/agent.sh"
