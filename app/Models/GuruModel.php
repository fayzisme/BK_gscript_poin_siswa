<?php
/** Model Guru — prepared statements, anti SQL Injection */
class GuruModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM guru ORDER BY nama ASC");
        return $stmt->fetchAll();
    }

    public function find(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM guru WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM guru WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByNip(string $nip): ?array {
        $stmt = $this->db->prepare("SELECT * FROM guru WHERE nip = ? LIMIT 1");
        $stmt->execute([$nip]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $id, string $nama, string $nip, string $jabatan, string $username = '', string $passwordHash = ''): void {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO guru (id, nama, nip, jabatan, username, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $nama, $nip, $jabatan, $username, $passwordHash]);
    }

    public function update(string $id, string $nama, string $nip, string $jabatan): void {
        $stmt = $this->db->prepare("UPDATE guru SET nama = ?, nip = ?, jabatan = ? WHERE id = ?");
        $stmt->execute([$nama, $nip, $jabatan, $id]);
    }

    public function delete(string $id): void {
        $stmt = $this->db->prepare("DELETE FROM guru WHERE id = ?");
        $stmt->execute([$id]);
    }
}