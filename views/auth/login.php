<?php
$error = $error ?? '';
$locked = $locked ?? false;
$lockRemaining = $lockRemaining ?? 0;
$csrf = $csrf ?? '';
$oldUsername = $oldUsername ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b3d2e">
    <meta name="description" content="Login Sistem Poin Pelanggaran & Surat Peringatan Siswa">
    <title>Login — BK Poin & SP</title>
    <link rel="stylesheet" href="<?= asset('/assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/custom.css') ?>">
</head>
<body class="login-page">

<div class="login-wrapper">
    <div class="login-card card border-0 shadow-lg overflow-hidden">
        <div class="row g-0 h-100">

        <!-- ═══ Panel Kiri: Branding ═══ -->
        <aside class="col-lg-5 d-none d-lg-flex login-brand-panel">
            <div class="p-4 p-xl-5 text-white w-100">
                <div class="mb-4 d-flex align-items-center gap-2">
                    <div class="brand-logo bg-white bg-opacity-25"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="fw-bold fs-5 lh-1">BK &amp; SP</div>
                        <div class="small opacity-75" style="letter-spacing:1.5px">Sekolah Anda</div>
                    </div>
                </div>

                <h2 class="fw-bold mb-3">Sistem Poin Pelanggaran<br>&amp; Surat Peringatan</h2>
                <p class="small opacity-75 mb-4">
                    Pendataan pelanggaran tata tertib sekolah, akumulasi poin,
                    dan penerbitan Surat Peringatan otomatis berdasarkan ambang batas.
                </p>

                <div class="login-points">
                    <div class="d-flex gap-2 mb-2"><i class="bi bi-check-circle-fill"></i><span>Input pelanggaran &amp; prestasi</span></div>
                    <div class="d-flex gap-2 mb-2"><i class="bi bi-check-circle-fill"></i><span>SP-1, SP-2, SP-3 otomatis</span></div>
                    <div class="d-flex gap-2 mb-2"><i class="bi bi-check-circle-fill"></i><span>Cetak surat resmi sekolah</span></div>
                </div>

                <div class="login-brand-foot">
                    <i class="bi bi-geo-alt-fill me-1"></i>
                    Alamat sekolah Anda
                </div>
            </div>
        </aside>

        <!-- ═══ Panel Kanan: Form ═══ -->
        <main class="col-lg-7 login-form-col">
            <div class="login-form-panel h-100 d-flex flex-column justify-content-center">
                <div class="d-lg-none mb-4 text-center">
                    <div class="brand-logo mx-auto mb-2" style="width:56px;height:56px"><i class="bi bi-shield-check"></i></div>
                    <div class="fw-bold fs-4">BK &amp; SP</div>
                </div>

                <h3 class="fw-bold mb-1">Selamat Datang 👋</h3>
                <p class="text-muted small mb-4">Silakan masuk dengan akun guru / staf sekolah.</p>

                <?php if ($locked): ?>
                    <div class="alert alert-danger shadow-sm d-flex gap-2" role="alert">
                        <i class="bi bi-lock-fill fs-5 mt-1"></i>
                        <div>
                            <strong>Akun terkunci sementara.</strong><br>
                            Terlalu banyak percobaan gagal. Coba lagi dalam
                            <strong><?= (int)ceil($lockRemaining / 60) ?> menit</strong>.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error && !$locked): ?>
                    <div class="alert alert-danger small shadow-sm d-flex gap-2 py-2 px-3 animate-shake" role="alert">
                        <i class="bi bi-x-circle mt-1"></i><span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="/login" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="username">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="username" name="username"
                                   class="form-control" placeholder="mis. admin"
                                   value="<?= e($oldUsername) ?>"
                                   autocomplete="username" autocapitalize="off" autocorrect="off"
                                   spellcheck="false" inputmode="latin"
                                   required <?= $locked ? 'disabled' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex justify-content-between" for="password">
                            <span>Password</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password"
                                   class="form-control border-end-0" placeholder="••••••••"
                                   autocomplete="current-password"
                                   aria-describedby="capsHint"
                                   required <?= $locked ? 'disabled' : '' ?>>
                            <button type="button" id="togglePassword"
                                    class="btn btn-outline-secondary border-start-0 bg-white text-muted"
                                    aria-label="Tampilkan password" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="capsHint" class="form-text text-warning d-none">
                            <i class="bi bi-exclamation-triangle me-1"></i>Caps Lock sedang aktif
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                            <label class="form-check-label small text-muted" for="remember">Ingat saya</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-semibold py-2" id="btnLogin"
                            <?= $locked ? 'disabled' : '' ?>>
                        <span class="btn-login-label"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</span>
                        <span class="btn-login-spinner spinner-border spinner-border-sm" style="display:none!important" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="text-center mt-4 small text-muted">
                    <i class="bi bi-shield-lock me-1"></i>Akses terbatas untuk staf sekolah
                    <div class="mt-2 text-muted-50" style="font-size:11px">© <?= date('Y') ?> BK & SP</div>
                </div>
            </div>
        </main>
        </div>
    </div>
</div>

<script src="<?= asset('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
(function () {
    const form    = document.getElementById('loginForm');
    const btn     = document.getElementById('btnLogin');
    const pwd     = document.getElementById('password');
    const toggle  = document.getElementById('togglePassword');
    const caps    = document.getElementById('capsHint');
    const user    = document.getElementById('username');

    // ── Fokus otomatis ke username saat halaman dibuka ──
    if (user && !user.disabled) user.focus();

    // ── Show / hide password ──
    if (toggle && pwd) {
        toggle.addEventListener('click', function () {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            toggle.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            toggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            pwd.focus();
        });
    }

    // ── Deteksi Caps Lock ──
    if (pwd && caps) {
        const syncCaps = function (e) {
            const on = e.getModifierState && e.getModifierState('CapsLock');
            caps.classList.toggle('d-none', !on);
        };
        pwd.addEventListener('keyup', syncCaps);
        pwd.addEventListener('keydown', syncCaps);
        pwd.addEventListener('blur', function () { caps.classList.add('d-none'); });
    }

    // ── Loading state saat submit (cegah double-click) ──
    if (form && btn) {
        const spinnerEl = btn.querySelector('.btn-login-spinner');
        const labelEl   = btn.querySelector('.btn-login-label');
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            btn.disabled = true;
            if (labelEl) labelEl.style.display = 'none';
            if (spinnerEl) spinnerEl.style.setProperty('display', 'inline-block', 'important');
            window.addEventListener('pageshow', function () {
                btn.disabled = false;
                if (labelEl) labelEl.style.display = '';
                if (spinnerEl) spinnerEl.style.setProperty('display', 'none', 'important');
            });
        });
    }
})();
</script>
</body>
</html>
