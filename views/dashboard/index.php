<?php

$content = '';
ob_start();
?>
<!-- Statistik Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div>
            <div class="flex-grow-1">
                <div class="stat-value"><?= (int)$totalSiswa ?></div>
                <div class="stat-label">Total Siswa</div>
                <div class="stat-trend text-primary">Data siswa seluruh madrasah</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="flex-grow-1">
                <div class="stat-value"><?= (int)$kenaSpSiswa ?></div>
                <div class="stat-label">Siswa Kena SP</div>
                <div class="stat-trend mt-1">
                    <span class="badge bg-danger-subtle text-danger"><?= (int)$totalSpTerbit ?> surat terbit</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-journal-check"></i></div>
            <div class="flex-grow-1">
                <div class="stat-value"><?= (int)$totalLog ?></div>
                <div class="stat-label">Total Catatan Pelanggaran</div>
                <div class="stat-trend mt-1 text-warning"><?= (int)$logBulanIni ?> catatan bulan ini</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-shield-check"></i></div>
            <div class="flex-grow-1">
                <div class="stat-value"><?= (int)$totalSpTerbit ?></div>
                <div class="stat-label">Total SP Terbit</div>
                <div class="stat-trend mt-1 text-success"><?= (int)$keributanBulan ?> catatan kepribadian</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                <span class="fw-semibold me-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Aksi Cepat:</span>
                <a href="/pelanggaran/input" class="btn btn-success btn-sm"><i class="bi bi-pencil-square me-1"></i>Input Pelanggaran</a>
                <a href="/siswa/rekap" class="btn btn-outline-primary btn-sm"><i class="bi bi-people me-1"></i>Lihat Rekap Poin</a>
                <a href="/sp" class="btn btn-outline-danger btn-sm"><i class="bi bi-envelope-paper me-1"></i>Kelola Surat SP</a>
                <a href="/export/rekap-csv" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Unduh Rekap CSV</a>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Distribusi Pelanggaran per Sub-Kategori</div>
            <div class="card-body"><div id="chartSubKategori"></div></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Kategori Pelanggaran</div>
            <div class="card-body"><div id="chartKategori"></div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Top Siswa Bermasalah -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Top Poin Pelanggaran</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($topSiswa)): ?>
                        <div class="text-center text-muted py-5 small">
                            <i class="bi bi-emoji-smile fs-1 d-block mb-2 text-success"></i>
                            Tidak ada siswa yang kena SP. Pertahankan!
                        </div>
                    <?php else: foreach ($topSiswa as $ts): ?>
                        <?php $info = getSpStatusInfo((int)$ts['total_poin']); ?>
                        <div class="list-group-item d-flex align-items-center gap-3 py-3">
                            <div class="avatar bg-danger-subtle text-danger fw-bold"><?= e(strtoupper(substr($ts['nama'], 0, 1))) ?></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small"><?= e($ts['nama']) ?></div>
                                <div class="text-muted" style="font-size:12px"><?= e($ts['kelas']) ?></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-danger"><?= (int)$ts['total_poin'] ?> Poin</div>
                                <span class="badge <?= $info['badge'] ?>"><?= e($info['status']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terakhir -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Aktivitas Terakhir</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Tanggal</th><th>Siswa</th><th>Pelanggaran</th><th class="text-center">Poin</th><th>Pelapor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLogs)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
                            <?php else: foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td class="text-nowrap small"><?= e($log['tanggal']) ?></td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($log['siswa_nama']) ?></div>
                                        <div class="text-muted" style="font-size:11px"><?= e($log['siswa_kelas']) ?></div>
                                    </td>
                                    <td class="small"><?= e($log['jenis_pelanggaran']) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $log['poin'] > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                                            <?= $log['poin'] > 0 ? '+' : '' ?><?= (int)$log['poin'] ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= e($log['guru_input']) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart Sub-Kategori
    new ApexCharts(document.querySelector('#chartSubKategori'), {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        series: [{ name: 'Jumlah', data: <?= json_encode($subJumlah) ?> }],
        xaxis: { categories: <?= json_encode($subLabels) ?>, labels: { style: { fontSize: '11px' } } },
        colors: ['#059669'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: v => v + ' catatan' } }
    }).render();

    // Chart Kategori (donut)
    new ApexCharts(document.querySelector('#chartKategori'), {
        chart: { type: 'donut', height: 300 },
        series: <?= json_encode($kategoriJumlah) ?>,
        labels: <?= json_encode($kategoriLabels) ?>,
        colors: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total' } } } } },
        dataLabels: { enabled: true, formatter: v => v + '%' }
    }).render();
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';