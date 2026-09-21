<?php
/** Model Master Pelanggaran / Tatib — prepared statements */
class PelanggaranModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function all(?string $kategori = null, ?string $subKategori = null): array {
        $sql = "SELECT * FROM master_pelanggaran WHERE 1=1";
        $params = [];
        if ($kategori) {
            $sql .= " AND kategori = ?";
            $params[] = $kategori;
        }
        if ($subKategori) {
            $sql .= " AND sub_kategori = ?";
            $params[] = $subKategori;
        }
        $sql .= " ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM master_pelanggaran WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function categories(): array {
        return array_column($this->db->query("SELECT DISTINCT kategori FROM master_pelanggaran ORDER BY kategori ASC")->fetchAll(), 'kategori');
    }

    public function subCategories(?string $kategori = null): array {
        if ($kategori) {
            $stmt = $this->db->prepare("SELECT DISTINCT sub_kategori FROM master_pelanggaran WHERE kategori = ? ORDER BY sub_kategori ASC");
            $stmt->execute([$kategori]);
            return array_column($stmt->fetchAll(), 'sub_kategori');
        }
        return array_column($this->db->query("SELECT DISTINCT sub_kategori FROM master_pelanggaran ORDER BY sub_kategori ASC")->fetchAll(), 'sub_kategori');
    }

    public function create(string $id, string $kategori, string $subKategori, string $jenisPelanggaran, int $poin): void {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO master_pelanggaran (id, kategori, sub_kategori, jenis_pelanggaran, poin) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $kategori, $subKategori, $jenisPelanggaran, $poin]);
    }

    public function update(string $id, string $kategori, string $subKategori, string $jenisPelanggaran, int $poin): void {
        $stmt = $this->db->prepare("UPDATE master_pelanggaran SET kategori = ?, sub_kategori = ?, jenis_pelanggaran = ?, poin = ? WHERE id = ?");
        $stmt->execute([$kategori, $subKategori, $jenisPelanggaran, $poin, $id]);
    }

    public function delete(string $id): void {
        $stmt = $this->db->prepare("DELETE FROM master_pelanggaran WHERE id = ?");
        $stmt->execute([$id]);
    }
}