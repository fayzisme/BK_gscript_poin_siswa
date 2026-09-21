<?php
require_once __DIR__ . '/../config/database.php';

/**
 * CompatPDO — SQL Compatibility Layer
 * Transform sintaks SQLite-only menjadi MySQL/MariaDB syntax on-the-fly,
 * sehingga models & seed dapat berjaya di BOTH drivers tanpa diganti query.
 *
 * Transformasi:
 *   INSERT OR REPLACE INTO        → REPLACE INTO
 *   strftime('%Y', col)           → YEAR(col)
 *   strftime('%Y-%m', col)        → DATE_FORMAT(col, '%Y-%m')
 *   strftime('%Y', 'now')         → YEAR(NOW())
 *   strftime('%Y-%m', 'now')      → DATE_FORMAT(NOW(), '%Y-%m')
 *   ON CONFLICT(pk) DO UPDATE SET … excluded.col = …  →  ON DUPLICATE KEY UPDATE … col = VALUES(col)
 *   PRAGMA table_info(t)          → SHOW COLUMNS FROM t
 */
class CompatPDO extends PDO {
    private static bool $isMysql = false;

    public function __construct(string $dsn, ?string $user = null, ?string $pass = null, ?array $opts = null) {
        parent::__construct($dsn, $user, $pass, $opts ?? []);
        self::$isMysql = true;
    }

    public static function transform(string $sql): string {
        if (!self::$isMysql) {
            return $sql;
        }

        // 1) INSERT OR REPLACE INTO → REPLACE INTO
        $sql = preg_replace('/\bINSERT\s+OR\s+REPLACE\s+INTO\b/i', 'REPLACE INTO', $sql);

        // 2) strftime on 'now'
        $sql = preg_replace("/strftime\('%Y-%m',\s*'now'\)/", "DATE_FORMAT(NOW(), '%Y-%m')", $sql);
        $sql = preg_replace("/strftime\('%Y',\s*'now'\)/", "YEAR(NOW())", $sql);

        // 3) strftime on column
        $sql = preg_replace("/strftime\('%Y-%m',\s*([a-zA-Z_][a-zA-Z0-9_]*)\)/", "DATE_FORMAT($1, '%Y-%m')", $sql);
        $sql = preg_replace("/strftime\('%Y',\s*([a-zA-Z_][a-zA-Z0-9_]*)\)/", "YEAR($1)", $sql);

        // 4) SQLite upsert → MySQL/MariaDB upsert (ON DUPLICATE KEY UPDATE … VALUES(col))
        //    Catatan: "AS new ON DUPLICATE KEY UPDATE new.col" hanya ada di MySQL 8.0+;
        //    MariaDB (11.x) TIDAK mendukung alias tsb → pakai VALUES(col) yg didukung
        //    BOTH MySQL 5.7+/8.0 maupun MariaDB 10.x/11.x.
        $sql = preg_replace("/\s+ON\s+CONFLICT\(\s*([a-zA-Z0-9_]+)\s*\)\s+DO\s+UPDATE\s+SET\s+/i",
            " ON DUPLICATE KEY UPDATE ", $sql);
        // col = excluded.col  →  col = VALUES(col)  (ambil nilai yg baru di-insert)
        //     Catatan: di SQLite urutannya "KOLOM = excluded.KOLOM", jadi tangkap
        //     nama kolom di KIRI tanda "=", lalu gunakan utk VALUES(...).
        $sql = preg_replace("/\b([a-zA-Z0-9_]+)\s*=\s*excluded\.([a-zA-Z0-9_]+)/i", "$1 = VALUES($1)", $sql);
        // Sisanya: "excluded.col" yg berdiri sendiri (bukan assignment) → nilainya
        // ada di VALUES(), yg paling aman & portabel adalah VALUES(col) jg.
        $sql = preg_replace("/\bexcluded\.([a-zA-Z0-9_]+)/i", "VALUES($1)", $sql);

        // 5) PRAGMA table_info → SHOW COLUMNS
        $sql = preg_replace("/PRAGMA\s+table_info\(\s*([a-zA-Z0-9_]+)\s*\)/i", "SHOW COLUMNS FROM $1", $sql);

        return $sql;
    }

    public function prepare(string $sql, ?array $options = null): PDOStatement {
        return parent::prepare(self::transform($sql), $options ?? []);
    }

    public function query(string $sql, ...$params): PDOStatement {
        return parent::query(self::transform($sql), ...$params);
    }

