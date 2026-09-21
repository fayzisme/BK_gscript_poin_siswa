<?php
/**
 * Auto-Updater Dokumentasi Arsitektur Sistem (ARCHITECTURE.md)
 * 
 * Skrip ini melakukan pemindaian (reflection & static analysis) pada codebase 
 * untuk mengekstraksi:
 * 1. Skema Database & Tabel (Database Inspector)
 * 2. Daftar Route HTTP & Controller Mapping (Route Inspector)
 * 3. Daftar Models & Service Handlers (Model Inspector)
 * 4. Daftar Views & Komponen UI (View Inspector)
 * 5. Telemetri & Statistik Kode (Code Metrics)
 * 
 * Dan menulis secara otomatis ke `ARCHITECTURE.md`.
 * 
 * Penggunaan:
 *   php scripts/update-architecture-docs.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

echo "=== MEMINDAI CODEBASE & MENGENERASI ARCHITECTURE.MD ===\n";

// 1. Dapatkan Schema Database
Database::initSchema();
$pdo = Database::getConnection();

$tablesInfo = [];
$driver = DB_DRIVER;

if ($driver === 'sqlite') {
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $tbl) {
        $cols = $pdo->query("PRAGMA table_info($tbl)")->fetchAll(PDO::FETCH_ASSOC);
        $tablesInfo[$tbl] = array_map(fn($c) => [
            'name' => $c['name'],
            'type' => $c['type'],
            'notnull' => $c['notnull'] ? 'YES' : 'NO',
            'pk' => $c['pk'] ? 'PRIMARY KEY' : ''
        ], $cols);
    }
} else {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $tbl) {
        $cols = $pdo->query("SHOW COLUMNS FROM $tbl")->fetchAll(PDO::FETCH_ASSOC);
        $tablesInfo[$tbl] = array_map(fn($c) => [
            'name' => $c['Field'],
            'type' => $c['Type'],
            'notnull' => $c['Null'] === 'NO' ? 'YES' : 'NO',
            'pk' => $c['Key'] === 'PRI' ? 'PRIMARY KEY' : ''
        ], $cols);
    }
}

// 2. Extract Routes dari public/index.php
$indexContent = file_get_contents(__DIR__ . '/../public/index.php');
preg_match_all('/\$router->(get|post)\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,\s*\[([^:]+)::class\s*,\s*[\'\"]([^\'\"]+)[\'\"]\]\s*\);/', $indexContent, $matches, PREG_SET_ORDER);

$routes = [];
foreach ($matches as $m) {
    $routes[] = [
        'method' => strtoupper($m[1]),
        'path' => $m[2],
        'controller' => trim($m[3]),
        'action' => trim($m[4])
    ];
}

// 3. Extract Controllers & Methods
$controllerFiles = glob(__DIR__ . '/../app/Controllers/*.php');
$controllersInfo = [];
foreach ($controllerFiles as $file) {
    $cName = pathinfo($file, PATHINFO_FILENAME);
    $content = file_get_contents($file);
    preg_match_all('/public\s+function\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)/', $content, $mFunc);
    $methods = array_diff($mFunc[1], ['__construct']);
    $controllersInfo[$cName] = array_values($methods);
}

// 4. Extract Models
$modelFiles = glob(__DIR__ . '/../app/Models/*.php');
$modelsList = array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), $modelFiles);

// 5. Extract Views
$viewFiles = glob(__DIR__ . '/../views/**/*.php');
$viewsList = array_map(fn($f) => str_replace(realpath(__DIR__ . '/../views/') . '/', '', realpath($f)), $viewFiles);

// 6. Hitung Statistik Baris Kode (LOC)
$totalLoc = 0;
$fileCounts = 0;
$directoryIterator = new RecursiveDirectoryIterator(__DIR__ . '/..');
$iterator = new RecursiveIteratorIterator($directoryIterator);
foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
        $path = $fileInfo->getPathname();
        if (str_contains($path, '/vendor/') || str_contains($path, '/_bmad/')) continue;
        $totalLoc += count(file($path));
        $fileCounts++;
    }
}

// Generasi Markdown ARCHITECTURE.md
$md = "# 🏗️ Dokumentasi Arsitektur Perangkat Lunak (BK Poin & SP)\n\n";
$md .= "> 🔄 **Status Dokumentasi:** Automatically Updated via `scripts/update-architecture-docs.php`  \n";
$md .= "> 📅 **Terakhir Diperbarui:** " . date('d F Y H:i:s') . "  \n";
$md .= "> 📊 **Statistik Project:** $fileCounts file PHP | " . number_format($totalLoc) . " baris kode (LOC) | Driver DB Active: `" . DB_DRIVER . "`\n\n";

$md .= "---\n\n";

