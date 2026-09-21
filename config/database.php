<?php
/**
 * Simple .env Loader
 */
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Hapus komentari inline dalam nilai (mis: DB_PASS=x # nota)
            $commentPos = stripos($value, ' #');
            if ($commentPos !== false) {
                $value = substr($value, 0, $commentPos);
                $value = trim($value);
            }
            // Hapus kutip jika ada
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/../.env');

/**
 * Application Configuration
 * .env memuat banyak nilai (APP_URL, APP_NAME, dll) tetapi konstanta PHP
 * hanya didefinisikan untuk nilai yang benar-benar dipakai di kode.
 * `??` memastikan selalu ada fallback walau .env kosong.
 */
define('APP_NAME',    getenv('APP_NAME')    ?: 'BK Poin & SP');
define('APP_ENV',     getenv('APP_ENV')     ?: 'production');
define('APP_URL',     getenv('APP_URL')     ?: '');
define('APP_DEBUG',   filter_var(getenv('APP_DEBUG')   ?: false, FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE',getenv('APP_TIMEZONE')?: 'Asia/Jakarta');
define('SESSION_SECRET', getenv('SESSION_SECRET') ?: '');
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 7200));
define('LOGIN_MAX_ATTEMPTS', (int)(getenv('LOGIN_MAX_ATTEMPTS') ?: 5));
define('LOGIN_LOCKOUT_MINUTES', (int)(getenv('LOGIN_LOCKOUT_MINUTES') ?: 15));
define('LOG_DIR',     getenv('LOG_DIR')     ?: 'storage/logs');
define('LOG_LEVEL',   getenv('LOG_LEVEL')   ?: 'info');

if (!empty(APP_TIMEZONE)) {
    date_default_timezone_set(APP_TIMEZONE);
}

/**
 * Database Configuration for BK Poin & SP
 * Supports both SQLite (default for local) and MySQL/MariaDB (for shared hosting / VPS).
 */

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite'); // 'sqlite' or 'mysql'

// SQLite Settings
define('SQLITE_PATH', __DIR__ . '/../database/database.sqlite');

// MySQL / MariaDB Settings
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'bk_poin_sp');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
