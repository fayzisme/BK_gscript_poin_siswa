<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/SiswaModel.php';
require_once __DIR__ . '/../Models/LogModel.php';
require_once __DIR__ . '/../Models/SpModel.php';
require_once __DIR__ . '/../Models/PelanggaranModel.php';

class DashboardController extends Controller {

    public function index(): void {
        Security::requireLogin();

        $siswaModel = new SiswaModel($this->db);
        $logModel = new LogModel($this->db);
        $spModel = new SpModel($this->db);

        // Statistik kart
        $totalSiswa = $siswaModel->count();
        $kenaSpSiswa = (int)$this->db->query("SELECT COUNT(*) FROM poin_siswa WHERE status_sp != 'Bebas SP'")->fetchColumn();
        $totalLog = (int)$this->db->query("SELECT COUNT(*) FROM log_pelanggaran")->fetchColumn();
        $logBulanIni = $logModel->countBulanIni();
        $totalSpTerbit = (int)$this->db->query("SELECT COUNT(*) FROM surat_peringatan")->fetchColumn();
        $keributanBulan = (int)$this->db->query("
            SELECT COUNT(*) FROM log_pelanggaran l
            JOIN master_pelanggaran m ON m.id = l.pelanggaran_id
            WHERE m.kategori = 'Kepribadian'
        ")->fetchColumn();

        // Chart data: distribusi per kategori
        $kategoriDist = $logModel->kategoriDistribution();
        $kategoriLabels = array_column($kategoriDist, 'kategori');
        $kategoriJumlah = arrayMap($kategoriDist, fn($r) => (int)$r['jumlah']);
        $kategoriPoin = arrayMap($kategoriDist, fn($r) => (int)$r['total_poin']);

        // Chart data: per sub_kategori
        $subDist = $logModel->subKategoriDistribution();
        $subLabels = arrayMap($subDist, fn($r) => (string)$r['sub_kategori']);
        $subJumlah = arrayMap($subDist, fn($r) => (int)$r['jumlah']);

        // Top siswa (per poin akumulasi)
        $topSiswa = $this->db->query("
            SELECT s.nama, s.kelas, ps.total_poin, ps.status_sp
            FROM poin_siswa ps
            JOIN siswa s ON s.id = ps.siswa_id
            WHERE ps.status_sp != 'Bebas SP'
            ORDER BY ps.total_poin DESC
            LIMIT 6
        ")->fetchAll();

        // Aktivitas terakhir
        $recentLogs = $logModel->recent(7);

        view('dashboard/index', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'user' => Security::user(),
            'totalSiswa' => $totalSiswa,
            'kenaSpSiswa' => $kenaSpSiswa,
            'totalLog' => $totalLog,
            'logBulanIni' => $logBulanIni,
            'totalSpTerbit' => $totalSpTerbit,
            'keributanBulan' => $keributanBulan,
            'kategoriLabels' => $kategoriLabels,
            'kategoriJumlah' => $kategoriJumlah,
            'kategoriPoin' => $kategoriPoin,
            'subLabels' => $subLabels,
            'subJumlah' => $subJumlah,
            'topSiswa' => $topSiswa,
            'recentLogs' => $recentLogs,
        ]);
    }
}