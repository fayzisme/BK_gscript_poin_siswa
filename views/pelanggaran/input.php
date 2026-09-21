<?php

$content = '';
ob_start();
?>
<div class="row g-3">
    <!-- FORM INPUT -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil-square me-2 text-success"></i>Form Input Pelanggaran / Prestasi</div>
            <div class="card-body">
                <form id="formPelanggaran" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Kejadian <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                            <select name="kelas" id="pilihKelas" class="form-select" required>
                                <option value="">— Pilih Kelas —</option>
                                <?php foreach ($kelasList as $k): ?>
                                    <option value="<?= e($k) ?>"><?= e($k) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Siswa <span class="text-danger">*</span></label>
                            <select name="siswa_id" id="pilihSiswa" class="form-select" required disabled>
                                <option value="">— Pilih Kelas dulu —</option>
                            </select>
                        </div>

                        <!-- SELEKTOR JENIS PELANGGARAN / PRESTASI (RICH UI/UX) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold d-flex justify-content-between align-items-center mb-1">
                                <span>Jenis Pelanggaran / Prestasi <span class="text-danger">*</span></span>
                                <span class="badge bg-light text-muted border fw-normal" id="countPelanggaranInfo">Menampilkan <?= count($pelanggaranList) ?> item</span>
                            </label>

                            <!-- Tool Filter & Search Header -->
                            <div class="p-2 border rounded-top bg-light-subtle pelanggaran-filter-head">
                                <div class="row g-2 mb-2">
                                    <div class="col-sm-7">
                                        <div class="btn-group w-100" role="group" id="filterTypeGroup">
                                            <button type="button" class="btn btn-outline-secondary active" data-type="all">Semua</button>
                                            <button type="button" class="btn btn-outline-danger" data-type="pelanggaran"><i class="bi bi-exclamation-triangle-fill me-1"></i>Pelanggaran</button>
                                            <button type="button" class="btn btn-outline-success" data-type="prestasi"><i class="bi bi-trophy-fill me-1"></i>Prestasi</button>
                                        </div>
                                    </div>
                                    <div class="col-sm-5">
                                        <select id="filterKategori" class="form-select">
                                            <option value="">Semua Kategori</option>
                                            <?php foreach ($kategoriList as $k): ?>
                                                <option value="<?= e($k) ?>"><?= e($k) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="position-relative">
                                    <input type="text" id="cariPelanggaran" class="form-control ps-4" placeholder="🔍 Cari nama pelanggaran/prestasi, kode (PEL-01), dll..." autocomplete="off">
                                </div>
                            </div>

                            <!-- Input Hidden untuk Menyimpan ID Terpilih (Sesuai Form POST) -->
                            <input type="hidden" name="pelanggaran_id" id="pelanggaranIdInput" required>

                            <!-- Custom Scrollable List View -->
                            <div class="border border-top-0 rounded-bottom custom-select-list overflow-auto" id="pelanggaranListContainer" style="max-height: 290px; background: #fff;">
                                <?php foreach ($pelanggaranList as $p): 
                                    $poin = (int)$p['poin'];
                                    $isPrestasi = $poin <= 0;
                                    $typeClass = $isPrestasi ? 'prestasi' : 'pelanggaran';
                                ?>
                                    <div class="pelanggaran-item p-2 px-3 border-bottom d-flex align-items-center justify-content-between"
                                         data-id="<?= e($p['id']) ?>"
                                         data-poin="<?= $poin ?>"
                                         data-kategori="<?= e($p['kategori']) ?>"
                                         data-sub="<?= e($p['sub_kategori']) ?>"
                                         data-type="<?= $typeClass ?>"
                                         data-title="<?= e(mb_strtolower($p['jenis_pelanggaran'])) ?>"
                                         data-id-lower="<?= e(mb_strtolower($p['id'])) ?>"
                                         style="cursor: pointer; transition: background 0.15s ease, border-color 0.15s ease;">
                                        <div class="d-flex align-items-center gap-2 min-w-0 me-2">
                                            <div class="item-icon flex-shrink-0 d-flex align-items-center justify-content-center rounded-2" 
                                                 style="width: 40px; height: 40px; background: <?= $isPrestasi ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $isPrestasi ? '#059669' : '#dc2626' ?>;">
                                                <i class="bi <?= $isPrestasi ? 'bi-trophy-fill' : 'bi-exclamation-triangle-fill' ?>" style="font-size: 18px;"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-dark text-truncate item-title">
                                                    <?= e($p['jenis_pelanggaran']) ?>
                                                </div>
                                                <div class="d-flex align-items-center gap-1 flex-wrap mt-0-5 item-meta">
                                                    <span class="badge bg-light text-secondary border px-1 py-0-5"><?= e($p['id']) ?></span>
                                                    <span class="text-muted">•</span>
                                                    <span class="text-muted"><?= e($p['kategori']) ?></span>
                                                    <?php if (!empty($p['sub_kategori'])): ?>
                                                        <span class="text-muted">» <?= e($p['sub_kategori']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                            <?php if ($isPrestasi): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1 item-poin">
                                                    <?= $poin ?> Poin
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1 item-poin">
                                                    +<?= $poin ?> Poin
                                                </span>
                                            <?php endif; ?>
                                            <i class="bi bi-check-circle-fill text-success fs-4 select-check-icon d-none"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div id="noPelanggaranResult" class="p-4 text-center text-muted d-none item-meta">
                                    <i class="bi bi-search fs-3 text-secondary d-block mb-1"></i>
                                    Tidak ada pelanggaran / prestasi yang cocok.
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Catatan / Konteks</label>
                            <textarea name="catatan" class="form-control" rows="2" maxlength="500" placeholder="Opsional: deskripsi singkat kejadian..."></textarea>
                            <div class="form-text text-end"><span id="catatanCounter">0</span>/500</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Bukti URL (opsional)</label>
                            <input type="url" name="bukti_url" class="form-control" placeholder="https://drive.google.com/... (foto bukti)">
                        </div>
                    </div>

                    <!-- Preview Poin Card -->
                    <div class="alert alert-light border mt-3 mb-3 d-flex align-items-center justify-content-between" id="previewPoin">
                        <div>
                            <div class="text-muted">Preview Poin</div>
                            <div class="fw-bold fs-4" id="previewPoinText">-</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted">Kategori / Sub-Kategori</div>
                            <div id="previewKategori" class="fw-semibold">-</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-semibold btn-lg-pelanggaran" id="btnSimpan">
                        <i class="bi bi-check2-circle me-1"></i>Simpan Catatan Pelanggaran
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- INFO ALUR SP -->
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Alur SP Otomatis</div>
            <div class="card-body p-3 alur-sp-list">
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                    <li class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-circle-fill me-2" style="font-size:10px;color:#10b981"></i>0 – 24 poin</span>
                        <span class="badge bg-success-subtle text-success">Bebas SP</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-circle-fill me-2" style="font-size:10px;color:#0dcaf0"></i>25 – 49 poin</span>
                        <span class="badge bg-info-subtle text-info">SP-1</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-circle-fill me-2" style="font-size:10px;color:#ffc107"></i>50 – 74 poin</span>
                        <span class="badge bg-warning-subtle text-warning">SP-2</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-circle-fill me-2" style="font-size:10px;color:#dc3545"></i>75 – 99 poin</span>
                        <span class="badge bg-danger-subtle text-danger">SP-3</span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-circle-fill me-2" style="font-size:10px;color:#6f42c1"></i>≥ 100 poin</span>
                        <span class="badge bg-dark-subtle text-dark">Dikembalikan ke Ortum</span>
                    </li>
                </ul>
                <hr>
                <p class="mb-1 text-muted alur-sp-note">
                    <i class="bi bi-magic me-1"></i>Ketika poin siswa menyentuh ambang baru, sistem <strong>otomatis membuat draf Surat Peringatan</strong> yang siap dicetak.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-lightbulb me-2 text-warning"></i>Tips Input</div>
            <div class="card-body text-muted tips-input-list">
                <ul class="mb-0 ps-3">
                    <li>Pilih <strong>Prestasi / Penghargaan</strong> untuk pengurangan poin siswa.</li>
                    <li>Poin akan langsung <strong>dikurangi/ditambah</strong> & status SP dihitung ulang otomatis.</li>
                    <li>Gunakan kolom <em>Bukti URL</em> untuk foto/dokumen pendukung kejadian.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formPelanggaran');
    const kelasSelect = document.getElementById('pilihKelas');
    const siswaSelect = document.getElementById('pilihSiswa');
    const kategoriFilter = document.getElementById('filterKategori');
    const cariPelanggaran = document.getElementById('cariPelanggaran');
    const pelanggaranIdInput = document.getElementById('pelanggaranIdInput');
    const listContainer = document.getElementById('pelanggaranListContainer');
    const items = listContainer.querySelectorAll('.pelanggaran-item');
    const noResultMsg = document.getElementById('noPelanggaranResult');
    const countInfo = document.getElementById('countPelanggaranInfo');
    const typeButtons = document.querySelectorAll('#filterTypeGroup button');

    const previewPoin = document.getElementById('previewPoinText');
    const previewKategori = document.getElementById('previewKategori');
    const catatanInput = form.querySelector('[name=catatan]');

    let selectedType = 'all';

    // 1) Load siswa berdasarkan kelas
    kelasSelect.addEventListener('change', function () {
        siswaSelect.disabled = true;
        siswaSelect.innerHTML = '<option value="">Memuat siswa...</option>';
        const kelas = this.value;
        if (!kelas) { siswaSelect.innerHTML = '<option value="">— Pilih Kelas dulu —</option>'; return; }

        fetch('/pelanggaran/siswa-by-kelas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ kelas: kelas, csrf_token: '<?= e($csrf) ?>' })
        })
        .then(r => r.json())
        .then(res => {
            siswaSelect.innerHTML = '<option value="">— Pilih Siswa —</option>';
            if (res.ok && res.data.length) {
                res.data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = `No. ${s.absen} — ${s.nama}`;
                    siswaSelect.appendChild(opt);
                });
                siswaSelect.disabled = false;
            } else {
                siswaSelect.innerHTML = '<option value="">Tidak ada siswa di kelas ini</option>';
            }
        })
        .catch(() => { siswaSelect.innerHTML = '<option value="">Gagal memuat siswa</option>'; });
    });

    // 2) Filter Type Tabs (Semua, Pelanggaran, Prestasi)
    typeButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            typeButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            selectedType = this.dataset.type;
            applyFilterPelanggaran();
        });
    });

    // 3) Filter catalog (type + kategori + teks pencarian)
    function applyFilterPelanggaran() {
        const kat = kategoriFilter.value;
        const q = cariPelanggaran.value.trim().toLowerCase();
        let visibleCount = 0;

        items.forEach(item => {
            const itemType = item.dataset.type;
            const itemKat = item.dataset.kategori || '';
            const title = item.dataset.title || '';
            const idLower = item.dataset.idLower || '';
            const sub = (item.dataset.sub || '').toLowerCase();

            const matchType = (selectedType === 'all') || (itemType === selectedType);
            const matchKat = !kat || itemKat === kat;
            const matchQ = !q || title.includes(q) || idLower.includes(q) || sub.includes(q);

            if (matchType && matchKat && matchQ) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });

        if (visibleCount === 0) {
            noResultMsg.classList.remove('d-none');
        } else {
            noResultMsg.classList.add('d-none');
        }

        countInfo.textContent = `Menampilkan ${visibleCount} item`;
    }

    kategoriFilter.addEventListener('change', applyFilterPelanggaran);
    cariPelanggaran.addEventListener('input', applyFilterPelanggaran);

    // 4) Selection Event Handler
    items.forEach(item => {
        item.addEventListener('click', function () {
            // Unselect previous
            items.forEach(i => {
                i.classList.remove('selected', 'bg-success-subtle', 'border-success');
                i.querySelector('.select-check-icon').classList.add('d-none');
            });

            // Select current
            this.classList.add('selected', 'bg-success-subtle', 'border-success');
            this.querySelector('.select-check-icon').classList.remove('d-none');

            // Set hidden value & preview card
            const id = this.dataset.id;
            const poin = parseInt(this.dataset.poin, 10);
            const kat = this.dataset.kategori;
            const sub = this.dataset.sub;

            pelanggaranIdInput.value = id;
            listContainer.classList.remove('border-danger');

            previewPoin.textContent = (poin > 0 ? '+' : '') + poin + ' poin';
            previewPoin.className = 'fw-bold ' + (poin > 0 ? 'text-danger' : 'text-success');
            previewKategori.textContent = `${kat}${sub ? ' / ' + sub : ''}`;
        });
    });

    // Counter catatan
    catatanInput.addEventListener('input', () => {
        document.getElementById('catatanCounter').textContent = catatanInput.value.length;
    });

    // 5) Submit via AJAX
    form.addEventListener('submit', function (ev) {
        ev.preventDefault();

        // Validasi seleksi pelanggaran
        if (!pelanggaranIdInput.value) {
            listContainer.classList.add('border-danger');
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Jenis Pelanggaran / Prestasi',
                text: 'Silakan klik salah satu jenis pelanggaran atau prestasi dari daftar terlebih dahulu.',
                confirmButtonColor: '#059669'
            });
            return;
        }

        const btn = document.getElementById('btnSimpan');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

        const data = new URLSearchParams(new FormData(form));
        fetch('/pelanggaran/store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data
        })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Tersimpan!',
                    html: `<b>${res.siswa_nama}</b><br>Total poin: <b>${res.total_poin}</b> — ${res.status_sp}${res.sp_dibuat ? '<br><span class="badge bg-danger">SP baru diterbitkan otomatis!</span>' : ''}`,
                    confirmButtonColor: '#059669'
                });

                // Reset Form
                siswaSelect.innerHTML = '<option value="">— Pilih Kelas dulu —</option>';
                siswaSelect.disabled = true;
                pelanggaranIdInput.value = '';
                items.forEach(i => {
                    i.classList.remove('selected', 'bg-success-subtle', 'border-success');
                    i.querySelector('.select-check-icon').classList.add('d-none');
                });
                form.querySelector('[name=catatan]').value = '';
                form.querySelector('[name=bukti_url]').value = '';
                document.getElementById('catatanCounter').textContent = '0';
                previewPoin.textContent = '-';
                previewPoin.className = 'fw-bold';
                previewKategori.textContent = '-';
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.error || 'Terjadi kesalahan' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Kesalahan Jaringan', text: 'Tidak dapat terhubung ke server.' }))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Simpan Catatan Pelanggaran'; });
    });
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
