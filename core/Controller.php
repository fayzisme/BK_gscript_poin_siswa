<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Security.php';

class Controller {
    protected PDO $db;

    public function __construct() {
        Security::startSession();
        Security::sendSecurityHeaders();
        Database::initSchema();
        $this->db = Database::getConnection();
    }
}