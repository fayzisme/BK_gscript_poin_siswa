<?php

$content = '';
ob_start();
$isAdmin = (Security::user()['role'] ?? '') === 'admin';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="text-muted small"><?= count($dataSp) ?> surat peringatan diterbitkan</span>
    </div>
</div>

<?php if (empty($dataSp)): ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-envelope-paper fs-1 d-block mb-2"></i>
            Belum ada Surat Peringatan diterbitkan.
        </div>
    </div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">No. Surat</th>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>Tingkat SP</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dataSp as $sp): ?>
                    <?php
                    $badgeMap = [
                        'SP-1' => 'bg-info-subtle text-info',
                        'SP-2' => 'bg-warning-subtle text-warning',
                        'SP-3' => 'bg-danger-subtle text-danger',
                        'Dikembalikan ke Orang Tua' => 'bg-dark-subtle text-dark',
                    ];
                    ?>
                    <tr>
                        <td class="ps-3 small"><?= e($sp['no_surat']) ?></td>
                        <td class="small text-nowrap"><?= e($sp['tanggal']) ?></td>
                        <td>
                            <div class="fw-semibold small"><?= e($sp['siswa_nama']) ?></div>
                            <div class="text-muted" style="font-size:11px"><?= e($sp['siswa_kelas']) ?> · NISN <?= e($sp['siswa_nisn']) ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $badgeMap[$sp['tingkat_sp']] ?? 'bg-secondary-subtle' ?>"><?= e($sp['tingkat_sp']) ?></span>
                        </td>
                        <td><span class="badge bg-success-subtle text-success"><?= e($sp['status']) ?></span></td>
                        <td class="text-end pe-3">
                            <a href="/sp/print/<?= urlencode($sp['id']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-printer me-1"></i>Cetak
                            </a>
                            <?php if ($isAdmin): ?>
                            <button class="btn btn-sm btn-outline-danger btn-hapus-sp" data-id="<?= e($sp['id']) ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-hapus-sp').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Hapus surat?',
                text: 'Surat akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('/sp/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ id: id, csrf_token: '<?= e($csrf ?? '') ?>' })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.ok) location.reload();
                        else Swal.fire({ icon: 'error', title: 'Gagal', text: res.error || 'Gagal menghapus surat.' });
                    });
                }
            });
        });
    });
});
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';