<?php
// ============================================
// EMAIL CONFIGURATION - PHPMailer (MANUAL)
// ============================================

// Manual include ng PHPMailer files (since walang Composer)
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendOTPEmail($recipientEmail, $recipientName, $otpCode) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'joanneshiela5@gmail.com';
        $mail->Password   = 'izaj lsem kvyc wtav';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('joanneshiela5@gmail.com', 'UMak Registrar');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->addReplyTo('no-reply@umak.edu.ph', 'No Reply');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = '🔐 Your OTP Verification Code - UMak Registrar';
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #FFD700; padding-bottom: 15px; }
                .header h1 { color: #003366; margin: 0; }
                .header h1 span { color: #FFD700; }
                .otp-code { 
                    background: #f8f7f4; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 32px; 
                    font-weight: bold; 
                    letter-spacing: 8px;
                    color: #003366;
                    border-radius: 6px;
                    margin: 20px 0;
                }
                .footer { text-align: center; color: #888; font-size: 12px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
                .warning { color: #dc2626; font-size: 13px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>UMak<span>·Registrar</span></h1>
                    <p style="color: #666;">University of Makati</p>
                </div>
                <p>Hello <strong>' . htmlspecialchars($recipientName) . '</strong>,</p>
                <p>You requested a verification code for your UMak Registrar account. Use the OTP code below:</p>
                <div class="otp-code">' . $otpCode . '</div>
                <p>This code will expire in <strong>10 minutes</strong>.</p>
                <p class="warning">⚠️ If you didn\'t request this, please ignore this email or contact our support.</p>
                <div class="footer">
                    <p>&copy; 2026 University of Makati · Registrar\'s Office</p>
                    <p>Email: registrar@umak.edu.ph | Contact: (02) 1234-5678</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->AltBody = "UMak Registrar - OTP Verification\n\n";
        $mail->AltBody .= "Hello " . $recipientName . ",\n\n";
        $mail->AltBody .= "Your OTP verification code is: " . $otpCode . "\n\n";
        $mail->AltBody .= "This code will expire in 10 minutes.\n\n";
        $mail->AltBody .= "If you didn't request this, please ignore this email.\n\n";
        $mail->AltBody .= "© 2026 University of Makati · Registrar's Office";
        
        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully to ' . $recipientEmail];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send OTP: ' . $mail->ErrorInfo];
    }
}
?>