    public function exec(string $sql): int {
        return parent::exec(self::transform($sql));
    }
}

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                if (DB_DRIVER === 'sqlite') {
                    $dbDir = dirname(SQLITE_PATH);
                    if (!is_dir($dbDir)) {
                        mkdir($dbDir, 0777, true);
                    }
                    self::$instance = new PDO('sqlite:' . SQLITE_PATH);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec('PRAGMA foreign_keys = ON;');
                } else {
                    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                    self::$instance = new CompatPDO($dsn, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                }
            } catch (PDOException $e) {
                die("Koneksi Database Gagal: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function initSchema(): void {
        $db = self::getConnection();
        $isMysql = DB_DRIVER === 'mysql';

        $queries = [
            "CREATE TABLE IF NOT EXISTS guru (
                id VARCHAR(30) PRIMARY KEY,
                nama VARCHAR(150) NOT NULL,
                nip VARCHAR(30) DEFAULT '',
                jabatan VARCHAR(100) DEFAULT 'Guru BK',
                role VARCHAR(30) NOT NULL DEFAULT 'guru',
                username VARCHAR(60) DEFAULT '',
                password_hash VARCHAR(255) DEFAULT ''
            )" . ($isMysql ? " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" : ""),

            "CREATE TABLE IF NOT EXISTS siswa (
                id VARCHAR(30) PRIMARY KEY,
                nisn VARCHAR(30) NOT NULL,
                nama VARCHAR(150) NOT NULL,
                kelas VARCHAR(30) NOT NULL,
                absen INT NOT NULL,
                jenis_kelamin VARCHAR(1) DEFAULT 'L'
            )" . ($isMysql ? " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" : ""),

            "CREATE TABLE IF NOT EXISTS master_pelanggaran (
                id VARCHAR(10) PRIMARY KEY,
                kategori VARCHAR(50) NOT NULL,
                sub_kategori VARCHAR(50) NOT NULL,
                jenis_pelanggaran TEXT NOT NULL,
                poin INT NOT NULL
            )" . ($isMysql ? " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" : ""),

            "CREATE TABLE IF NOT EXISTS log_pelanggaran (
                id VARCHAR(50) PRIMARY KEY,
                tanggal DATE NOT NULL,
                siswa_id VARCHAR(30) NOT NULL,
                pelanggaran_id VARCHAR(10) NOT NULL,
                poin INT NOT NULL,
                guru_input VARCHAR(150) NOT NULL,
                catatan VARCHAR(1000) DEFAULT '',
                bukti_url VARCHAR(500) DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
                FOREIGN KEY (pelanggaran_id) REFERENCES master_pelanggaran(id) ON DELETE CASCADE
            )" . ($isMysql ? " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" : ""),

            "CREATE TABLE IF NOT EXISTS poin_siswa (
                siswa_id VARCHAR(30) PRIMARY KEY,
                total_poin INT DEFAULT 0,
                status_sp VARCHAR(50) DEFAULT 'Bebas SP',
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
            )" . ($isMysql ? " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" : ""),

            "CREATE TABLE IF NOT EXISTS surat_peringatan (
                id VARCHAR(50) PRIMARY KEY,
                no_surat VARCHAR(100) NOT NULL,
                tanggal DATE NOT NULL,
                siswa_id VARCHAR(30) NOT NULL,
                tingkat_sp VARCHAR(50) NOT NULL,
                status VARCHAR(30) DEFAULT 'Terbit',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
            )" . ($isMysql ? " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" : "")
        ];

        foreach ($queries as $sql) {
            $db->exec($sql);
        }

        // ── Migrasi kolom (tabel lama yang sudah ada) ──
        $columns = $db->query($isMysql ? "SHOW COLUMNS FROM guru" : "PRAGMA table_info(guru)")->fetchAll();
        $existing = [];
        foreach ($columns as $row) {
            $existing[] = $row['Field'] ?? $row['name'] ?? '';
        }
        if (!in_array('username', $existing, true)) {
            $db->exec("ALTER TABLE guru ADD COLUMN username VARCHAR(60) DEFAULT ''");
        }
        if (!in_array('password_hash', $existing, true)) {
            $db->exec("ALTER TABLE guru ADD COLUMN password_hash VARCHAR(255) DEFAULT ''");
        }
        if (!in_array('role', $existing, true)) {
            $db->exec("ALTER TABLE guru ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'guru'");
        }
    }
}