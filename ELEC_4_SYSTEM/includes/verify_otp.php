<?php
// includes/verify_otp.php

header('Content-Type: application/json');
session_start();

// ============================================
// CHECK IF USER IS LOGGED IN
// ============================================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

// ============================================
// DATABASE CONNECTION
// ============================================
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

// ============================================
// GET OTP CODE FROM REQUEST
// ============================================
$input = json_decode(file_get_contents('php://input'), true);
$otp_code = trim($input['otp_code'] ?? '');

$user_id = $_SESSION['user_id'];

// ============================================
// DEBUG MODE: Accept any valid 6-digit code
// ============================================
// For testing purposes, any 6-digit number will work
// Remove this block in production!

if (strlen($otp_code) === 6 && is_numeric($otp_code)) {
    // Mark user as verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Create session for logged in user
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully!',
        'redirect' => '/ELEC_4_SYSTEM/htdocs/User_Dashboard.php'
    ]);
    exit;
}

// ============================================
// NORMAL OTP VALIDATION (if debug mode fails)
// ============================================
if (empty($otp_code) || strlen($otp_code) !== 6 || !is_numeric($otp_code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP format. Enter 6 digits.']);
    exit;
}

$email = $_SESSION['user_email'] ?? '';

// Check OTP in database
try {
    $stmt = $pdo->prepare("
        SELECT * FROM otp_codes 
        WHERE email = ? AND otp_code = ? AND is_used = 0 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$email, $otp_code]);
    $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otp_record) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please check your code and try again.']);
        exit;
    }

    // Check if OTP expired
    $expires_at = new DateTime($otp_record['expires_at']);
    $now = new DateTime();
    if ($now > $expires_at) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Request a new one.']);
        exit;
    }

    // Mark OTP as used
    $stmt = $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
    $stmt->execute([$otp_record['id']]);

    // Mark user as verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    // Create session for logged in user
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user_id;

    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully!',
        'redirect' => '/ELEC_4_SYSTEM/htdocs/User_Dashboard.php'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>