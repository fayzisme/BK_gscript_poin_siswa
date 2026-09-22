# ⚙️ Panduan Konfigurasi CI/CD GitHub Actions — BK Poin & SP

Dokumen ini adalah panduan langkah demi langkah untuk mengonfigurasi dan mengelola **Pipeline CI/CD (Continuous Integration & Continuous Deployment)** menggunakan **GitHub Actions** yang terhubung langsung ke VPS produksi/beta Anda.

---

## 📋 Daftar Isi
1. [Arsitektur & Alur Kerja (Pipeline)](#1-arsitektur--alur-kerja-pipeline)
2. [Langkah 1: Persiapan SSH Key Khusus (Dedicated Deploy Key)](#2-langkah-1-persiapan-ssh-key-khusus-dedicated-deploy-key)
3. [Langkah 2: Daftarkan Public Key di VPS](#3-langkah-2-daftarkan-public-key-di-vps)
4. [Langkah 3: Konfigurasi GitHub Secrets](#4-langkah-3-konfigurasi-github-secrets)
5. [Langkah 4: Struktur File Workflow](#5-langkah-4-struktur-file-workflow)
6. [Fitur Unggulan Pipeline](#6-fitur-unggulan-pipeline)
7. [Troubleshooting & Perintah Pemeliharaan](#7-troubleshooting--perintah-pemeliharaan)

---

## 1. Arsitektur & Alur Kerja (Pipeline)

Project ini memiliki dua workflow GitHub Actions di dalam folder `.github/workflows/`:
1. **`ci.yml` (Continuous Integration)**: Berjalan otomatis setiap `push` atau `pull_request` ke branch `main` / `develop`. Tugasnya:
   - Lint sintaks PHP (`php -l`) di seluruh file project.
   - Audit keamanan (memastikan `.env` dan file sensitif tidak ikut ter-commit).
   - Melaporkan kegagalan via notifikasi Discord (Hermes gateway).
2. **`deploy.yml` (Continuous Deployment)**: Berjalan otomatis setiap `push` ke `main` atau `develop` (dan bisa di-trigger manual). Tugasnya:
   - Setup SSH aman menggunakan dedicated deploy key.
   - Backup release lama secara otomatis (`.prev`).
   - Sync file via `rsync` (mengabaikan `.env`, `storage/logs/`, dll.).
   - Set permission folder `storage/` (`www-data:www-data`, chmod `775`).
   - **Health Check** otomatis ke endpoint `/health` (retry 5x).
   - **Auto-Rollback** jika health check gagal (mengembalikan versi `.prev`).
   - Kirim notifikasi sukses/gagal ke Discord (`#vps-ops`) via Hermes.

---

## 2. Langkah 1: Persiapan SSH Key Khusus (Dedicated Deploy Key)

> 💡 **Prinsip Keamanan:** Jangan pernah memakai *master key* (`~/.ssh/id_rsa`) Anda untuk GitHub Actions. Selalu gunakan kunci khusus yang diisolasi hanya untuk deployment repository ini.

Jalankan perintah ini di komputer lokal (terminal):

```bash
# Buat dedicated deploy key baru (ED25519, tanpa passphrase)
ssh-keygen -t ed25519 -f ~/.ssh/bk_deploy_key -N "" -C "github-actions-deploy@bk-poin-sp"
```

Perintah ini akan menghasilkan dua file:
- `~/.ssh/bk_deploy_key` (🔑 **Private Key** — rahasia, akan dimasukkan ke GitHub Secrets).
- `~/.ssh/bk_deploy_key.pub` (🔓 **Public Key** — akan dipasang di VPS).

---

## 3. Langkah 2: Daftarkan Public Key di VPS

Salin isi public key ke server VPS Anda agar GitHub Actions diizinkan masuk:

```bash
# Kirim public key ke authorized_keys VPS
cat ~/.ssh/bk_deploy_key.pub | ssh user@vps-ip 'cat >> ~/.ssh/authorized_keys && echo "✓ Key berhasil ditambahkan"'
```

> ⚠️ **Catatan Penting:** Pastikan key ini **tidak** menggunakan `command=` guard di `authorized_keys` (kecuali Anda ingin membatasinya). Workflow rsync dan post-deploy script memerlukan akses shell bebas untuk menjalankan `rsync`, `mkdir`, `chown`, dan `php`.

---

## 4. Langkah 3: Konfigurasi GitHub Secrets

Agar GitHub Actions dapat mengakses VPS Anda dengan aman, daftarkan 3 Secret berikut di repository GitHub Anda:

1. Buka repository Anda di GitHub: **`https://github.com/fayzisme/BK_gscript_poin_siswa`**
2. Klik **Settings** ➔ **Secrets and variables** ➔ **Actions**.
3. Klik **New repository secret** dan tambahkan tiga secret berikut:

| Nama Secret | Nilai / Contoh | Keterangan |
|---|---|---|
| `VPS_HOST` | `43.173.7.25` (atau domain Anda) | Alamat IP publik VPS |
| `VPS_USER` | `ubuntu` (atau user deployer Anda) | User SSH untuk login ke VPS |
| `VPS_SSH_PRIVATE_KEY` | *(isi seluruh isi file `~/.ssh/bk_deploy_key`)* | Kunci rahasia private key |

Cara cepat via GitHub CLI (opsional):
```bash
gh secret set VPS_HOST --body "43.173.7.25"
gh secret set VPS_USER --body "ubuntu"
gh secret set VPS_SSH_PRIVATE_KEY < ~/.ssh/bk_deploy_key
```

---

## 5. Langkah 4: Struktur File Workflow

### A. `.github/workflows/ci.yml` (Lint & Pre-Deploy Check)
```yaml
name: 🔍 CI — Lint & Pre-Deploy Check

on:
  pull_request:
    branches: [main, develop]
  push:
    branches: [main, develop]

jobs:
  lint:
    name: PHP Syntax Lint & Security Audit
    runs-on: ubuntu-latest

    steps:
      - name: 📥 Checkout code
        uses: actions/checkout@v4

      - name: 🐘 Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: 🔍 Syntax lint semua file PHP
        run: |
          ERROR=0
          while IFS= read -r -d '' f; do
            if ! RESULT=$(php -l "$f" 2>&1); then
              echo "❌ $f"
              echo "$RESULT"
              ERROR=1
            fi
          done < <(find . -name '*.php' -not -path './vendor/*' -print0)
          if [ "$ERROR" -eq 1 ]; then exit 1; fi
          echo "✅ Semua file PHP lolos syntax check"

      - name: "Audit: pastikan .env & secret tidak ter-commit"
        run: |
          for f in .env .env.local .env.production database/database.sqlite; do
            if git ls-files --error-unmatch "$f" 2>/dev/null; then
              echo "❌ $f ter-commit!" && exit 1
            fi
          done
          echo "✅ Tidak ada file sensitif yang ter-commit"
```

### B. `.github/workflows/deploy.yml` (CD dengan Health Check & Rollback)
Ringkasan langkah di `deploy.yml`:
1. **Setup SSH**: Menulis private key ke `~/.ssh/deploy_key` dengan permission `600`.
2. **Resolve Target Path**: Branch `main` atau `develop` mengarah ke `/var/www/bk-poin-sp-beta` (atau path production sesuai konfigurasi).
3. **Backup Release**: Menyimpan snapshot folder lama ke `${DEPLOY_PATH}.prev`.
4. **Rsync Sync**: Menyinkronkan file baru ke VPS, mengecualikan `.env`, `storage/logs/`, dll.
5. **Post-Deploy**: Mengatur izin `chown -R www-data:www-data storage` menggunakan `sudo`.
6. **Health Check**: Melakukan HTTP GET ke `${APP_URL}/health` sebanyak 5 kali percobaan.
7. **Auto-Rollback**: Jika health check gagal (bukan HTTP 200), otomatis mengembalikan folder dari `.prev`.
8. **Discord Notification**: Mengirim pesan status via Hermes gateway ke `#vps-ops`.

---

## 6. Fitur Unggulan Pipeline

- 🛡️ **Zero Downtime Protection**: Health check memastikan Nginx & PHP-FPM merespon dengan benar sebelum CD dianggap sukses.
- 🔙 **Auto-Rollback**: Jika update kode menyebabkan error fatal, sistem otomatis rollback dalam hitungan detik.
- 🔒 **Secure Secrets**: Kunci SSH disimpan terenkripsi di GitHub Actions Secrets.
- 📣 **Real-time Alert**: Notifikasi langsung masuk ke channel Discord tim (`#vps-ops`) setiap kali ada push, deploy sukses, atau kegagalan CI.

---

## 7. Troubleshooting & Perintah Pemeliharaan

### A. Cek Status Run CI/CD via CLI
```bash
# Lihat daftar run terakhir
gh run list --limit 5

# Lihat detail run tertentu
gh run view <RUN_ID>
```

### B. Trigger Manual CD (Opsional dengan Seeding)
1. Buka tab **Actions** di GitHub repository Anda.
2. Pilih workflow **🚀 CD — Deploy ke VPS (Beta/Dev Server)**.
3. Klik **Run workflow**, pilih branch (`main`), dan tentukan opsi `should_seed=yes` jika ini setup database pertama.

### C. Rollback Manual di VPS
Jika Anda perlu melakukan rollback manual langsung di server:
```bash
ssh myserver
cd /var/www/bk-poin-sp-beta

# Tukar folder saat ini dengan folder backup .prev
mv /var/www/bk-poin-sp-beta /var/www/bk-poin-sp-beta.broken
mv /var/www/bk-poin-sp-beta.prev /var/www/bk-poin-sp-beta
sudo chown -R www-data:www-data /var/www/bk-poin-sp-beta/storage
echo "✅ Rollback manual selesai"
```
