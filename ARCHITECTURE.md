# 🏗️ Dokumentasi Arsitektur Perangkat Lunak (BK Poin & SP)

> 🔄 **Status Dokumentasi:** Automatically Updated via `scripts/update-architecture-docs.php`  
> 📅 **Terakhir Diperbarui:** 22 September 2026 07:56:44  
> 📊 **Statistik Project:** 34 file PHP | 4,202 baris kode (LOC) | Driver DB Active: `sqlite`

---

## 1. Visi Arsitektur & Prinsip Utama

Aplikasi ini dirancang dengan prinsip **"Ultra-Lightweight Server-Side Monolith"** yang dioptimalkan secara khusus untuk lingkungan **Shared Hosting Unlimited (RumahWeb / cPanel)** serta kompatibel untuk **Development Lokal**.

### Prinsip Utama (Architectural Pillars):
- **Memory Footprint Rendah (< 15 MB RAM)**: Menghindari dependency framework berat, menggunakan streaming output (`php://output`) untuk ekspor CSV masif.
- **Cross-Database Compatibility Layer (`CompatPDO`)**: Mendukung **SQLite** (0-config lokal) dan **MySQL/MariaDB** (RumahWeb production) secara seamless tanpa mengubah query aplikasi.
- **Security-First Design**: Bcrypt Password Hashing (cost 12), Anti-CSRF Token per sesi, Session Fixation Protection, Prepared Statements 100%, dan Rate-Limiting Login anti brute-force.
- **Automated Threshold Engine**: Sistem secara otomatis menghitung akumulasi poin dan menerbitkan Surat Peringatan (SP-1, SP-2, SP-3, Dikembalikan ke Ortum) ketika ambang poin terlampaui.

---

## 2. Diagram Topologi Sistem & Layer Arsitektur

```text
+-------------------------------------------------------------------------+
|                     BROWSER CLIENT (Spark Admin UI)                      |
|    Bootstrap 5 · Bootstrap Icons · ApexCharts · Flatpickr · Fetch/AJAX  |
+-------------------------------------------------------------------------+
                                     │ (HTTP POST / GET / CSRF Token)
                                     ▼
+-------------------------------------------------------------------------+
|                     ENTRYPOINT & ROUTER LAYER                           |
|         public/index.php ──► core/Router.php ──► core/Security.php      |
+-------------------------------------------------------------------------+
                                     │
                 ┌───────────────────┴───────────────────┐
                 ▼                                       ▼
+----------------------------------+   +----------------------------------+
|        CONTROLLERS LAYER         |   |         SERVICES / HELPERS       |
| Auth, Dashboard, Pelanggaran,    |   | Helpers.php (XSS Escape e())     |
| Siswa, Master, SP, Export        |   | ErrorHandler.php (Log Manager)  |
+----------------------------------+   +----------------------------------+
                 │                                       │
                 ▼                                       │
+--------------------------------------------------------┴----------------+
|                           MODELS & DATA LAYER                           |
|         AuthModel, GuruModel, SiswaModel, PelanggaranModel,           |
|         LogModel, PoinSiswaModel, SpModel                             |
+-------------------------------------------------------------------------+
                                     │ (PDO Prepared Statements)
                                     ▼
+-------------------------------------------------------------------------+
|                    COMPATIBILITY LAYER (`CompatPDO`)                    |
| Auto-transform SQL: SQLite syntax ◄──► MySQL / MariaDB syntax           |
+-------------------------------------------------------------------------+
                 │                                       │
                 ▼                                       ▼
+----------------------------------+   +----------------------------------+
|     SQLite (Local Dev DB)        |   |    MySQL / MariaDB (RumahWeb)    |
|     database/database.sqlite     |   |    Port 3306 / 3606 InnoDB       |
+----------------------------------+   +----------------------------------+
```

---

## 3. Peta Rute HTTP & Handler (Auto-Extracted: 20 Routes)

