<?php
// includes/resend_otp.php

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

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

$email = $_SESSION['user_email'];

// Generate new OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store new OTP
$stmt = $pdo->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$email, $otp, $expires_at]);

// Delete old OTPs (cleanup)
$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE email = ? AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$email]);

echo json_encode([
    'success' => true,
    'otp' => $otp, // Remove in production
    'message' => 'OTP resent successfully!'
]);
?>