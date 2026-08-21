<?php
// ============================================
// DATABASE CONNECTION - umak_registrar
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'umak_registrar');
define('DB_USER', 'root');
define('DB_PASS', '');

// ============================================
// START SESSION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// PDO CONNECTION FUNCTION
// ============================================
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}
?>