| Method | HTTP Path | Controller Class | Action Method |
|---|---|---|---|
| `GET` | `/health` | `HealthController` | `check()` |
| `GET` | `/login` | `AuthController` | `loginForm()` |
| `POST` | `/login` | `AuthController` | `doLogin()` |
| `GET` | `/auth/logout` | `AuthController` | `logout()` |
| `GET` | `/` | `DashboardController` | `index()` |
| `GET` | `/pelanggaran/input` | `PelanggaranController` | `form()` |
| `POST` | `/pelanggaran/store` | `PelanggaranController` | `store()` |
| `POST` | `/pelanggaran/siswa-by-kelas` | `PelanggaranController` | `siswaByKelas()` |
| `POST` | `/pelanggaran/poin-by-pelanggaran` | `PelanggaranController` | `poinByPelanggaran()` |
| `GET` | `/siswa/rekap` | `SiswaController` | `rekap()` |
| `POST` | `/siswa/riwayat` | `SiswaController` | `riwayat()` |
| `GET` | `/master/katalog` | `MasterPelanggaranController` | `index()` |
| `POST` | `/master/store` | `MasterPelanggaranController` | `store()` |
| `POST` | `/master/delete` | `MasterPelanggaranController` | `delete()` |
| `POST` | `/master/restore` | `MasterPelanggaranController` | `restoreDefault()` |
| `GET` | `/sp` | `SpController` | `index()` |
| `GET` | `/sp/print/{id}` | `SpController` | `print()` |
| `POST` | `/sp/delete` | `SpController` | `delete()` |
| `GET` | `/export/rekap-csv` | `ExportController` | `rekapCsv()` |
| `GET` | `/export/log-csv` | `ExportController` | `logCsv()` |

---

## 4. Skema Tabel Database (Auto-Inspected: 6 Tabel)

### 🗄️ Tabel: `guru`
| Kolom | Tipe Data | NOT NULL | Key |
|---|---|---|---|
| `id` | `VARCHAR(30)` | NO | PRIMARY KEY |
| `nama` | `VARCHAR(150)` | YES |  |
| `nip` | `VARCHAR(30)` | NO |  |
| `jabatan` | `VARCHAR(100)` | NO |  |
| `role` | `VARCHAR(30)` | YES |  |
| `username` | `VARCHAR(60)` | NO |  |
| `password_hash` | `VARCHAR(255)` | NO |  |

### 🗄️ Tabel: `siswa`
| Kolom | Tipe Data | NOT NULL | Key |
|---|---|---|---|
| `id` | `VARCHAR(30)` | NO | PRIMARY KEY |
| `nisn` | `VARCHAR(30)` | YES |  |
| `nama` | `VARCHAR(150)` | YES |  |
| `kelas` | `VARCHAR(30)` | YES |  |
| `absen` | `INT` | YES |  |
| `jenis_kelamin` | `VARCHAR(1)` | NO |  |

### 🗄️ Tabel: `master_pelanggaran`
| Kolom | Tipe Data | NOT NULL | Key |
|---|---|---|---|
| `id` | `VARCHAR(10)` | NO | PRIMARY KEY |
| `kategori` | `VARCHAR(50)` | YES |  |
| `sub_kategori` | `VARCHAR(50)` | YES |  |
| `jenis_pelanggaran` | `TEXT` | YES |  |
| `poin` | `INT` | YES |  |

### 🗄️ Tabel: `log_pelanggaran`
| Kolom | Tipe Data | NOT NULL | Key |
|---|---|---|---|
| `id` | `VARCHAR(50)` | NO | PRIMARY KEY |
| `tanggal` | `DATE` | YES |  |
| `siswa_id` | `VARCHAR(30)` | YES |  |
| `pelanggaran_id` | `VARCHAR(10)` | YES |  |
| `poin` | `INT` | YES |  |
| `guru_input` | `VARCHAR(150)` | YES |  |
| `catatan` | `VARCHAR(1000)` | NO |  |
| `bukti_url` | `VARCHAR(500)` | NO |  |
| `created_at` | `DATETIME` | NO |  |

### 🗄️ Tabel: `poin_siswa`
| Kolom | Tipe Data | NOT NULL | Key |
|---|---|---|---|
| `siswa_id` | `VARCHAR(30)` | NO | PRIMARY KEY |
| `total_poin` | `INT` | NO |  |
| `status_sp` | `VARCHAR(50)` | NO |  |
| `updated_at` | `DATETIME` | NO |  |

