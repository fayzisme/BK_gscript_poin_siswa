<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/PelanggaranModel.php';

class MasterPelanggaranController extends Controller {

    public function index(): void {
        Security::requireLogin();

        $kategori = substr(Security::get('kategori'), 0, 30);
        $search = substr(Security::get('q'), 0, 60);

        $pelanggaranModel = new PelanggaranModel($this->db);
        $semua = $pelanggaranModel->all();

        // Filter sisi server
        if ($kategori !== '') {
            $semua = array_filter($semua, fn($p) => $p['kategori'] === $kategori);
        }
        if ($search !== '') {
            $semua = array_filter($semua, fn($p) => stripos($p['jenis_pelanggaran'], $search) !== false || stripos($p['id'], $search) !== false);
        }
        $semua = array_values($semua);

        view('master/index', [
            'title' => 'Katalog Tata Tertib & Poin',
            'active' => 'katalog',
            'user' => Security::user(),
            'csrf' => Security::csrfToken(),
            'kategoriList' => $pelanggaranModel->categories(),
            'dataPelanggaran' => $semua,
            'kategoriAktif' => $kategori,
            'search' => $search,
        ]);
    }

    public function store(): void {
        Security::requireRole('admin');
        Security::verifyCsrf();

        $id = strtoupper(Security::cleanId(Security::post('id')));
        $kategori = ucfirst(strtolower(substr(Security::post('kategori'), 0, 30)));
        $subKategori = substr(Security::post('sub_kategori'), 0, 50);
        $jenis = substr(Security::post('jenis_pelanggaran'), 0, 300);
        $poin = Security::cleanInt(Security::post('poin'), -500, 500);

        $validKategori = ['Kepribadian', 'Kerajinan', 'Kerapian', 'Penghargaan'];
        if (!in_array($kategori, $validKategori, true)) {
            jsonResponse(['ok' => false, 'error' => 'Kategori tidak valid'], 400);
        }
        if (!$id || !$jenis) {
            jsonResponse(['ok' => false, 'error' => 'ID dan jenis pelanggaran wajib diisi'], 400);
        }
        if (!preg_match('/^[PH]\d{2}$/', $id)) {
            jsonResponse(['ok' => false, 'error' => 'Format ID harus P01 / H01'], 400);
        }
        if ($poin === 0) {
            jsonResponse(['ok' => false, 'error' => 'Poin tidak boleh 0'], 400);
        }

        $model = new PelanggaranModel($this->db);
        try {
if ($model->find($id)) {
                $model->update($id, $kategori, $subKategori, $jenis, $poin);
            } else {
                $model->create($id, $kategori, $subKategori, $jenis, $poin);
            }
        } catch (Throwable $e) {
            error_log("Gagal simpan master: " . $e->getMessage());
            jsonResponse(['ok' => false, 'error' => 'Gagal menyimpan katalog'], 500);
        }

        jsonResponse(['ok' => true, 'id' => $id]);
    }

    public function delete(): void {
        Security::requireRole('admin');
        Security::verifyCsrf();

        $id = strtoupper(Security::cleanId(Security::post('id')));
        $model = new PelanggaranModel($this->db);

        // Cek apakah sudah dipakai di log (integritas referensial)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM log_pelanggaran WHERE pelanggaran_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            jsonResponse(['ok' => false, 'error' => 'Tidak bisa dihapus: sudah dipakai di log pelanggaran'], 409);
        }

        try {
            $model->delete($id);
        } catch (Throwable $e) {
            error_log("Gagal hapus master: " . $e->getMessage());
            jsonResponse(['ok' => false, 'error' => 'Gagal menghapus'], 500);
        }
        jsonResponse(['ok' => true]);
    }

    /** Restore master data dari seed (khusus admin) */
    public function restoreDefault(): void {
        Security::requireRole('admin');
        Security::verifyCsrf();

        require_once __DIR__ . '/../../database/seed_master.php';
        $count = seedMasterPelanggaran($this->db);

        jsonResponse(['ok' => true, 'count' => $count, 'message' => "Master data berhasil di-restore ($count item)"]);
    }
}