$md .= "## 1. Visi Arsitektur & Prinsip Utama\n\n";
$md .= "Aplikasi ini dirancang dengan prinsip **\"Ultra-Lightweight Server-Side Monolith\"** yang dioptimalkan secara khusus untuk lingkungan **Shared Hosting Unlimited (RumahWeb / cPanel)** serta kompatibel untuk **Development Lokal**.\n\n";
$md .= "### Prinsip Utama (Architectural Pillars):\n";
$md .= "- **Memory Footprint Rendah (< 15 MB RAM)**: Menghindari dependency framework berat, menggunakan streaming output (`php://output`) untuk ekspor CSV masif.\n";
$md .= "- **Cross-Database Compatibility Layer (`CompatPDO`)**: Mendukung **SQLite** (0-config lokal) dan **MySQL/MariaDB** (RumahWeb production) secara seamless tanpa mengubah query aplikasi.\n";
$md .= "- **Security-First Design**: Bcrypt Password Hashing (cost 12), Anti-CSRF Token per sesi, Session Fixation Protection, Prepared Statements 100%, dan Rate-Limiting Login anti brute-force.\n";
$md .= "- **Automated Threshold Engine**: Sistem secara otomatis menghitung akumulasi poin dan menerbitkan Surat Peringatan (SP-1, SP-2, SP-3, Dikembalikan ke Ortum) ketika ambang poin terlampaui.\n\n";

$md .= "---\n\n";

$md .= "## 2. Diagram Topologi Sistem & Layer Arsitektur\n\n";
$md .= "```text\n";
$md .= "+-------------------------------------------------------------------------+\n";
$md .= "|                     BROWSER CLIENT (Spark Admin UI)                      |\n";
$md .= "|    Bootstrap 5 · Bootstrap Icons · ApexCharts · Flatpickr · Fetch/AJAX  |\n";
$md .= "+-------------------------------------------------------------------------+\n";
$md .= "                                     │ (HTTP POST / GET / CSRF Token)\n";
$md .= "                                     ▼\n";
$md .= "+-------------------------------------------------------------------------+\n";
$md .= "|                     ENTRYPOINT & ROUTER LAYER                           |\n";
$md .= "|         public/index.php ──► core/Router.php ──► core/Security.php      |\n";
$md .= "+-------------------------------------------------------------------------+\n";
$md .= "                                     │\n";
$md .= "                 ┌───────────────────┴───────────────────┐\n";
$md .= "                 ▼                                       ▼\n";
$md .= "+----------------------------------+   +----------------------------------+\n";
$md .= "|        CONTROLLERS LAYER         |   |         SERVICES / HELPERS       |\n";
$md .= "| Auth, Dashboard, Pelanggaran,    |   | Helpers.php (XSS Escape e())     |\n";
$md .= "| Siswa, Master, SP, Export        |   | ErrorHandler.php (Log Manager)  |\n";
$md .= "+----------------------------------+   +----------------------------------+\n";
$md .= "                 │                                       │\n";
$md .= "                 ▼                                       │\n";
$md .= "+--------------------------------------------------------┴----------------+\n";
$md .= "|                           MODELS & DATA LAYER                           |\n";
$md .= "|         AuthModel, GuruModel, SiswaModel, PelanggaranModel,           |\n";
$md .= "|         LogModel, PoinSiswaModel, SpModel                             |\n";
$md .= "+-------------------------------------------------------------------------+\n";
$md .= "                                     │ (PDO Prepared Statements)\n";
$md .= "                                     ▼\n";
$md .= "+-------------------------------------------------------------------------+\n";
$md .= "|                    COMPATIBILITY LAYER (`CompatPDO`)                    |\n";
$md .= "| Auto-transform SQL: SQLite syntax ◄──► MySQL / MariaDB syntax           |\n";
$md .= "+-------------------------------------------------------------------------+\n";
$md .= "                 │                                       │\n";
$md .= "                 ▼                                       ▼\n";
$md .= "+----------------------------------+   +----------------------------------+\n";
$md .= "|     SQLite (Local Dev DB)        |   |    MySQL / MariaDB (RumahWeb)    |\n";
$md .= "|     database/database.sqlite     |   |    Port 3306 / 3606 InnoDB       |\n";
$md .= "+----------------------------------+   +----------------------------------+\n";
$md .= "```\n\n";

$md .= "---\n\n";

$md .= "## 3. Peta Rute HTTP & Handler (Auto-Extracted: " . count($routes) . " Routes)\n\n";
$md .= "| Method | HTTP Path | Controller Class | Action Method |\n";
$md .= "|---|---|---|---|\n";
foreach ($routes as $r) {
    $md .= "| `{$r['method']}` | `{$r['path']}` | `{$r['controller']}` | `{$r['action']}()` |\n";
}
$md .= "\n---\n\n";

$md .= "## 4. Skema Tabel Database (Auto-Inspected: " . count($tablesInfo) . " Tabel)\n\n";
foreach ($tablesInfo as $tableName => $cols) {
    $md .= "### 🗄️ Tabel: `$tableName`\n";
    $md .= "| Kolom | Tipe Data | NOT NULL | Key |\n";
    $md .= "|---|---|---|---|\n";
    foreach ($cols as $c) {
        $md .= "| `{$c['name']}` | `{$c['type']}` | {$c['notnull']} | {$c['pk']} |\n";
    }
    $md .= "\n";
}

