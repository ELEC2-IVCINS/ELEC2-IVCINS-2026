<?php
// config/db_connect_pg.php

class Database {
    private $host = 'localhost';
    private $port = '5432';
    private $dbname = 'umak_registrar';
    private $user = 'postgres';
    private $password = 'Admin@123';
    private $conn = null;

    // CONSTRUCTOR - ito ang kulang!
    public function __construct() {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            $this->conn = new PDO($dsn, $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
    error_log("PostgreSQL Connection failed: " . $e->getMessage());
    $this->conn = null;
    // echo "DEBUG ERROR: " . $e->getMessage() . "<br>";  // TEMPORARY - tanggalin mo ulit pagkatapos
}
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>