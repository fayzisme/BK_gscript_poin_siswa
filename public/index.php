<?php
/**
 * Front Controller — BK Poin & SP
 * Entry point: semua request HTTP divir ke router MVC.
 * Compatible: PHP built-in server (dev) & Apache/Nginx (prod).
 */

// ── Boot Core ──
require_once __DIR__ . '/../core/ErrorHandler.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Controller.php';

// Auto-register semua controller
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/PelanggaranController.php';
require_once __DIR__ . '/../app/Controllers/SiswaController.php';
require_once __DIR__ . '/../app/Controllers/MasterPelanggaranController.php';
require_once __DIR__ . '/../app/Controllers/SpController.php';
require_once __DIR__ . '/../app/Controllers/ExportController.php';
require_once __DIR__ . '/../app/Controllers/HealthController.php';

$router = new Router();

// ── Health Check (publik, untuk CI/CD & monitoring) ──
$router->get('/health', [HealthController::class, 'check']);

// ── Auth ──
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'doLogin']);
$router->get('/auth/logout', [AuthController::class, 'logout']);

// ── Dashboard ──
$router->get('/', [DashboardController::class, 'index']);

// ── Pelanggaran ──
$router->get('/pelanggaran/input', [PelanggaranController::class, 'form']);
$router->post('/pelanggaran/store', [PelanggaranController::class, 'store']);
$router->post('/pelanggaran/siswa-by-kelas', [PelanggaranController::class, 'siswaByKelas']);
$router->post('/pelanggaran/poin-by-pelanggaran', [PelanggaranController::class, 'poinByPelanggaran']);

// ── Siswa / Rekap ──
$router->get('/siswa/rekap', [SiswaController::class, 'rekap']);
$router->post('/siswa/riwayat', [SiswaController::class, 'riwayat']);

// ── Master Katalog ──
$router->get('/master/katalog', [MasterPelanggaranController::class, 'index']);
$router->post('/master/store', [MasterPelanggaranController::class, 'store']);
$router->post('/master/delete', [MasterPelanggaranController::class, 'delete']);
$router->post('/master/restore', [MasterPelanggaranController::class, 'restoreDefault']);

// ── Surat Peringatan ──
$router->get('/sp', [SpController::class, 'index']);
$router->get('/sp/print/{id}', [SpController::class, 'print']);
$router->post('/sp/delete', [SpController::class, 'delete']);

// ── Export ──
$router->get('/export/rekap-csv', [ExportController::class, 'rekapCsv']);
$router->get('/export/log-csv', [ExportController::class, 'logCsv']);

$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');