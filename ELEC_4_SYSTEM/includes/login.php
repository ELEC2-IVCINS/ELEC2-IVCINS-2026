<?php
// includes/login.php

header('Content-Type: application/json');
session_start();

// Database Connection
$host = 'localhost';
$dbname = 'umak_registrar';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// VALIDATION
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Log failed attempt
    logFailedAttempt($pdo, $email);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Check if account is locked
if ($user['lock_time']) {
    $lock_time = new DateTime($user['lock_time']);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $lock_time->getTimestamp();
    
    if ($diff < 300) { // 5 minutes lockout
        $remaining = 300 - $diff;
        echo json_encode([
            'success' => false,
            'message' => "Account locked. Please wait {$remaining} seconds before trying again."
        ]);
        exit;
    } else {
        // Reset lock if time passed
        $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_time = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
    }
}

// Verify password
if (!password_verify($password, $user['password'])) {
    // Increment failed attempts
    $failed_attempts = $user['failed_attempts'] + 1;
    $lock_time = ($failed_attempts >= 5) ? date('Y-m-d H:i:s') : null;
    
    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = ?, lock_time = ? WHERE id = ?");
    $stmt->execute([$failed_attempts, $lock_time, $user['id']]);
    
    logFailedAttempt($pdo, $email);
    
    if ($failed_attempts >= 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Too many failed attempts. Account locked for 5 minutes.'
        ]);
    } else {
        $attempts_left = 5 - $failed_attempts;
        echo json_encode([
            'success' => false,
            'message' => "Invalid password. {$attempts_left} attempts remaining."
        ]);
    }
    exit;
}

// Reset failed attempts on successful login
$stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_time = NULL, last_login = NOW() WHERE id = ?");
$stmt->execute([$user['id']]);

// GENERATE OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store OTP in database
$stmt = $pdo->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$email, $otp, $expires_at]);

// Store user session data
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['full_name'];

echo json_encode([
    'success' => true,
    'requires_otp' => true,
    'otp' => $otp, // In production, remove this. Only for development/testing.
    'message' => 'OTP sent to your email.',
    'user' => [
        'id' => $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email']
    ]
]);

// Function to log failed attempts
function logFailedAttempt($pdo, $email) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
}
?>