# 🏫 BK Poin & SP — Sistem Poin Pelanggaran & Surat Peringatan Siswa

Sistem manajemen **Bimbingan Konseling (BK)** untuk mencatat poin pelanggaran siswa,
menghasilkan **Surat Peringatan (SP)** otomatis berdasarkan akumulasi poin, dan
memberikan penghargaan untuk poin positif. Dibangun sebagai **platform SaaS-ready**
yang dapat digunakan oleh sekolah mana pun.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-11.8+-003545?logo=mariadb&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-blue)

---

## ✨ Fitur Utama

- **Dashboard analitik** — ringkasan pelanggaran, SP aktif, dan tren bulanan (ApexCharts)
- **Input pelanggaran cepat** — pencarian siswa by NISN/nama, auto-complete katalog tata tertib
- **Rekap poin siswa** — total poin, riwayat, status SP, dengan filter kelas
- **Katalog tata tertib** — master pelanggaran & penghargaan (CRUD, bisa di-restore ke default)
- **Surat Peringatan otomatis** — draft SP-1/SP-2/SP-3 tergenerate saat poin mencapai ambang batas
- **Multi-role** — Admin (kepala sekolah), Koordinator BK, Guru BK (masing-masing hak akses berbeda)
- **Anti brute-force** — lockout otomatis setelah 5x percobaan login gagal
- **CSRF protection** di seluruh form mutasi data
- **Responsive & senior-friendly** — WCAG 2.2: kontras tinggi, font 16–17px+, touch target ≥48px
- **Dual database** — SQLite (0-config lokal) & MySQL/MariaDB (production)

---

## 🚀 Quick Start (Lokal, < 5 menit)

### Prasyarat

- PHP **8.3+** (dengan ekstensi `pdo_sqlite`, `pdo_mysql`, `mbstring`)
- Composer *(opsional — hanya jika ingin mengelola vendor via composer)*
- Node.js *(tidak diperlukan — asset front-end sudah bundled di repo)*

### Langkah

```bash
# 1. Clone
git clone https://github.com/fayzisme/BK_gscript_poin_siswa.git
cd BK_gscript_poin_siswa

# 2. Salin konfigurasi (boleh dikosongkan — ada fallback default)
cp .env.example .env          # edit jika perlu

# 3. Inisialisasi database + seed data contoh
php database/seed.php

# 4. Jalankan dev server
php -S localhost:8080 -t public
```

Buka **http://localhost:8080** dan login:

| Username | Password    | Role              |
|----------|-------------|-------------------|
| `admin`  | `admin123`  | Admin             |
| `bkkord` | `bkkord123` | Koordinator BK    |
| `gurubk` | `gurubk123` | Guru BK           |

> ⚠️ **Ganti password default ini segera setelah login pertama** di deployment nyata.

---

## ⚙️ Konfigurasi

Semua konfigurasi dibaca dari `.env` (di root proyek) dengan fallback aman jika kosong:

```ini
APP_NAME="BK Poin & SP — Nama Sekolah Anda"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bk.sekolahanda.sch.id

# Database: sqlite (default) atau mysql
DB_DRIVER=sqlite
DB_NAME=bk_poin_sp
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASS=

# Keamanan — WAJIB diisi di production
SESSION_SECRET=<random-string-panjang>
```

File `.env` **sudah di-gitignore** dan tidak akan pernah ter-commit.

---

## 🏗️ Arsitektur

**Ultra-Lightweight Server-Side Monolith** — PHP native tanpa framework berat,
dirancang untuk jalan di shared hosting / VPS minimalis sekalipun.

```
public/              → Document root (Apache/Nginx)
├── index.php        → Front controller (router entry)
└── assets/          → CSS, JS, vendor bundles
core/                → Kernel: Router, Database (CompatPDO), Security, Helpers
app/Controllers/     → Logika per-fitur (MVP pattern)
views/               → Template PHP (layout + partial)
database/            → Schema, seeder (seed.php, seed_master.php)
config/              → Loader .env & definisi konstanta
storage/             → Log & cache runtime (di-gitignore)
```

Detail teknis lengkap: **[ARCHITECTURE.md](ARCHITECTURE.md)**

---

## 📂 Struktur Database

Tabel inti:

| Tabel                  | Isi                                                    |
|------------------------|--------------------------------------------------------|
| `pengguna`             | Akun login (username, password hash bcrypt, role)     |
| `guru`                 | Profil staf pengajar                                   |
| `siswa`                | Data siswa (NISN, nama, kelas)                         |
| `master_pelanggaran`   | Katalog tata tertib: kode, poin, kategori              |
| `log_pelanggaran`      | Riwayat input pelanggaran/penghargaan                 |
| `sp_draft`             | Surat Peringatan otomatis (SP-1/SP-2/SP-3)            |

Schema dibuat otomatis oleh `database/seed.php` (SQLite) atau via migrasi SQL
untuk MySQL/MariaDB.

---

## 🔒 Keamanan

- **Password**: bcrypt via `password_hash()` — tidak pernah disimpan plain-text
- **CSRF token** disisipkan di semua form POST & divalidasi server-side
- **Session hardening**: `SESSION_SECRET`, cookie flags, regenerasi ID pasca-login
- **Rate limiting**: login diblokir sementara setelah `LOGIN_MAX_ATTEMPTS` kegagalan
- **Error handling**: tidak ada stack trace yang bocor ke user di production
- **No framework dependencies** = permukaan serang minimal

---

## 🧪 Testing & CI

```bash
# Syntax lint semua file PHP
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# Re-seed database & verifikasi integritas
php database/seed.php
```

GitHub Actions workflow `.github/workflows/ci.yml` menjalankan PHP syntax lint
secara otomatis pada setiap push & PR.

---

## 📦 Deployment

Untuk production, arahkan document root ke folder `public/`:

- **Apache**: `DocumentRoot /var/www/bk-poin-sp/public` (`.htaccess` sudah disediakan)
- **Nginx**: lihat contoh server block di `DEPLOYMENT-VPS.md` *(jika dipublish)*
- **cPanel / shared hosting**: gunakan Setup Node.js App atau manual upload

Pastikan:
1. `APP_ENV=production` dan `APP_DEBUG=false`
2. `SESSION_SECRET` diisi string random ≥ 64 karakter
3. Password default admin **diganti**
4. Folder `storage/logs` writable oleh web server

Script deploy otomatis & CI/CD pipeline tersedia (baca **[GITHUB-ACTIONS-CICD.md](GITHUB-ACTIONS-CICD.md)** dan **[DEPLOYMENT-VPS.md](DEPLOYMENT-VPS.md)**).

---

## 🤝 Brancing & Contributing

Model branching: `main` (production) ← feature branch per fitur.
Detail: **[GITHUB-AND-BRANCHING.md](GITHUB-AND-BRANCHING.md)**

## 📄 License

Proprietary — © 2026 BK Poin & SP. Hubungi author untuk lisensi penggunaan.

---

Dibangun dengan ❤️ untuk membantu sekolah mengelola kedisiplinan siswa secara **adil, transparan, dan terdokumentasi**.
