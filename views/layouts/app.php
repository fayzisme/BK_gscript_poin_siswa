<?php
/**
 * Layout Utama — Spark Admin Inspired
 * Menampilkan sidebar, navbar, konten, footer, scripts.
 * Variabel yang diharapkan: $title, $active, $user, $content, $csrf
 */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// ── Badge notifikasi global: hitung sekali di layout agar konsisten ──
// View boleh override dengan nilai yang sudah dihitung sendiri (mis. halaman SP).
if (!isset($notifCount)) {
    try {
        $badgeDb = Database::getConnection();
        $notifCount    = (int)$badgeDb->query("SELECT COUNT(*) FROM poin_siswa WHERE status_sp != 'Bebas SP'")->fetchColumn();
        $spBadgeCount  = (int)$badgeDb->query("SELECT COUNT(*) FROM surat_peringatan")->fetchColumn();
    } catch (\Throwable $e) {
        $notifCount = 0;
        $spBadgeCount = 0;
    }
} elseif (!isset($spBadgeCount)) {
    $spBadgeCount = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> — <?= e(APP_NAME) ?></title>
    <meta name="description" content="Sistem Bimbingan Konseling: Poin Pelanggaran dan Surat Peringatan Siswa">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏫</text></svg>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/vendor/flatpickr/flatpickr.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/custom.css') ?>">
</head>
<body class="bg-body-tertiary">

<div class="app-shell">
    <!-- ═══ SIDEBAR ═══ -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <div class="brand-logo"><i class="bi bi-shield-check"></i></div>
            <div>
                <div class="brand-title">BK & SP</div>
                <div class="brand-sub">Sekolah Anda</div>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li class="nav-section">MENU UTAMA</li>
            <li>
                <a href="/" class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/pelanggaran/input" class="nav-link <?= $active === 'input' ? 'active' : '' ?>">
                    <i class="bi bi-pencil-square"></i><span>Input Pelanggaran</span>
                </a>
            </li>
            <li>
                <a href="/siswa/rekap" class="nav-link <?= $active === 'rekap' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i><span>Rekap Poin Siswa</span>
                </a>
            </li>
            <li>
                <a href="/master/katalog" class="nav-link <?= $active === 'katalog' ? 'active' : '' ?>">
                    <i class="bi bi-journal-bookmark-fill"></i><span>Katalog Tata Tertib</span>
                </a>
            </li>

            <li class="nav-section mt-3">MANAJEMEN</li>
            <li>
                <a href="/sp" class="nav-link <?= $active === 'sp' ? 'active' : '' ?>">
                    <i class="bi bi-envelope-paper-fill"></i><span>Surat Peringatan</span>
                    <?php if (!empty($spBadgeCount)): ?>
                        <span class="badge badge-count ms-auto bg-danger"><?= (int)$spBadgeCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="/export/rekap-csv" class="nav-link">
                    <i class="bi bi-filetype-csv"></i><span>Ekspor Rekap CSV</span>
                </a>
            </li>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <li>
                <a href="/master/katalog" class="nav-link" id="navRestoreMaster" data-csrf="<?= e($csrf ?? '') ?>">
                    <i class="bi bi-arrow-counterclockwise"></i><span>Restore Master Data</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-section mt-3">SISTEM</li>
            <li>
                <a href="/auth/logout" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i><span>Keluar</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm bg-success-subtle text-success">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="lh-1">
                    <div class="fw-semibold small text-white"><?= e($user['nama'] ?? '-') ?></div>
                    <div class="text-white-50 small text-capitalize"><?= e($user['role'] ?? '-') ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ═══ BACKDROP MOBILE ═══ -->
    <div class="sidebar-backdrop" data-sidebar-toggle></div>

    <!-- ═══ MAIN AREA ═══ -->
    <main class="app-main">
        <!-- Navbar -->
        <nav class="app-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-icon btn-light" type="button" data-sidebar-toggle
                        aria-label="Tampilkan/sembunyikan menu" aria-expanded="true" title="Tampilkan/sembunyikan menu">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-sm-block">
                    <h1 class="fs-5 fw-bold mb-0 text-dark"><?= e($title ?? 'Dashboard') ?></h1>
                    <div class="breadcrumb-line">BK Poin <i class="bi bi-chevron-right small"></i> <?= e($title ?? '') ?></div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="get" action="/siswa/rekap" class="d-none d-md-flex search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" class="form-control navbar-search-input" placeholder="Cari siswa di rekap poin..." value="<?= e($_GET['q'] ?? '') ?>">
                </form>
                <div class="dropdown">
                    <button class="btn btn-icon btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-label="Notifikasi"><i class="bi bi-bell"></i><?php if (!empty($notifCount)): ?><span class="badge bg-danger notif-badge"><?= (int)$notifCount ?></span><?php endif; ?></button>
                    <ul class="dropdown-menu dropdown-menu-end p-2 shadow navbar-notif-menu" style="width:320px">
                        <li><h6 class="dropdown-header">Notifikasi</h6></li>
                        <?php if (empty($notifCount)): ?>
                            <li><span class="dropdown-item-text text-muted navbar-notif-empty">Tidak ada notifikasi baru.</span></li>
                        <?php else: ?>
                            <li><a class="dropdown-item navbar-notif-item" href="/sp"><i class="bi bi-envelope-paper me-2 text-danger"></i><?= (int)$notifCount ?> surat peringatan terbit otomatis</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" style="background:none;border:none">
                        <div class="avatar bg-success text-white">
                            <?= e(strtoupper(substr($user['nama'] ?? 'U', 0, 1))) ?>
                        </div>
                        <div class="d-none d-md-block text-start lh-1 navbar-user">
                            <div class="fw-semibold text-dark"><?= e($user['nama'] ?? '') ?></div>
                            <div class="text-muted text-capitalize"><?= e($user['role'] ?? '') ?></div>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow navbar-user-menu">
                        <li><span class="dropdown-item-text text-muted px-3 py-2"><?= e($user['username'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/auth/logout"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Konten -->
        <div class="app-content">
            <?php if (!empty($flash_msg)): ?>
                <div class="alert alert-<?= e($flash_type ?? 'success') ?> alert-dismissible fade show shadow-sm">
                    <i class="bi bi-info-circle me-2"></i><?= e($flash_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>

        <footer class="app-footer">
            <div>© <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong> · Sistem Poin Pelanggaran & Surat Peringatan Siswa</div>
            <div class="text-muted small">v1.0 · Laravel-style MVP on shared-hosting-ready PHP</div>
        </footer>
    </main>
</div>

<!-- Toast container untuk notifikasi -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

<!-- Scripts -->
    <script src="<?= asset('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('/assets/vendor/flatpickr/flatpickr.min.js') ?>"></script>
    <script src="<?= asset('/assets/vendor/apexcharts/apexcharts.min.js') ?>"></script>
    <script src="<?= asset('/assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="/assets/js/app.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Catatan: sidebar toggle & window.showToast ada di public/assets/js/app.js.

    // Flatpickr untuk semua input tanggal
    document.querySelectorAll('input[type="date"]').forEach(el => {
        flatpickr(el, { dateFormat: 'Y-m-d', allowInput: true });
    });

    // Catatan: window.showToast didefinisikan satu kali di public/assets/js/app.js

    // ── Restore Master Data (POST ke /master/restore, response JSON) ──
    // Dipakai dari sidebar & tombol di halaman katalog. Hanya admin.
    const navRestore = document.getElementById('navRestoreMaster');
    if (navRestore) {
        navRestore.addEventListener('click', function (ev) {
            ev.preventDefault();
            const csrf = this.dataset.csrf || '';
            const restoreUrl = '/master/restore';

            Swal.fire({
                title: 'Restore master data?',
                text: 'Semua item katalog akan dikembalikan ke tata tertib default bawaan. Item yang pernah diubah atau ditambah akan ditimpa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, restore',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mengembalikan katalog ke data default.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: p => { Swal.showLoading(); }
                });

                fetch(restoreUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ csrf_token: csrf })
                })
                .then(r => r.json().then(body => ({ ok: r.ok, body })))
                .then(({ ok, body }) => {
                    if (ok && body.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Selesai',
                            text: body.message || 'Katalog berhasil di-restore.',
                            confirmButtonColor: '#059669'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: (body && (body.error || body.message)) || 'Terjadi kesalahan saat restore.'
                        });
                    }
                })
                .catch(() => Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Jaringan',
                    text: 'Tidak dapat terhubung ke server.'
                }));
            });
        });
    }
});
</script>
</body>
</html>