$md .= "---\n\n";

$md .= "## 5. Komponen Controller & Action Handlers\n\n";
foreach ($controllersInfo as $cName => $methods) {
    $md .= "- **`$cName`**: " . implode(', ', array_map(fn($m) => "`$m()`", $methods)) . "\n";
}
$md .= "\n---\n\n";

$md .= "## 6. Diagram Relasi Entitas (ERD)\n\n";
$md .= "```text\n";
$md .= "  +----------------+         +--------------------+         +-----------------------+\n";
$md .= "  |      guru      |         | master_pelanggaran |         |         siswa         |\n";
$md .= "  +----------------+         +--------------------+         +-----------------------+\n";
$md .= "  | PK id          |         | PK id              |         | PK id                 |\n";
$md .= "  |    nama        |         |    kategori        |         |    nisn               |\n";
$md .= "  |    nip         |         |    sub_kategori    |         |    nama               |\n";
$md .= "  |    username    |         |    jenis_pelangg.. |         |    kelas, absen, jk   |\n";
$md .= "  |    pass_hash   |         |    poin            |         +-----------------------+\n";
$md .= "  +-------+--------+         +---------+----------+                     │\n";
$md .= "          │                            │ (1)                        (1) │\n";
$md .= "          │ (referensi guru_input)     │                                │\n";
$md .= "          ▼                            ▼ (N)                        (N) ▼\n";
$md .= "+----------------------------------------------------+    +-------------------------+\n";
$md .= "|                  log_pelanggaran                   |    |       poin_siswa        |\n";
$md .= "+----------------------------------------------------+    +-------------------------+\n";
$md .= "| PK id                                              |    | PK, FK siswa_id         |\n";
$md .= "|    tanggal                                         |    |        total_poin        |\n";
$md .= "| FK siswa_id ───────────────────────────────────────┼───►|        status_sp        |\n";
$md .= "| FK pelanggaran_id                                  |    +-------------------------+\n";
$md .= "|    poin, guru_input, catatan, bukti_url            |                 │ (1)\n";
$md .= "+----------------------------------------------------+                 │\n";
$md .= "                                                                       ▼ (N)\n";
$md .= "                                                          +-------------------------+\n";
$md .= "                                                          |    surat_peringatan     |\n";
$md .= "                                                          +-------------------------+\n";
$md .= "                                                          | PK id                   |\n";
$md .= "                                                          |    no_surat, tanggal    |\n";
$md .= "                                                          | FK siswa_id             |\n";
$md .= "                                                          |    tingkat_sp, status   |\n";
$md .= "                                                          +-------------------------+\n";
$md .= "```\n\n";

$md .= "---\n\n";

$md .= "## 7. Aturan Bisnis Otomatisasi SP (Automated SP Engine)\n\n";
$md .= "| Ambang Akumulasi Poin | Status SP | Tindakan Otomatis Sistem |\n";
$md .= "|---|---|---|\n";
$md .= "| **0 – 24 Poin** | `Bebas SP` | Tidak ada surat peringatan terbit. |\n";
$md .= "| **25 – 49 Poin** | `SP-1` | Generasi draf Surat Peringatan ke-1 (`B-01/Ma.11.18/...`). |\n";
$md .= "| **50 – 74 Poin** | `SP-2` | Generasi draf Surat Peringatan ke-2 (`B-02/Ma.11.18/...`). |\n";
$md .= "| **75 – 99 Poin** | `SP-3` | Generasi draf Surat Peringatan ke-3 (`B-03/Ma.11.18/...`). |\n";
$md .= "| **≥ 100 Poin** | `Dikembalikan ke Ortum` | Generasi draf Surat Pengembalian ke Orang Tua (`B-04/Ma.11.18/...`). |\n\n";

$md .= "---\n\n";

$md .= "## 8. Otomatisasi Pembaruan Dokumentasi Arsitektur\n\n";
$md .= "Dokumentasi arsitektur ini **otomatis ter-update** setiap kali ada penambahan fitur, rute baru, atau perubahan skema tabel database.\n\n";
$md .= "### Cara Menjalankan Update Manual:\n";
$md .= "```bash\n";
$md .= "php scripts/update-architecture-docs.php\n";
$md .= "```\n\n";
$md .= "### Otomatisasi via Git Hook (Pre-commit):\n";
$md .= "Skrip ini juga terhubung ke Git Pre-commit hook di `.git/hooks/pre-commit` sehingga dokumentasi `ARCHITECTURE.md` selalu tersinkronisasi sebelum commit baru dibuat.\n";

$targetPath = __DIR__ . '/../ARCHITECTURE.md';
file_put_contents($targetPath, $md);

echo "[✓] ARCHITECTURE.md berhasil di-generate! (" . strlen($md) . " karakter)\n";
