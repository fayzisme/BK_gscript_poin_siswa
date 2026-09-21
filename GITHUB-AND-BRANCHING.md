# 🐙 Panduan GitHub, Branching Strategy & Beta Testing

Dokumen ini menjelaskan cara menghubungkan repository lokal ke GitHub dan memanfaatkan **branch strategy** untuk rilis fitur ke beta tester secara aman tanpa mengganggu server produksi.

---

## 🌴 Branching Strategy (Dua Environment)

Aplikasi ini mendukung 2 lingkungan (*environment*) otomatis melalui GitHub Actions:

```text
               (Pengembangan Fitur Baru)
                           │
                           ▼
 📦 Feature Branch ──> [ PR ] ──> 🧪 branch: develop
                                        │
                                        ▼ (Auto-deploy via CI/CD)
                                   🌐 Environment: BETA
                                   Target: /var/www/bk-poin-sp-beta
                                   URL   : https://beta-bk.sekolahanda.sch.id
                                        │
                         (Diuji oleh Guru BK / Beta Tester)
                                        │ (Lolos & Stabil)
                                        ▼
                             [ Merge PR ] ──> 🚀 branch: main
                                                   │
                                                   ▼ (Auto-deploy via CI/CD)
                                              🏢 Environment: PRODUCTION
                                              Target: /var/www/bk-poin-sp
                                              URL   : https://bk.sekolahanda.sch.id
```

| Branch | Tujuan | Trigger Deploy | Path di Server | Subdomain Rekomendasi |
|---|---|---|---|---|
| `develop` | Tempat pengujian fitur baru (Beta) | Auto-deploy tiap `push` | `/var/www/bk-poin-sp-beta` | `beta-bk.sekolahanda.sch.id` |
| `main` | Kode stabil siap pakai (Production) | Auto-deploy tiap `push` | `/var/www/bk-poin-sp` | `bk.sekolahanda.sch.id` |

---

## 1. Menghubungkan Repository ke GitHub (Pertama Kali)

### Step 1: Buat Repository Baru di GitHub
1. Buka [github.com/new](https://github.com/new).
2. Nama Repository: `BK_gscript_poin_siswa` (atau bebas).
3. Set sebagai **Private** (karena aplikasi internal sekolah).
4. **JANGANI** centang *"Initialize this repository with a README/gitignore"* (karena repo lokal kita sudah ada).
5. Klik **Create repository**.

### Step 2: Hubungkan Remote & Push Branch Pertama
Di terminal Mac Anda, jalankan:

```bash
# Pastikan Anda di root folder project
cd /Users/muhammadfaizal/BK_gscript_poin_siswa

# 1. Inisialisasi git (jika belum)
git init

# 2. Hubungkan ke GitHub (ganti USERNAME dengan username GitHub Anda)
git remote add origin git@github.com:USERNAME/BK_gscript_poin_siswa.git

# 3. Rename branch utama jadi main
git branch -M main

# 4. Commit semua file
git add .
git commit -m "feat: initial commit - MVC structure, CI/CD pipeline, health check & docs"

# 5. Push branch main ke GitHub
git push -u origin main

# 6. Buat branch develop untuk beta tester & push
git checkout -b develop
git push -u origin develop
```

---

## 2. Mengkonfigurasi Secret di GitHub (Wajib)

Buka repo di GitHub → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**.

Tambahkan 4 secret berikut:

```text
VPS_HOST             = 103.xxx.xxx.xxx (atau domain VPS Anda)
VPS_USER             = deployer
VPS_SSH_PRIVATE_KEY  = (isi dengan seluruh teks isi ~/.ssh/gh_deploy_key)
```

> ℹ️ `VPS_DEPLOY_PATH` tidak perlu diisi manual jika Anda memakai workflow default — pipeline kami secara otomatis mengarahkan:
> - Branch `main`    → `/var/www/bk-poin-sp`
> - Branch `develop` → `/var/www/bk-poin-sp-beta`

---

## 3. Workflow Pengoperasian Sehari-hari

### 🟢 Skenario A: Mengembangkan Fitur Baru
```bash
# 1. Pindah ke branch develop & tarik versi terbaru
git checkout develop
git pull origin develop

# 2. Buat branch fitur baru (contoh: fitur cetak rekap per kelas)
git checkout -b feature/rekap-kelas

# 3. Kerjakan kodingan ...
# 4. Commit & push
git add .
git commit -m "feat: tambah filter cetak rekap per kelas"
git push -u origin feature/rekap-kelas

# 5. Buka GitHub → Buat Pull Request (PR) dari feature/rekap-kelas ke branch develop
```
Setelah PR di-merge ke `develop`, GitHub Actions akan **otomatis deploy** ke server Beta (`/var/www/bk-poin-sp-beta`).

### 🧪 Skenario B: Pengujian Beta Tester
Berikan link subdomain beta (misal `https://beta-bk.sekolahanda.sch.id`) ke tim Guru BK / Kesiswaan.
- Data di server beta terpisah dari production (database `bk_man1pati_beta`).
- Guru BK bisa menguji tanpa takut merusak data nyata.

### 🚀 Skenario C: Rilis ke Production
Jika fitur di branch `develop` sudah diuji dan stabil:
1. Buka GitHub → Buat Pull Request dari `develop` ke `main`.
2. Setelah di-merge ke `main`, GitHub Actions akan **otomatis deploy + jalankan health check** ke server Production (`/var/www/bk-poin-sp`).
3. Jika aplikasi crash/unhealthy, sistem akan **otomatis melakukan ROLLBACK** ke versi sebelum deploy.

---

## 4. Proteksi Branch (Rekomendasi Keamanan)

Untuk mencegah orang sengaja/tidak sengaja push langsung ke branch `main`:

1. Buka GitHub Repo → **Settings** → **Branches**.
2. Klik **Add branch protection rule**.
3. Pattern: `main`.
4. Centang:
   - ✅ *Require a pull request before merging*
   - ✅ *Require status checks to pass before merging* (Pilih: `PHP Syntax Lint & Security Audit`)
5. Simpan.

Dengan aturan ini, kode **wajib** lolos syntax check di CI & di-review sebelum bisa masuk ke `main`.
