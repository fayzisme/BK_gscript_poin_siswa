<?php
/**
 * Error & Exception Handler — Aman: tidak pernah menampilkan stack trace ke end-user.
 */
class SafeErrorHandler {

    public static function register(): void {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool {
        // Log error ke file (jangan ditampilkan ke user)
        self::log("PHP Error [$severity]: $message in $file:$line");
        return true; // suppress default handler
    }

    public static function handleException(Throwable $e): void {
        self::log("Uncaught " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        http_response_code(500);
        // Tampilkan halaman 500 generik yang aman
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Terjadi Kesalahan</title>';
        echo '<style>body{font-family:sans-serif;background:#f8f9fa;color:#333;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}';
        echo '.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:40px;max-width:480px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.06)}';
        echo 'h1{color:#dc2626;font-size:24px;margin:0 0 12px} p{color:#6b7280;line-height:1.6;margin:0 0 20px}</style></head><body>';
        echo '<div class="card"><h1>Terjadi Kesalahan Server</h1>';
        echo '<p>Maaf, terjadi kesalahan internal. Silakan coba lagi beberapa saat lagi atau hubungi administrator.</p>';
        echo '<a href="/" style="background:#059669;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block">Kembali ke Beranda</a></div>';
        echo '</body></html>';
        exit(1);
    }

    private static function log(string $message): void {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }
}

SafeErrorHandler::register();