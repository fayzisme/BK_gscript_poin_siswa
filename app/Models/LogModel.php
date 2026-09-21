<?php
/** Model Log Pelanggaran — prepared statements */
class LogModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create(string $id, string $tanggal, string $siswaId, string $pelanggaranId, int $poin, string $guruInput, string $catatan = '', string $buktiUrl = ''): void {
        $stmt = $this->db->prepare("INSERT INTO log_pelanggaran (id, tanggal, siswa_id, pelanggaran_id, poin, guru_input, catatan, bukti_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $tanggal, $siswaId, $pelanggaranId, $poin, $guruInput, $catatan, $buktiUrl]);
    }

    public function historyBySiswa(string $siswaId): array {
        $stmt = $this->db->prepare("
            SELECT l.*, m.kategori, m.sub_kategori, m.jenis_pelanggaran AS jenis
            FROM log_pelanggaran l
            JOIN master_pelanggaran m ON m.id = l.pelanggaran_id
            WHERE l.siswa_id = ?
            ORDER BY l.tanggal ASC, l.created_at ASC
        ");
        $stmt->execute([$siswaId]);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 8): array {
        $stmt = $this->db->prepare("
            SELECT l.*, s.nama AS siswa_nama, s.kelas AS siswa_kelas, m.jenis_pelanggaran, m.sub_kategori
            FROM log_pelanggaran l
            JOIN siswa s ON s.id = l.siswa_id
            JOIN master_pelanggaran m ON m.id = l.pelanggaran_id
            ORDER BY l.tanggal DESC, l.created_at DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countBulanIni(): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM log_pelanggaran WHERE strftime('%Y-%m', tanggal) = strftime('%Y-%m', 'now')");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /** Distribusi pelanggaran per kategori */
    public function kategoriDistribution(?int $limit = null): array {
        $sql = "SELECT m.kategori, SUM(l.poin) AS total_poin, COUNT(l.id) AS jumlah
                FROM log_pelanggaran l
                JOIN master_pelanggaran m ON m.id = l.pelanggaran_id
                GROUP BY m.kategori
                ORDER BY jumlah DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /** Distribusi per sub_kategori (untuk chart detail) */
    public function subKategoriDistribution(): array {
        $stmt = $this->db->query("
            SELECT m.sub_kategori, COUNT(l.id) AS jumlah
            FROM log_pelanggaran l
            JOIN master_pelanggaran m ON m.id = l.pelanggaran_id
            GROUP BY m.sub_kategori
            ORDER BY jumlah DESC
        ");
        return $stmt->fetchAll();
    }
}