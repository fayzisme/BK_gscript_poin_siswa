<?php
/** Model Auth — prepared statements */
class AuthModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM guru WHERE username = ? AND username != '' LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Buat akun pengguna dgn password hashed (bcrypt) */
    public function createUser(string $guruId, string $username, string $password): void {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare("UPDATE guru SET username = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$username, $hash, $guruId]);
    }

    public function updatePassword(string $guruId, string $password): void {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare("UPDATE guru SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $guruId]);
    }
}