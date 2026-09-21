<?php

require_once __DIR__ . '/../core/Security.php';

/** Escape output untuk mencegah XSS */
function e(?string $value): string {
    return Security::clean((string)$value);
}

/**
 * arrayMap — transform array dengan callback (cross-compatible, order-agnostic).
 * Support kedua style:
 *   arrayMap(items, fn(x) => …)
 *   arrayMap(fn(x) => …, items)   // Hack style
 * Tidak menggunakan hacklib array_map (broken di PHP-hybrid env).
 */
function arrayMap($argA, $argB): array {
    $callback = null;
    $items = [];
    if (is_callable($argA)) {
        $callback = $argA;
        $items = $argB;
    } else {
        $items = $argA;
        $callback = $argB;
    }
    $result = [];
    foreach ($items as $item) {
        $result[] = call_user_func_array($callback, [$item]);
    }
    return $result;
}

/** JSON response aman (hebat untuk AJAX) */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    $opts = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if ($statusCode >= 500) $opts |= JSON_PRETTY_PRINT;
    echo json_encode($data, $opts);
    exit;
}

/** Render view dengan data */
function view(string $viewPath, array $data = []): void {
    // CSRF token selalu tersedia di semua view (sidebar butuh ini untuk
    // POST /master/restore). Mencegah token kosong di halaman yg lupa dikirim.
    if (!array_key_exists('csrf', $data)) {
        $data['csrf'] = Security::csrfToken();
    }
    extract($data);
    $fullPath = __DIR__ . '/../views/' . $viewPath . '.php';
    if (file_exists($fullPath)) {
        require $fullPath;
    } else {
        http_response_code(500);
        die("View $viewPath tidak ditemukan.");
    }
}

/** Ambil status SP berdasarkan akumulasi poin */
function getSpStatusInfo(int $totalPoin): array {
    if ($totalPoin >= 100) {
        return ['status' => 'Dikembalikan ke Orang Tua', 'badge' => 'bg-danger text-white', 'level' => 4];
    } elseif ($totalPoin >= 75) {
        return ['status' => 'SP-3', 'badge' => 'bg-danger text-white', 'level' => 3];
    } elseif ($totalPoin >= 50) {
        return ['status' => 'SP-2', 'badge' => 'bg-warning text-dark', 'level' => 2];
    } elseif ($totalPoin >= 25) {
        return ['status' => 'SP-1', 'badge' => 'bg-info text-dark', 'level' => 1];
    }
    return ['status' => 'Bebas SP', 'badge' => 'bg-success-subtle text-success', 'level' => 0];
}

/** Format tanggal Indonesia */
function formatDateIndo(?string $dateStr): string {
    if (empty($dateStr) || $dateStr === '-') return '-';
    $time = strtotime($dateStr);
    if (!$time) return e($dateStr);
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $time) . ' ' . $bulan[(int)date('n', $time)] . ' ' . date('Y', $time);
}

/** Redirect aman */
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

/**
 * Asset — cache-busting otomatis berdasarkan waktu modifikasi file.
 * Hasil: /assets/css/custom.css?v=1754...
 * Browser selalu memuat versi terbaru setiap kali file diganti,
 * tanpa perlu hard-refresh manual.
 */
function asset(string $path): string {
    $real = __DIR__ . '/../public' . $path;
    if (is_file($real)) {
        $path .= '?v=' . substr(filemtime($real), -8);
    }
    return $path;
}