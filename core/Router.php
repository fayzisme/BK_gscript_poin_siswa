<?php
class Router {
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, $handler): void {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $uri, string $method): void {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/');
        if ($path === '') $path = '/';
        $method = strtoupper($method);

        // ── HEAD = GET tanpa body (RFC 7231 §4.3.2) ──
        // Monitoring & health-checker (curl -I, uptime kuma) memakai HEAD.
        $wantsHead = ($method === 'HEAD');
        $lookup = $wantsHead ? 'GET' : $method;

        // ── Static file serving (CSS/JS/images) ──
        // Di Apache (production) /assets/... di-serve langsung oleh web server,
        // tapi PHP built-in server melewatkan SEMUA request ke front controller
        // sehingga tanpa ini file statis menjadi 404.
        if ($lookup === 'GET' && $this->serveStatic($path)) {
            return;
        }

        $methodRoutes = $this->routes[$lookup] ?? [];

        foreach ($methodRoutes as $pathPattern => $handler) {
            $regex = preg_quote($pathPattern, '~');
            $regex = preg_replace('/\\\{[a-zA-Z_]+\\\}/', '([^/]+)', $regex);
            $regex = '~^' . $regex . '$~';

            $matches = [];
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }

            // Extract route params
            preg_match_all('/\{([a-zA-Z_]+)\}/', $pathPattern, $names);
            $args = [];
            foreach ($names[1] as $i => $paramName) {
                $val = rawurldecode($matches[$i + 1] ?? '');
                $args[] = $val;
                $GLOBALS[$paramName] = $val;
            }

            if (is_array($handler) && count($handler) === 2) {
                $controllerClass = $handler[0];
                $action = $handler[1];
                $controller = new $controllerClass();
                call_user_func_array([$controller, $action], $args);
            } elseif (is_callable($handler)) {
                call_user_func_array($handler, $args);
            }
            return;
        }

        http_response_code(404);
        view('errors/404');
    }

    /**
     * Serve static asset dari folder public/.
     * Hanya ekstensi aman yang diizinkan (mencegah kebocoran file sensitif
     * seperti .env, .php, database).
     *
     * @return bool true jika file berhasil di-serve (request selesai).
     */
    private function serveStatic(string $path): bool {
        // Bersihkan path traversal (mis. /assets/../../.env)
        $clean = preg_replace('#/+#', '/', $path);
        if (str_contains($clean, '../') || str_contains($clean, '..\\')) {
            return false;
        }

        $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));
        $mime = [
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'application/javascript; charset=utf-8',
            'json'  => 'application/json; charset=utf-8',
            'ico'   => 'image/x-icon',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'txt'   => 'text/plain; charset=utf-8',
            'map'   => 'application/json; charset=utf-8',
        ];

        if (!isset($mime[$ext])) {
            return false; // ekstensi tidak di-whitelist -> biarkan 404
        }

        $file = __DIR__ . '/../public' . $clean;
        if (!is_file($file)) {
            return false;
        }

        // Cache-control untuk performa (1 jam)
        header('Cache-Control: public, max-age=3600');
        header('Content-Type: ' . $mime[$ext]);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
}