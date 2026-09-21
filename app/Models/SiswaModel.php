<?php
/** Model Siswa — prepared statements, anti SQL Injection */
class SiswaModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function all(?string $kelas = null): array {
        if ($kelas !== null && $kelas !== '') {
            $stmt = $this->db->prepare("SELECT * FROM siswa WHERE kelas = ? ORDER BY absen ASC");
            $stmt->execute([$kelas]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query("SELECT * FROM siswa ORDER BY kelas ASC, absen ASC");
        return $stmt->fetchAll();
    }

    public function find(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM siswa WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByNisn(string $nisn): ?array {
        $stmt = $this->db->prepare("SELECT * FROM siswa WHERE nisn = ? LIMIT 1");
        $stmt->execute([$nisn]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function classes(): array {
        $stmt = $this->db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC");
        return array_column($stmt->fetchAll(), 'kelas');
    }

    public function getWithPoin(?string $kelas = null, ?string $search = null): array {
        $sql = "SELECT s.*, 
                       COALESCE(ps.total_poin, 0) AS total_poin, 
                       COALESCE(ps.status_sp, 'Bebas SP') AS status_sp
                FROM siswa s
                LEFT JOIN poin_siswa ps ON ps.siswa_id = s.id
                WHERE 1=1";

        $params = [];
        if ($kelas !== null && $kelas !== '') {
            $sql .= " AND s.kelas = ?";
            $params[] = $kelas;
        }
        if ($search !== null && $search !== '') {
            $sql .= " AND (s.nama LIKE ? OR s.nisn LIKE ? OR s.absen LIKE ?)";
            $like = "%" . $search . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY s.kelas ASC, s.absen ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(string $id, string $nisn, string $nama, string $kelas, int $absen, string $jk): void {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO siswa (id, nisn, nama, kelas, absen, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $nisn, $nama, $kelas, $absen, $jk]);
    }

    public function update(string $id, string $nisn, string $nama, string $kelas, int $absen, string $jk): void {
        $stmt = $this->db->prepare("UPDATE siswa SET nisn = ?, nama = ?, kelas = ?, absen = ?, jenis_kelamin = ? WHERE id = ?");
        $stmt->execute([$nisn, $nama, $kelas, $absen, $jk, $id]);
    }

    public function delete(string $id): void {
        $stmt = $this->db->prepare("DELETE FROM siswa WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function count(?string $kelas = null): int {
        if ($kelas) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE kelas = ?");
            $stmt->execute([$kelas]);
            return (int)$stmt->fetchColumn();
        }
        return (int)$this->db->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
    }
}