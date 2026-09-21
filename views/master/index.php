<?php

$content = '';
ob_start();
$isAdmin = (Security::user()['role'] ?? '') === 'admin';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="text-muted master-count"><?= count($dataPelanggaran) ?> item katalog tata tertib</span>
    </div>
    <div class="d-flex gap-2">
        <?php if ($isAdmin): ?>
            <button class="btn btn-outline-secondary btn-master-restore" id="btnRestore" title="Kembalikan katalog ke data default bawaan" data-csrf="<?= e($csrf ?? '') ?>">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Restore Default
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMaster">
                <i class="bi bi-plus-lg me-1"></i>Tambah Item
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="get" action="/master/katalog" class="row g-2 align-items-center master-filter">
            <div class="col-md-4">
                <select name="kategori" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategoriList as $k): ?>
                        <option value="<?= e($k) ?>" <?= ($kategoriAktif === $k) ? 'selected' : '' ?>><?= e($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari jenis pelanggaran..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Badge Ringkas per kategori -->
<div class="row g-2 mb-3">
    <?php
    $countByKategori = [];
    foreach ($dataPelanggaran as $p) {
        $countByKategori[$p['kategori']] = ($countByKategori[$p['kategori']] ?? 0) + 1;
    }
    $warna = [
        'Kepribadian' => 'bg-danger-subtle text-danger',
        'Kerajinan' => 'bg-warning-subtle text-warning',
        'Kerapian' => 'bg-info-subtle text-info',
        'Penghargaan' => 'bg-success-subtle text-success',
    ];
    foreach ($countByKategori as $kat => $jml): ?>
        <div class="col-6 col-md-3">
            <div class="card text-center py-2 h-100">
                <div class="fw-bold"><?= (int)$jml ?> item</div>
                <span class="badge mx-auto mt-1 <?= $warna[$kat] ?? 'bg-secondary-subtle' ?>"><?= e($kat) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Tabel Katalog -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 master-table">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">ID</th>
                    <th>Kategori</th>
                    <th>Sub Kategori</th>
                    <th>Jenis Pelanggaran</th>
                    <th class="text-center">Poin</th>
                    <?php if ($isAdmin): ?><th class="text-end pe-3">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataPelanggaran)): ?>
                    <tr><td colspan="<?= $isAdmin ? 6 : 5 ?>" class="text-center text-muted py-4">Tidak ada item ditemukan.</td></tr>
                <?php else: foreach ($dataPelanggaran as $p): ?>
                    <tr>
                        <td class="ps-3"><span class="badge bg-light text-dark border"><?= e($p['id']) ?></span></td>
                        <td>
                            <span class="badge <?= $warna[$p['kategori']] ?? 'bg-secondary-subtle' ?>"><?= e($p['kategori']) ?></span>
                        </td>
                        <td><?= e($p['sub_kategori']) ?></td>
                        <td><?= e($p['jenis_pelanggaran']) ?></td>
                        <td class="text-center">
                            <span class="fw-bold <?= (int)$p['poin'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= (int)$p['poin'] > 0 ? '+' . (int)$p['poin'] : (int)$p['poin'] ?>
                            </span>
                        </td>
                        <?php if ($isAdmin): ?>
                        <td class="text-end pe-3">
                            <button class="btn btn-outline-primary btn-edit-master" data-id="<?= e($p['id']) ?>" data-kategori="<?= e($p['kategori']) ?>" data-sub="<?= e($p['sub_kategori']) ?>" data-jenis="<?= e($p['jenis_pelanggaran']) ?>" data-poin="<?= (int)$p['poin'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-hapus-master" data-id="<?= e($p['id']) ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modal Tambah/Edit Master -->
