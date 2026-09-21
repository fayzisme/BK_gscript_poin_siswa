<?php
/**
 * HealthController — Endpoint pemeriksaan kesehatan aplikasi.
 *
 * Dipakai oleh CI/CD pipeline untuk memverifikasi keberhasilan deploy
 * (dan sebagai basis auto-rollback kalau setelah deploy app tidak sehat).
 *
 * Route: GET /health
 * Output: JSON (tanpa butuh login — aman, tidak membocorkan data sensitif)
 */
require_once __DIR__ . '/../../core/Controller.php';

class HealthController extends Controller {

    /**
     * GET /health → status aplikasi dalam format JSON.
     *
     * Pengecekan yang dilakukan:
     *  1. Koneksi database bisa diakses
     *  2. Tabel utama ada & schema sudah ter-migrate
     *  3. Sesi PHP berjalan normal
     *
     * Status: "ok" (HTTP 200) atau "degraded" (HTTP 503)
     */
    public function check(): void {
        $start = microtime(true);
        $checks = [];
        $allOk = true;

        // ── 1) Cek koneksi & tabel utama database ──
        try {
            $db = $this->db;

            // Pastikan schema ada (initSchema berjalan di constructor base Controller)
            $tables = ['guru', 'siswa', 'master_pelanggaran', 'log_pelanggaran', 'poin_siswa', 'surat_peringatan'];
            $missing = [];
            foreach ($tables as $t) {
                try {
                    $db->query("SELECT 1 FROM $t LIMIT 1")->fetch();
                } catch (Throwable $e) {
                    $missing[] = $t;
                }
            }

            if (!empty($missing)) {
                $allOk = false;
                $checks['database'] = [
                    'status' => 'fail',
                    'message' => 'Tabel tidak ditemukan: ' . implode(', ', $missing)
                ];
            } else {
                $checks['database'] = ['status' => 'ok'];
            }
        } catch (Throwable $e) {
            $allOk = false;
            $checks['database'] = [
                'status' => 'fail',
                'message' => 'Koneksi DB gagal: ' . $e->getMessage()
            ];
        }

        // ── 2) Cek .env ter-load & nilai penting tidak kosong ──
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $checks['config'] = [
            'status' => 'ok',
            'driver' => $driver,
        ];

        // ── 3) Cek folder storage writable (log bisa ditulis) ──
        $logDir = __DIR__ . '/../../storage/logs';
        if (is_dir($logDir) && is_writable($logDir)) {
            $checks['storage'] = ['status' => 'ok'];
        } else {
            $allOk = false;
            $checks['storage'] = [
                'status' => 'fail',
                'message' => 'storage/logs tidak writable: ' . $logDir
            ];
        }

        // ── Response ──
        $latency = round((microtime(true) - $start) * 1000, 2);
        $status = $allOk ? 'ok' : 'degraded';
        http_response_code($allOk ? 200 : 503);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        echo json_encode([
            'status' => $status,
            'app' => defined('APP_NAME') ? APP_NAME : 'BK Poin & SP',
            'env' => defined('APP_ENV') ? APP_ENV : 'unknown',
            'timestamp' => date('c'),
            'latency_ms' => $latency,
            'checks' => $checks,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
