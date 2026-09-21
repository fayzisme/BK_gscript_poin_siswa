<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/SiswaModel.php';
require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../Models/SpModel.php';

class SiswaController extends Controller {

    public function rekap(): void {
        Security::requireLogin();

        $kelas = Security::cleanKelas(Security::get('kelas'));
        $search = substr(Security::get('q'), 0, 60);

        $siswaModel = new SiswaModel($this->db);
        $dataSiswa = $siswaModel->getWithPoin($kelas !== '' ? $kelas : null, $search !== '' ? $search : null);

        // Kelompokkan per kelas
        $grouped = [];
        foreach ($dataSiswa as $s) {
            $grouped[$s['kelas']][] = $s;
        }
        // Sortir kelas
        ksort($grouped);

        view('siswa/rekap', [
            'title' => 'Rekapitulasi Poin Siswa',
            'active' => 'rekap',
            'user' => Security::user(),
            'kelasList' => $siswaModel->classes(),
            'dataSiswa' => $grouped,
            'kelasAktif' => $kelas,
            'search' => $search,
        ]);
    }

    /** AJAX: riwayat pelanggaran per siswa */
    public function riwayat(): void {
        Security::requireLogin();
        $siswaId = Security::cleanId(Security::post('siswa_id'));

        $siswaModel = new SiswaModel($this->db);
        $siswa = $siswaModel->find($siswaId);
        if (!$siswa) {
            jsonResponse(['ok' => false, 'error' => 'Siswa tidak ditemukan'], 404);
        }

        $logModel = new LogModel($this->db);
        $history = $logModel->historyBySiswa($siswaId);

        $poinModel = new PoinSiswaModel($this->db);
        $poin = $poinModel->get($siswaId);
        $totalPoin = $poin['total_poin'] ?? 0;
        $statusSp = $poin['status_sp'] ?? 'Bebas SP';

        jsonResponse([
            'ok' => true,
            'siswa' => [
                'nama' => $siswa['nama'],
                'kelas' => $siswa['kelas'],
                'absen' => $siswa['absen'],
                'nisn' => $siswa['nisn'],
            ],
            'total_poin' => (int)$totalPoin,
            'status_sp' => $statusSp,
            'history' => arrayMap($history, fn($h) => [
                'tanggal' => $h['tanggal'],
                'jenis' => $h['jenis'],
                'kategori' => $h['kategori'],
                'sub_kategori' => $h['sub_kategori'],
                'poin' => (int)$h['poin'],
                'guru' => $h['guru_input'],
                'catatan' => $h['catatan'],
            ], $history),
        ]);
    }
}