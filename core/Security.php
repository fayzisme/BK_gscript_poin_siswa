<?php
/**
 * Security Layer — BK Poin & SP
 * Melindungi: Session, CSRF, XSS, Input Validation, Rate Limiting Login
 */

class Security {

    // ── Session Hardening ──────────────────────────────────────────────
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        // Hardening cookie session
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('BKSP_SESSID');
        session_start();

        // Regenerate ID periodically untuk mencegah session fixation
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    // ── CSRF Protection ────────────────────────────────────────────────
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('CSRF token tidak valid. Silakan muat ulang halaman.');
        }
    }

    // ── Auth Helpers ───────────────────────────────────────────────────
    public static function login(array $user): void {
        session_regenerate_id(true); // cegah session fixation
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'nama'     => $user['nama'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['user']);
    }

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void {
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            die('Akses ditolak: Anda tidak memiliki wewenang untuk halaman ini.');
        }
    }

    // ── Rate Limiting Login (anti brute force) ─────────────────────────
    public static function loginAttempts(): int {
        return $_SESSION['login_attempts'] ?? 0;
    }

    public static function incrementLoginAttempts(): void {
        $_SESSION['login_attempts'] = self::loginAttempts() + 1;
        $_SESSION['login_last_attempt'] = time();
    }

    public static function resetLoginAttempts(): void {
        unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
    }

    public static function isLoginLocked(): bool {
        return self::loginAttempts() >= 5
            && (time() - ($_SESSION['login_last_attempt'] ?? 0)) < 900; // 15 menit
    }

    public static function loginLockRemaining(): int {
        $elapsed = time() - ($_SESSION['login_last_attempt'] ?? time());
        return max(0, 900 - $elapsed);
    }

    // ── Input Sanitization & Validation ────────────────────────────────
    public static function post(string $key, $default = ''): string {
        return trim((string)($_POST[$key] ?? $default));
    }

    public static function get(string $key, $default = ''): string {
        return trim((string)($_GET[$key] ?? $default));
    }

    public static function clean(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function cleanInt(string $value, int $min = 0, int $max = 100000): int {
        $int = (int)$value;
        return max($min, min($max, $int));
    }

    public static function cleanDate(string $value): string {
        $date = date('Y-m-d', strtotime($value));
        return ($date === '1970-01-01' || $date === false) ? date('Y-m-d') : $date;
    }

    public static function cleanKelas(string $value): string {
        // Whitelist format kelas: X-1, XI-IPA-1, XII-IPS-2, dll
        if (!preg_match('/^[XIV]{1,3}(-[A-Z]+)?-\d{1,2}$/i', $value)) {
            return '';
        }
        return strtoupper($value);
    }

    public static function cleanNisn(string $value): string {
        return preg_replace('/[^0-9]/', '', $value);
    }

    public static function cleanNip(string $value): string {
        return preg_replace('/[^0-9]/', '', $value);
    }

    public static function cleanId(string $value): string {
        return preg_replace('/[^A-Za-z0-9\-_.]/', '', $value);
    }

    public static function cleanUrl(string $value): string {
        $url = filter_var($value, FILTER_VALIDATE_URL);
        if (!$url) return '';
        // Hanya izinkan http/https
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) return '';
        return $url;
    }

    // ── Security Headers ───────────────────────────────────────────────
    public static function sendSecurityHeaders(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'");
    }
}