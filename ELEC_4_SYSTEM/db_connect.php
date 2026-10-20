<?php
// ============================================
// DATABASE CONNECTION - POSTGRESQL
// ============================================
function getDBConnection() {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'umak_registrar'; // PALITAN ITO
    $username = 'postgres';
    $password = 'Admin@123'; // PALITAN ITO - subukan ang iba't ibang password
    
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
?>