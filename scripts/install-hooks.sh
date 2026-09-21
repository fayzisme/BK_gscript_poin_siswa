#!/usr/bin/env bash
# Install Git Pre-commit Hook untuk auto-update ARCHITECTURE.md

HOOK_DIR=".git/hooks"
HOOK_FILE="$HOOK_DIR/pre-commit"

if [ ! -d "$HOOK_DIR" ]; then
    mkdir -p "$HOOK_DIR"
fi

cat << 'EOF' > "$HOOK_FILE"
#!/usr/bin/env bash
echo "[Git Hook] Regenerating ARCHITECTURE.md..."
php scripts/update-architecture-docs.php
git add ARCHITECTURE.md
EOF

chmod +x "$HOOK_FILE"
echo "[✓] Git pre-commit hook terpasang! ARCHITECTURE.md akan otomatis ter-update saat 'git commit'."
