<?php
/** Model Poin Siswa & Surat Peringatan — prepared statements */
class PoinSiswaModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /** Hitung ulang total poin & status SP utk satu siswa */
    public function recalculate(string $siswaId): int {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(poin), 0) FROM log_pelanggaran WHERE siswa_id = ?");
        $stmt->execute([$siswaId]);
        $total = max(0, (int)$stmt->fetchColumn());

        $status = getSpStatusInfo($total)['status'];

        $upsert = $this->db->prepare("
            INSERT INTO poin_siswa (siswa_id, total_poin, status_sp, updated_at) VALUES (?, ?, ?, ?)
            ON CONFLICT(siswa_id) DO UPDATE SET total_poin = excluded.total_poin, status_sp = excluded.status_sp, updated_at = excluded.updated_at
        ");
        $upsert->execute([$siswaId, $total, $status, date('Y-m-d H:i:s')]);
        return $total;
    }

    public function get(string $siswaId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM poin_siswa WHERE siswa_id = ?");
        $stmt->execute([$siswaId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

class SpModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT sp.*, s.nama AS siswa_nama, s.kelas AS siswa_kelas, s.nisn AS siswa_nisn
            FROM surat_peringatan sp
            JOIN siswa s ON s.id = sp.siswa_id
            ORDER BY sp.tanggal DESC, sp.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function find(string $id): ?array {
        $stmt = $this->db->prepare("
            SELECT sp.*, s.nama AS siswa_nama, s.kelas AS siswa_kelas, s.nisn AS siswa_nisn, s.absen AS siswa_absen
            FROM surat_peringatan sp
            JOIN siswa s ON s.id = sp.siswa_id
            WHERE sp.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Buat draf SP otomatis ketika siswa naik level */
    public function createIfNeeded(string $siswaId, int $totalPoin): ?string {
        $info = getSpStatusInfo($totalPoin);
        if ($info['level'] === 0) return null; // Bebas SP, tidak perlu surat

        // Cek apakah sudah ada SP dgn tingkat tsb
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM surat_peringatan WHERE siswa_id = ? AND tingkat_sp = ?");
        $stmt->execute([$siswaId, $info['status']]);
        if ((int)$stmt->fetchColumn() > 0) return null;

        $id = 'SP-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 4);
        $noSurat = "054/Ma.11.18/PP.00.6/" . date('m/Y');
        $stmt = $this->db->prepare("INSERT INTO surat_peringatan (id, no_surat, tanggal, siswa_id, tingkat_sp, status) VALUES (?, ?, ?, ?, ?, 'Terbit')");
        $stmt->execute([$id, $noSurat, date('Y-m-d'), $siswaId, $info['status']]);
        return $id;
    }

    public function delete(string $id): void {
        $stmt = $this->db->prepare("DELETE FROM surat_peringatan WHERE id = ?");
        $stmt->execute([$id]);
    }
}