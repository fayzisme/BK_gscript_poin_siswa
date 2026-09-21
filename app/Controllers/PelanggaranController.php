<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/SiswaModel.php';
require_once __DIR__ . '/../Models/PelanggaranModel.php';
require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../Models/SpModel.php';

class PelanggaranController extends Controller {

    public function form(): void {
        Security::requireLogin();

        $siswaModel = new SiswaModel($this->db);
        $pelanggaranModel = new PelanggaranModel($this->db);

        view('pelanggaran/input', [
            'title' => 'Input Pelanggaran & Prestasi',
            'active' => 'input',
            'user' => Security::user(),
            'csrf' => Security::csrfToken(),
            'siswaList' => $siswaModel->all(),
            'kelasList' => $siswaModel->classes(),
            'pelanggaranList' => $pelanggaranModel->all(),
            'kategoriList' => $pelanggaranModel->categories(),
            'subKategoriList' => $pelanggaranModel->subCategories(),
        ]);
    }

    /** Endpoint AJAX: siswa per kelas */
    public function siswaByKelas(): void {
        Security::requireLogin();
        $kelasResult = Security::cleanKelas(Security::post('kelas'));
        if ($kelasResult === '') {
            jsonResponse(['ok' => false, 'error' => 'Format kelas tidak valid'], 400);
        }
        $model = new SiswaModel($this->db);
        $siswa = $model->all($kelasResult);
        jsonResponse(['ok' => true, 'data' => arrayMap($siswa, fn($s) => [
            'id' => $s['id'],
            'nama' => $s['nama'],
            'absen' => (int)$s['absen'],
        ], $siswa)]);
    }

    /** Endpoint AJAX: poin per pelanggaran */
    public function poinByPelanggaran(): void {
        Security::requireLogin();
        $idPelanggaran = Security::cleanId(Security::post('id'));
        $model = new PelanggaranModel($this->db);
        $item = $model->find($idPelanggaran);
        if (!$item) {
            jsonResponse(['ok' => false],404);
        }
        jsonResponse(['ok' => true,'poin' => (int)$item['poin'],'kategori' => $item['kategori'],'sub_kategori' => $item['sub_kategori']]);
    }

    /** Proses simpan pelanggaran / prestasi */
    public function store(): void {
        Security::requireLogin();
        Security::verifyCsrf();

        $siswaId = Security::cleanId(Security::post('siswa_id'));
        $pelanggaranId = Security::cleanId(Security::post('pelanggaran_id'));
        $tanggal = Security::cleanDate(Security::post('tanggal'));
        $catatan = substr(Security::post('catatan'), 0, 500);
        $buktiUrl = Security::cleanUrl(Security::post('bukti_url'));
        $guruInput = Security::user()['nama'] ?? 'Guru';

        // Validasi referensial
        $siswaModel = new SiswaModel($this->db);
        $siswa = $siswaModel->find($siswaId);
        if (!$siswa) {
            jsonResponse(['ok' => false, 'error' => 'Siswa tidak ditemukan'],404);
        }

        $pelanggaranModel = new PelanggaranModel($this->db);
        $pelanggaran = $pelanggaranModel->find($pelanggaranId);
        if (!$pelanggaran) {
            jsonResponse(['ok' => false, 'error' => 'Jenis pelanggaran tidak ditemukan'],404);
        }
        $poin = (int)$pelanggaran['poin'];

        // Validasi tambahan
        if (strtotime($tanggal) > time()) {
            jsonResponse(['ok' => false, 'error' => 'Tanggal tidak boleh di masa depan'],400);
        }

        // ── Transaction: simpan log → rekalkulasi poin → generate SP otomatis ──
        $logModel = new LogModel($this->db);
        $poinModel = new PoinSiswaModel($this->db);
        $spModel = new SpModel($this->db);

        try {
            $this->db->beginTransaction();

            $logId = 'LOG-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 4);
            $logModel->create($logId, $tanggal, $siswaId, $pelanggaranId, $poin, $guruInput, $catatan, $buktiUrl);

            $totalBaru = $poinModel->recalculate($siswaId);
            $spDibuat = $spModel->createIfNeeded($siswaId, $totalBaru);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Gagal simpan pelanggaran: " . $e->getMessage());
            jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan data. Silakan coba lagi.'],500);
        }

        jsonResponse([
            'ok' => true,
            'siswa_nama' => $siswa['nama'],
            'total_poin' => $totalBaru,
            'status_sp' => getSpStatusInfo($totalBaru)['status'],
            'sp_dibuat' => $spDibuat !== null,
        ]);
    }
}