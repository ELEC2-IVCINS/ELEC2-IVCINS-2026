<?php
session_start();
header('Content-Type: application/json');

// ============================================
// DEBUG: CHECK ALL PATHS
// ============================================
$paths_to_try = [
    __DIR__ . '/../config/db_connect.php',
    '../config/db_connect.php',
    '../../config/db_connect.php',
    './config/db_connect.php',
    '/config/db_connect.php'
];

$found = false;
$found_path = '';

foreach ($paths_to_try as $path) {
    if (file_exists($path)) {
        $found = true;
        $found_path = $path;
        require_once $path;
        break;
    }
}

if (!$found) {
    // Return error with debug info
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection file not found. Please check paths.',
        'debug' => [
            'current_dir' => __DIR__,
            'tried_paths' => $paths_to_try,
            'directory_contents' => scandir(__DIR__ . '/..')
        ]
    ]);
    exit();
}

// ============================================
// GET POST DATA
// ============================================
$fullName = trim($_POST['full_name'] ?? '');
$birthdate = $_POST['birthdate'] ?? '';
$email = trim($_POST['email'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$userIdNumber = trim($_POST['user_id_number'] ?? '');
$role = $_POST['role'] ?? 'student';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// ============================================
// VALIDATION
// ============================================

// Check empty fields
if (empty($fullName)) {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit();
}
if (empty($birthdate)) {
    echo json_encode(['success' => false, 'message' => 'Birthdate is required']);
    exit();
}
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit();
}
if (empty($mobile)) {
    echo json_encode(['success' => false, 'message' => 'Mobile number is required']);
    exit();
}
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit();
}
if (empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'Please confirm your password']);
    exit();
}

// Check if passwords match
if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Validate mobile number
$cleanedMobile = preg_replace('/[\s\-+]/', '', $mobile);
if (!preg_match('/^(09|\+639|639)\d{9}$/', $cleanedMobile)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number. Must be a valid Philippine number (e.g., 09171234567)']);
    exit();
}

// Validate password strength
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit();
}
if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one uppercase letter']);
    exit();
}
if (!preg_match('/[a-z]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one lowercase letter']);
    exit();
}
if (!preg_match('/[0-9]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one number']);
    exit();
}
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one special character (!@#$%^&*)']);
    exit();
}

// ============================================
// DATABASE OPERATIONS
// ============================================
try {
    // Check if getDBConnection function exists
    if (!function_exists('getDBConnection')) {
        echo json_encode(['success' => false, 'message' => 'getDBConnection function not found in db_connect.php']);
        exit();
    }
    
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Please check your database settings.']);
        exit();
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit();
    }
    
    // Check if mobile already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE mobile = ?");
    $stmt->execute([$cleanedMobile]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Mobile number already registered']);
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (full_name, birthdate, email, mobile, password, is_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$fullName, $birthdate, $email, $cleanedMobile, $hashedPassword]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        
        // Try to save role if column exists
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$role, $userId]);
            }
        } catch (PDOException $e) {
            // Role column doesn't exist, ignore
        }
        
        // Try to save user_id_number if column exists
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'user_id_number'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE users SET user_id_number = ? WHERE id = ?");
                $stmt->execute([$userIdNumber, $userId]);
            }
        } catch (PDOException $e) {
            // user_id_number column doesn't exist, ignore
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully! Please login to continue.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>