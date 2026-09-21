<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/AuthModel.php';

class AuthController extends Controller {

    public function loginForm(): void {
        if (Security::isLoggedIn()) {
            redirect('/');
        }
        view('auth/login', [
            'title' => 'Login',
            'locked' => Security::isLoginLocked(),
            'lockRemaining' => Security::loginLockRemaining(),
            'csrf' => Security::csrfToken(),
            'oldUsername' => '',
        ]);
    }

    public function doLogin(): void {
        Security::verifyCsrf();

        // Rate limiting: anti brute force
        if (Security::isLoginLocked()) {
            view('auth/login', [
                'title' => 'Login',
                'error' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . ceil(Security::loginLockRemaining() / 60) . ' menit.',
                'locked' => true,
                'lockRemaining' => Security::loginLockRemaining(),
                'csrf' => Security::csrfToken(),
                'oldUsername' => $username,
            ]);
            return;
        }

        $username = Security::post('username');
        $password = Security::post('password');

        if ($username === '' || $password === '') {
            Security::incrementLoginAttempts();
            view('auth/login', [
                'title' => 'Login',
                'error' => 'Username dan password wajib diisi.',
                'locked' => Security::isLoginLocked(),
                'lockRemaining' => Security::loginLockRemaining(),
                'csrf' => Security::csrfToken(),
                'oldUsername' => $username,
            ]);
            return;
        }

        $authModel = new AuthModel($this->db);
        $user = $authModel->findByUsername($username);

        // Verifikasi password dengan password_verify (bcrypt/argon2)
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Security::incrementLoginAttempts();
            view('auth/login', [
                'title' => 'Login',
                'error' => 'Username atau password salah.',
                'locked' => Security::isLoginLocked(),
                'lockRemaining' => Security::loginLockRemaining(),
                'csrf' => Security::csrfToken(),
                'oldUsername' => $username,
            ]);
            return;
        }

        // Login sukses
        Security::resetLoginAttempts();
        Security::login($user);
        redirect('/');
    }

    public function logout(): void {
        Security::logout();
        redirect('/login');
    }
}