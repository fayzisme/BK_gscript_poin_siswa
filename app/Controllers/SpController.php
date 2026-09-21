<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/SpModel.php';
require_once __DIR__ . '/../Models/LogModel.php';

class SpController extends Controller {

    public function index(): void {
        Security::requireLogin();

        $spModel = new SpModel($this->db);

        view('sp/index', [
            'title' => 'Surat Peringatan (SP)',
            'active' => 'sp',
            'user' => Security::user(),
            'dataSp' => $spModel->all(),
        ]);
    }

    /** Preview cetak surat SP resmi */
    public function print(string $id): void {
        Security::requireLogin();

        $spModel = new SpModel($this->db);
        $sp = $spModel->find($id);
        if (!$sp) {
            http_response_code(404);
            die('Surat tidak ditemukan.');
        }

        // Rincian pelanggaran siswa
        $logModel = new LogModel($this->db);
        $history = $logModel->historyBySiswa($sp['siswa_id']);

        // Ambil poin status terkini
        $stmt = $this->db->prepare("SELECT total_poin FROM poin_siswa WHERE siswa_id = ?");
        $stmt->execute([$sp['siswa_id']]);
        $totalPoin = (int)($stmt->fetchColumn() ?: 0);

        view('sp/print', [
            'title' => 'Cetak Surat Peringatan',
            'sp' => $sp,
            'history' => $history,
            'totalPoin' => $totalPoin,
            'tanggalSekarang' => date('d F Y'),
        ]);
    }

    public function delete(): void {
        Security::requireRole('admin');
        Security::verifyCsrf();

        $id = Security::cleanId(Security::post('id'));
        if (!str_starts_with($id, 'SP-')) {
            jsonResponse(['ok' => false, 'error' => 'ID surat tidak valid'], 400);
        }

        try {
            (new SpModel($this->db))->delete($id);
        } catch (Throwable $e) {
            error_log("Gagal hapus SP: " . $e->getMessage());
            jsonResponse(['ok' => false, 'error' => 'Gagal menghapus surat'], 500);
        }
        jsonResponse(['ok' => true]);
    }
}