<div class="modal fade" id="modalMaster" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formMaster">
                <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
                <input type="hidden" name="mode" id="masterMode" value="create">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMasterTitle">Tambah Item Katalog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">ID</label>
                            <input type="text" name="id" id="masterId" class="form-control" placeholder="P40 / H04" maxlength="4" required>
                            <div class="form-text">Format: <code>P</code> pelanggaran / <code>H</code> penghargaan + nomor</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Poin</label>
                            <input type="number" name="poin" id="masterPoin" class="form-control" placeholder="-15 / 5 / 100" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Kategori</label>
                            <select name="kategori" id="masterKategori" class="form-select">
                                <option value="Kepribadian">Kepribadian (Pelanggaran Berat)</option>
                                <option value="Kerajinan">Kerajinan</option>
                                <option value="Kerapian">Kerapian</option>
                                <option value="Penghargaan">Penghargaan (Prestasi)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Sub Kategori</label>
                            <input type="text" name="sub_kategori" id="masterSub" class="form-control" placeholder="Ketertiban / Rokok / Prestasi" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Jenis Pelanggaran</label>
                            <textarea name="jenis_pelanggaran" id="masterJenis" class="form-control" rows="3" maxlength="300" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($isAdmin): ?>
    // Edit
    document.querySelectorAll('.btn-edit-master').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('masterMode').value = 'update';
            document.getElementById('masterId').value = this.dataset.id;
            document.getElementById('masterId').readOnly = true;
            document.getElementById('masterKategori').value = this.dataset.kategori;
            document.getElementById('masterSub').value = this.dataset.sub;
            document.getElementById('masterJenis').value = this.dataset.jenis;
            document.getElementById('masterPoin').value = this.dataset.poin;
            document.getElementById('modalMasterTitle').textContent = 'Edit Item ' + this.dataset.id;
            new bootstrap.Modal(document.getElementById('modalMaster')).show();
        });
    });

    // Reset saat modal baru dibuka (tombol tambah
    document.querySelector('[data-bs-target="#modalMaster"]')?.addEventListener('click', function () {
        document.getElementById('masterMode').value = 'create';
        document.getElementById('masterId').readOnly = false;
        document.getElementById('masterId').value = '';
        document.getElementById('masterSub').value = '';
        document.getElementById('masterJenis').value = '';
        document.getElementById('masterPoin').value = '';
        document.getElementById('modalMasterTitle').textContent = 'Tambah Item Katalog';
    });

    // Submit
    document.getElementById('formMaster').addEventListener('submit', function (ev) {
        ev.preventDefault();
        fetch('/master/store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(new FormData(this))
        })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Tersimpan!', text: 'Item katalog berhasil disimpan.', confirmButtonColor: '#059669' })
                .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.error || 'Terjadi kesalahan' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Tidak dapat terhubung.' }));
    });

    // Restore katalog ke default (POST /master/restore → JSON)
    const btnRestore = document.getElementById('btnRestore');
    if (btnRestore) {
        btnRestore.addEventListener('click', function () {
            const csrf = this.dataset.csrf || '';
            Swal.fire({
                title: 'Restore katalog default?',
                text: 'Semua item akan dikembalikan ke katalog tata tertib default bawaan. Item yang pernah diubah/ditambah akan ditimpa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, restore',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch('/master/restore', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ csrf_token: csrf })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.ok) {
                        Swal.fire({ icon: 'success', title: 'Selesai', text: res.message || 'Katalog berhasil di-restore.', confirmButtonColor: '#059669' })
                        .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.error || 'Terjadi kesalahan' });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Kesalahan', text: 'Tidak dapat terhubung.' }));
            });
        });
    }

    // Hapus
    document.querySelectorAll('.btn-hapus-master').forEach(btn => {        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Hapus item?',
                text: `Item ${id} akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('/master/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ id: id, csrf_token: '<?= e($csrf ?? '') ?>' })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.ok) location.reload();
                        else Swal.fire({ icon: 'error', title: 'Gagal', text: res.error || 'Gagal menghapus item.' });
                    });
                }
            });
        });
    });
    <?php endif; ?>
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';