<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/SiswaModel.php';
require_once __DIR__ . '/../Models/LogModel.php';

class ExportController extends Controller {

    /** Ekspor rekap poin seluruh siswa (CSV streaming, memory rendah) */
    public function rekapCsv(): void {
        Security::requireLogin();

        // Anti-cache & force download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rekap-poin-siswa-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 agar Excel terbaca

        fputcsv($out, ['NISN', 'Nama', 'Kelas', 'Absen', 'Total Poin', 'Status SP'], ',', '"', '\\');

        $sql = "SELECT s.nisn, s.nama, s.kelas, s.absen,
                       COALESCE(ps.total_poin, 0) AS total_poin,
                       COALESCE(ps.status_sp, 'Bebas SP') AS status_sp
                FROM siswa s
                LEFT JOIN poin_siswa ps ON ps.siswa_id = s.id
                ORDER BY s.kelas ASC, s.absen ASC";
        $stmt = $this->db->query($sql);

        while ($row = $stmt->fetch()) {
            fputcsv($out, [$row['nisn'], $row['nama'], $row['kelas'], $row['absen'], $row['total_poin'], $row['status_sp']], ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /** Ekspor riwayat pelanggaran (CSV streaming) */
    public function logCsv(): void {
        Security::requireLogin();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="riwayat-pelanggaran-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Tanggal', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Kategori', 'Sub Kategori', 'Poin', 'Guru Input', 'Catatan'], ',', '"', '\\');

        $sql = "SELECT l.tanggal, s.nisn, s.nama, s.kelas, m.jenis_pelanggaran, m.kategori, m.sub_kategori,
                       l.poin, l.guru_input, l.catatan
                FROM log_pelanggaran l
                JOIN siswa s ON s.id = l.siswa_id
                JOIN master_pelanggaran m ON m.id = l.pelanggaran_id
                ORDER BY l.tanggal DESC";
        $stmt = $this->db->query($sql);

        while ($row = $stmt->fetch()) {
            fputcsv($out, [
                $row['tanggal'], $row['nisn'], $row['nama'], $row['kelas'],
                $row['jenis_pelanggaran'], $row['kategori'], $row['sub_kategori'],
                $row['poin'], $row['guru_input'], $row['catatan'],
            ], ',', '"', '\\');
        }
        fclose($out);
        exit;
    }
}