### 🗄️ Tabel: `surat_peringatan`
| Kolom | Tipe Data | NOT NULL | Key |
|---|---|---|---|
| `id` | `VARCHAR(50)` | NO | PRIMARY KEY |
| `no_surat` | `VARCHAR(100)` | YES |  |
| `tanggal` | `DATE` | YES |  |
| `siswa_id` | `VARCHAR(30)` | YES |  |
| `tingkat_sp` | `VARCHAR(50)` | YES |  |
| `status` | `VARCHAR(30)` | NO |  |
| `created_at` | `DATETIME` | NO |  |

---

## 5. Komponen Controller & Action Handlers

- **`AuthController`**: `loginForm()`, `doLogin()`, `logout()`
- **`DashboardController`**: `index()`
- **`ExportController`**: `rekapCsv()`, `logCsv()`
- **`HealthController`**: `check()`
- **`MasterPelanggaranController`**: `index()`, `store()`, `delete()`, `restoreDefault()`
- **`PelanggaranController`**: `form()`, `siswaByKelas()`, `poinByPelanggaran()`, `store()`
- **`SiswaController`**: `rekap()`, `riwayat()`
- **`SpController`**: `index()`, `print()`, `delete()`

---

## 6. Diagram Relasi Entitas (ERD)

```text
  +----------------+         +--------------------+         +-----------------------+
  |      guru      |         | master_pelanggaran |         |         siswa         |
  +----------------+         +--------------------+         +-----------------------+
  | PK id          |         | PK id              |         | PK id                 |
  |    nama        |         |    kategori        |         |    nisn               |
  |    nip         |         |    sub_kategori    |         |    nama               |
  |    username    |         |    jenis_pelangg.. |         |    kelas, absen, jk   |
  |    pass_hash   |         |    poin            |         +-----------------------+
  +-------+--------+         +---------+----------+                     │
          │                            │ (1)                        (1) │
          │ (referensi guru_input)     │                                │
          ▼                            ▼ (N)                        (N) ▼
+----------------------------------------------------+    +-------------------------+
|                  log_pelanggaran                   |    |       poin_siswa        |
+----------------------------------------------------+    +-------------------------+
| PK id                                              |    | PK, FK siswa_id         |
|    tanggal                                         |    |        total_poin        |
| FK siswa_id ───────────────────────────────────────┼───►|        status_sp        |
| FK pelanggaran_id                                  |    +-------------------------+
|    poin, guru_input, catatan, bukti_url            |                 │ (1)
+----------------------------------------------------+                 │
                                                                       ▼ (N)
                                                          +-------------------------+
                                                          |    surat_peringatan     |
                                                          +-------------------------+
                                                          | PK id                   |
                                                          |    no_surat, tanggal    |
                                                          | FK siswa_id             |
                                                          |    tingkat_sp, status   |
                                                          +-------------------------+
```

---

## 7. Aturan Bisnis Otomatisasi SP (Automated SP Engine)

| Ambang Akumulasi Poin | Status SP | Tindakan Otomatis Sistem |
|---|---|---|
| **0 – 24 Poin** | `Bebas SP` | Tidak ada surat peringatan terbit. |
| **25 – 49 Poin** | `SP-1` | Generasi draf Surat Peringatan ke-1 (`B-01/Ma.11.18/...`). |
| **50 – 74 Poin** | `SP-2` | Generasi draf Surat Peringatan ke-2 (`B-02/Ma.11.18/...`). |
| **75 – 99 Poin** | `SP-3` | Generasi draf Surat Peringatan ke-3 (`B-03/Ma.11.18/...`). |
| **≥ 100 Poin** | `Dikembalikan ke Ortum` | Generasi draf Surat Pengembalian ke Orang Tua (`B-04/Ma.11.18/...`). |

---

## 8. Otomatisasi Pembaruan Dokumentasi Arsitektur

Dokumentasi arsitektur ini **otomatis ter-update** setiap kali ada penambahan fitur, rute baru, atau perubahan skema tabel database.

### Cara Menjalankan Update Manual:
```bash
php scripts/update-architecture-docs.php
```

### Otomatisasi via Git Hook (Pre-commit):
Skrip ini juga terhubung ke Git Pre-commit hook di `.git/hooks/pre-commit` sehingga dokumentasi `ARCHITECTURE.md` selalu tersinkronisasi sebelum commit baru dibuat.
