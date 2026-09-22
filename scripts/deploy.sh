#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
#  BK POIN & SP · Deploy Script (DEV → VPS)
#  Untuk beta tester: jalankan dari Mac/terminal Anda sendiri.
#
#  Cara pakai:
#    1) Edit bagian KONFIGURASI di bawah (atau bikin file .deploy.env)
#    2) chmod +x scripts/deploy.sh
#    3) ./scripts/deploy.sh              ← deploy normal
#       ./scripts/deploy.sh --seed       ← deploy + seed database (pertama kali)
#       ./scripts/deploy.sh --dry-run    ← lihat apa yg akan di-sync tanpa eksekusi
# ═══════════════════════════════════════════════════════════════════════
set -euo pipefail

# ── KONFIGURASI (boleh di-override oleh .deploy.env) ──────────────────
VPS_HOST="${VPS_HOST:-}"          # contoh: 103.2xx.1xx.5x atau bk.sekolahanda.sch.id
VPS_USER="${VPS_USER:-deployer}"  # user SSH (non-root, tapi punya akses folder)
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/bk-poin-sp}"
SSH_PORT="${SSH_PORT:-22}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/deploy_key}"  # path ke private key lokal

# ── FLAG parsing ───────────────────────────────────────────────────────
RUN_SEED=0
DRY_RUN=0
for arg in "$@"; do
  case "$arg" in
    --seed)    RUN_SEED=1 ;;
    --dry-run) DRY_RUN=1 ;;
    *) echo "Argumen tidak dikenal: $arg"; exit 1 ;;
  esac
done

# ── Baca config dari .deploy.env kalau ada (jangan di-commit!) ─────────
if [[ -f .deploy.env ]]; then
  echo "📋 Membaca konfigurasi dari .deploy.env ..."
  source .deploy.env
fi

# ── Validasi ───────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[0;33m'; NC='\033[0m'
log()  { echo -e "${GREEN}✔${NC} $1"; }
warn() { echo -e "${YELLOW}⚠${NC} $1"; }
err()  { echo -e "${RED}✖${NC} $1"; }

if [[ -z "$VPS_HOST" ]]; then err "VPS_HOST kosong! Isi di script ini atau di .deploy.env"; exit 1; fi
if [[ ! -f "$SSH_KEY" ]]; then err "SSH key tidak ditemukan: $SSH_KEY"; err "Buat dulu: ssh-keygen -t ed25519 -f $SSH_KEY"; exit 1; fi
if ! command -v rsync >/dev/null 2>&1; then err "rsync belum terinstall (brew install rsync)"; exit 1; fi

# ── Pastikan kita di root repo ─────────────────────────────────────────
if [[ ! -f public/index.php || ! -f core/Router.php ]]; then
  err "Jalankan script ini dari root repo (folder yg ada public/ & core/)"
  exit 1
fi

log "Target  : $VPS_USER@$VPS_HOST:$DEPLOY_PATH"
log "SSH key : $SSH_KEY"
[[ $RUN_SEED -eq 1 ]] && warn "Mode: DEPLOY + SEED (hati-hati, untuk setup pertama!)"
[[ $DRY_RUN  -eq 1 ]] && warn "Mode: DRY-RUN (tidak ada perubahan nyata)"
echo

# ── 1) Syntax check lokal sebelum kirim apapun ────────────────────────
log "Menjalankan syntax check PHP lokal ..."
ERRORS=0
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null 2>&1 || { err "Syntax error: $f"; ERRORS=1; }
done < <(find . -name '*.php' -not -path './vendor/*' -not -path './.git/*' -print0)
[[ $ERRORS -eq 1 ]] && exit 1
log "Semua file PHP lolos syntax check"

# ── 2) Cegah kebocoran secret ──────────────────────────────────────────
if [[ -n "$(git ls-files .env 2>/dev/null)" ]]; then
  err ".env ter-commit! Jalankan: git rm --cached .env && commit"
  exit 1
fi
log "Tidak ada secret yang akan ikut ter-sync"

# ── 3) rsync sync ke server ────────────────────────────────────────────
RSYNC_FLAGS=(-azP --delete
  --exclude='.git/'
  --exclude='.github/'
  --exclude='.env'
  --exclude='.env.*'
  --exclude='.deploy.env'
  --exclude='storage/logs/'
  --exclude='database/*.sqlite'
  --exclude='database/*.sqlite-*'
  --exclude='/node_modules/'
  --exclude='/vendor/'
  --exclude='.DS_Store'
  --exclude='scripts/'
  -e "ssh -i $SSH_KEY -p $SSH_PORT -o StrictHostKeyChecking=accept-new"
)

if [[ $DRY_RUN -eq 1 ]]; then
  RSYNC_FLAGS+=("--dry-run")
  echo -e "${YELLOW}▶ DRY RUN rsync:${NC}"
  rsync "${RSYNC_FLAGS[@]}" ./ "$VPS_USER@$VPS_HOST:$DEPLOY_PATH/"
  echo; log "Dry-run selesai — tidak ada perubahan dilakukan"; exit 0
fi

echo -e "${GREEN}▶ Sync file ke server...${NC}"
rsync "${RSYNC_FLAGS[@]}" ./ "$VPS_USER@$VPS_HOST:$DEPLOY_PATH/"
log "Sync file selesai"

# ── 4) Post-deploy remote (permission, cek .env, DB, seed opsional) ───
log "Menjalankan post-deploy di server..."
SEED_FLAG="no"; [[ $RUN_SEED -eq 1 ]] && SEED_FLAG="yes"

ssh -i "$SSH_KEY" -p "$SSH_PORT" "$VPS_USER@$VPS_HOST" bash -s "$DEPLOY_PATH" "$SEED_FLAG" << 'REMOTE_SCRIPT'
set -euo pipefail
DEPLOY_PATH="$1"
RUN_SEED="$2"
cd "$DEPLOY_PATH"

echo "📁 Pastikan folder runtime writable..."
mkdir -p storage/logs
chmod -R 775 storage
chown -R www-data:www-data storage 2>/dev/null || true

if [ ! -f .env ]; then
  echo "⚠️  .env TIDAK ADA di server — aplikasi akan error 500!"
  echo "    Buat sekarang: cp .env.example .env && nano .env"
  exit 1
fi
echo "✅ .env ditemukan"

echo "🔌 Cek koneksi database..."
php -r '
  require "core/Database.php";
  try {
    $pdo = Database::getConnection();
    echo "✅ Koneksi MySQL OK\n";
  } catch (Throwable $e) {
    echo "🚫 Koneksi DB gagal: " . $e->getMessage() . "\n";
    exit 1;
  }
'

if [ "$RUN_SEED" = "yes" ]; then
  echo "🌱 Menjalankan database seed (setup awal)..."
  php database/seed.php
fi

echo "🎉 Deploy selesai!"
REMOTE_SCRIPT

echo
log "=========================================="
log "  DEPLOY BERHASIL! 🎉"
log "  Cek: http://$VPS_HOST"
log "=========================================="
