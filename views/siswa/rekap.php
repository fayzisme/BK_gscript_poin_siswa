<?php

$content = '';
ob_start();

// Totals
$totalSiswaTampil = 0;
$totalKenaSp = 0;
foreach ($dataSiswa as $kelas => $siswas) {
    $totalSiswaTampil += count($siswas);
    foreach ($siswas as $s) {
        if (($s['status_sp'] ?? 'Bebas SP') !== 'Bebas SP') $totalKenaSp++;
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="text-muted small">Menampilkan <strong><?= (int)$totalSiswaTampil ?></strong> siswa · <strong class="text-danger"><?= (int)$totalKenaSp ?></strong> kena SP</span>
    </div>
    <div class="d-flex gap-2">
        <a href="/export/rekap-csv" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="get" action="/siswa/rekap" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="kelas" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasList as $k): ?>
                        <option value="<?= e($k) ?>" <?= ($kelasAktif === $k) ? 'selected' : '' ?>><?= e($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama / NISN / no. absen" value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
            <?php if ($kelasAktif || $search): ?>
                <div class="col-12 text-end">
                    <a href="/siswa/rekap" class="small text-muted"><i class="bi bi-x-circle me-1"></i>Reset filter</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($dataSiswa)): ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Tidak ada siswa ditemukan.
        </div>
    </div>
<?php else: foreach ($dataSiswa as $kelas => $siswas): ?>
    <h6 class="text-muted fw-bold text-uppercase mt-2 mb-2 small">
        <i class="bi bi-mortarboard me-1"></i>Kelas <?= e($kelas) ?>
        <span class="badge bg-secondary-subtle text-secondary ms-1"><?= count($siswas) ?> siswa</span>
    </h6>

    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-3">Abs</th>
                        <th>Nama Siswa</th>
                        <th class="text-center">Total Poin</th>
                        <th>Status SP</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siswas as $s): ?>
                        <?php $info = getSpStatusInfo((int)$s['total_poin']); ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= (int)$s['absen'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($s['nama']) ?></div>
                                <div class="text-muted" style="font-size:11px">NISN: <?= e($s['nisn']) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold <?= (int)$s['total_poin'] > 0 ? 'text-danger' : 'text-secondary' ?>">
                                    <?= (int)$s['total_poin'] ?> poin
                                </span>
                            </td>
                            <td>
                                <?php
                                $badgeMap = [
                                    'Bebas SP' => 'bg-success-subtle text-success',
                                    'SP-1' => 'bg-info-subtle text-info',
                                    'SP-2' => 'bg-warning-subtle text-warning',
                                    'SP-3' => 'bg-danger-subtle text-danger',
                                    'Dikembalikan ke Orang Tua' => 'bg-dark-subtle text-dark',
                                ];
                                $cls = $badgeMap[$s['status_sp']] ?? 'bg-secondary-subtle text-secondary';
                                ?>
                                <span class="badge <?= $cls ?>"><?= e($s['status_sp']) ?></span>
                            </td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-outline-primary btn-lihat-riwayat" data-siswa="<?= e($s['id']) ?>" data-nama="<?= e($s['nama']) ?>">
                                    <i class="bi bi-clock-history me-1"></i>Riwayat
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; endif; ?>

<!-- Modal Riwayat -->
<div class="modal fade" id="modalRiwayat" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalSiswaNama">-</h5>
                    <div class="text-muted small" id="modalSiswaInfo">-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-3 mb-3">
                    <div class="card flex-grow-1 text-center py-2">
                        <div class="small text-muted">Total Poin</div>
                        <div class="fw-bold fs-4 text-danger" id="modalTotalPoin">-</div>
                    </div>
                    <div class="card flex-grow-1 text-center py-2">
                        <div class="small text-muted">Status SP</div>
                        <div id="modalStatusSp" class="fw-bold"></div>
                    </div>
                    <div class="card flex-grow-1 text-center py-2">
                        <div class="small text-muted">Jumlah Catatan</div>
                        <div class="fw-bold fs-4" id="modalJumlahCatatan">-</div>
                    </div>
                </div>
                <div id="timelineContainer">
                    <p class="text-muted small">Memuat riwayat...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-lihat-riwayat').forEach(btn => {
        btn.addEventListener('click', function () {
            const siswaId = this.dataset.siswa;
            const nama = this.dataset.nama;
            const modal = new bootstrap.Modal(document.getElementById('modalRiwayat'));
            document.getElementById('modalSiswaNama').textContent = nama;
            document.getElementById('modalSiswaInfo').textContent = 'Memuat...';
            document.getElementById('modalTotalPoin').textContent = '-';
            document.getElementById('modalJumlahCatatan').textContent = '-';
            document.getElementById('timelineContainer').innerHTML = '<p class="text-muted small">Memuat riwayat...</p>';
            modal.show();

            fetch('/siswa/riwayat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ siswa_id: siswaId, csrf_token: '<?= e($csrf ?? '') ?>' })
            })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.error || 'Gagal');
                document.getElementById('modalSiswaInfo').textContent = `Kelas ${res.siswa.kelas} · No. Absen ${res.siswa.absen} · NISN ${res.siswa.nisn}`;
                document.getElementById('modalTotalPoin').textContent = res.total_poin + ' poin';
                document.getElementById('modalJumlahCatatan').textContent = res.history.length;
                const spEl = document.getElementById('modalStatusSp');
                spEl.textContent = res.status_sp;
                spEl.className = 'fw-bold badge ' + ({
                    'Bebas SP': 'bg-success-subtle text-success',
                    'SP-1': 'bg-info-subtle text-info',
                    'SP-2': 'bg-warning-subtle text-warning',
                    'SP-3': 'bg-danger-subtle text-danger',
                    'Dikembalikan ke Orang Tua': 'bg-dark-subtle text-dark'
                }[res.status_sp] || 'bg-secondary-subtle');

                const container = document.getElementById('timelineContainer');
                container.innerHTML = '';
                if (!res.history.length) {
                    container.innerHTML = '<p class="text-muted small text-center py-4"><i class="bi bi-emoji-smile me-1"></i>Belum ada catatan pelanggaran.</p>';
                    return;
                }
                res.history.forEach(h => {
                    const div = document.createElement('div');
                    div.className = 'bg-body-tertiary rounded-3 p-3 mb-2 border-start border-4 ' + (h.poin > 0 ? 'border-danger' : 'border-success');
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i>${h.tanggal}</span>
                            <span class="fw-bold ${h.poin > 0 ? 'text-danger' : 'text-success'}">${h.poin > 0 ? '+' : ''}${h.poin} poin</span>
                        </div>
                        <div class="fw-semibold">${h.jenis}</div>
                        <div class="small text-muted">${h.kategori} / ${h.sub_kategori}</div>
                        ${h.catatan ? `<div class="small mt-1">📝 ${h.catatan}</div>` : ''}
                        <div class="small text-muted mt-1"><i class="bi bi-person me-1"></i>Pelapor: ${h.guru}</div>
                    `;
                    container.appendChild(div);
                });
            })
            .catch(err => {
                document.getElementById('timelineContainer').innerHTML = `<p class="text-danger small">Gagal memuat: ${err.message}</p>`;
            });
        });
    });
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';