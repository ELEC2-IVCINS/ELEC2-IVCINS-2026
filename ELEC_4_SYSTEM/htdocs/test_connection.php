<?php
// test_connection.php
require_once __DIR__ . '/../config/db_connect.php';

echo "=== Testing Database Connection ===<br><br>";

try {
    $pdo = getDBConnection();
    echo "✅ Connection successful!<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Query executed successfully!<br><br>";
    
    echo "Connected to database: " . DB_NAME;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>