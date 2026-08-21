<?php
// ============================================
// TEST DATABASE CONNECTION
// ============================================

// Try to include db_connect.php
$configPath = __DIR__ . '/../config/db_connect.php';

echo "<h1>Database Connection Test</h1>";

if (file_exists($configPath)) {
    echo "<p style='color:green'>✅ db_connect.php found at: " . $configPath . "</p>";
    require_once $configPath;
} else {
    echo "<p style='color:red'>❌ db_connect.php NOT found at: " . $configPath . "</p>";
    exit();
}

// Check if function exists
if (function_exists('getDBConnection')) {
    echo "<p style='color:green'>✅ getDBConnection function exists</p>";
} else {
    echo "<p style='color:red'>❌ getDBConnection function NOT found</p>";
    exit();
}

// Try to connect
echo "<h2>Attempting to connect...</h2>";

try {
    $pdo = getDBConnection();
    
    if ($pdo) {
        echo "<p style='color:green'>✅ Database connected successfully!</p>";
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        echo "<p>Total users in database: " . $result['total'] . "</p>";
        
    } else {
        echo "<p style='color:red'>❌ Database connection failed!</p>";
        echo "<p>Error: getDBConnection() returned null. Please check your database settings.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Show current settings
echo "<h2>Current Settings:</h2>";
echo "<pre>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
echo "DB_PASS: " . (defined('DB_PASS') ? '*****' : 'NOT DEFINED') . "\n";
echo "</pre>";

// ============================================
// ADDITIONAL DEBUGGING - Check if database exists
// ============================================
echo "<h2>Additional Debugging:</h2>";

try {
    // Try to connect without selecting database
    $pdo_test = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo_test->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>✅ Database '" . DB_NAME . "' exists</p>";
    } else {
        echo "<p style='color:red'>❌ Database '" . DB_NAME . "' does NOT exist!</p>";
        echo "<p>Please create the database in phpMyAdmin.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Cannot connect to MySQL: " . $e->getMessage() . "</p>";
    echo "<p>Please check if MySQL is running in XAMPP.</p>";
}
?>