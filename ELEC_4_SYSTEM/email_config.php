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



// ============================================
// DOCUMENT REQUEST NOTIFICATION EMAIL
// ============================================

function sendRequestNotification($recipientEmail, $recipientName, $ticketNumber, $requestType, $status, $extraMessage = '') {
    $mail = new PHPMailer(true);

    try {
        // Same SMTP config (kapareho ng OTP)
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

        // Status-specific content
        $statusUpper = strtoupper($status);

        $statusConfig = [
            'PROCESSING' => [
                'subject'  => '📋 Your Document Request is Now Processing',
                'headline' => 'Request Accepted',
                'color'    => '#2563eb',
                'bgColor'  => '#dbeafe',
                'icon'     => '📋',
                'message'  => 'Good news! Your document request has been accepted and is now being processed by our staff.',
                'nextStep' => 'You will receive another email once your document is ready for pickup.',
            ],
            'READY' => [
                'subject'  => '🎉 Your Document is Ready for Pickup!',
                'headline' => 'Ready for Pickup',
                'color'    => '#059669',
                'bgColor'  => '#d1fae5',
                'icon'     => '✅',
                'message'  => 'Your document is now ready for pickup at the Registrar\'s Office.',
                'nextStep' => 'Please bring a valid ID and your ticket number when claiming.',
            ],
            'COMPLETED' => [
                'subject'  => '✅ Document Successfully Claimed',
                'headline' => 'Completed',
                'color'    => '#059669',
                'bgColor'  => '#d1fae5',
                'icon'     => '🎊',
                'message'  => 'Thank you! Your document has been successfully claimed.',
                'nextStep' => 'If you need another document, you may file a new request anytime.',
            ],
            'REJECTED' => [
                'subject'  => '❌ Document Request Update',
                'headline' => 'Request Rejected',
                'color'    => '#dc2626',
                'bgColor'  => '#fee2e2',
                'icon'     => '❌',
                'message'  => 'Unfortunately, your document request has been rejected.',
                'nextStep' => 'Please visit the Registrar\'s Office for more information.',
            ],
            'PENDING' => [
                'subject'  => '⏳ Document Request Received',
                'headline' => 'Request Received',
                'color'    => '#d97706',
                'bgColor'  => '#fef3c7',
                'icon'     => '⏳',
                'message'  => 'We have received your document request and it is now pending review.',
                'nextStep' => 'You will receive another email once we start processing your request.',
            ],
        ];

        $cfg = $statusConfig[$statusUpper] ?? $statusConfig['PENDING'];

        $mail->isHTML(true);
        $mail->Subject = $cfg['subject'];

        $ticketBlock = !empty($ticketNumber) ? '
            <tr>
                <td style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 14px;"><strong>Ticket #</strong></td>
                <td style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-size: 14px; font-weight: 700; font-family: monospace;">' . htmlspecialchars($ticketNumber) . '</td>
            </tr>
        ' : '';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="margin: 0; padding: 20px; background-color: #f1f5f9; font-family: Arial, Helvetica, sans-serif;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                
                <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 30px 40px; text-align: center;">
                    <h1 style="margin: 0; color: #ffffff; font-size: 24px;">U<span style="color: #3b82f6;">Mak</span> <span style="color: #64748b; font-weight: 400; font-size: 18px;">· Registrar</span></h1>
                    <p style="margin: 8px 0 0; color: #94a3b8; font-size: 13px;">University of Makati</p>
                </div>

                <div style="background: ' . $cfg['bgColor'] . '; padding: 20px 40px; text-align: center; border-bottom: 3px solid ' . $cfg['color'] . ';">
                    <div style="font-size: 36px; margin-bottom: 8px;">' . $cfg['icon'] . '</div>
                    <h2 style="margin: 0; color: ' . $cfg['color'] . '; font-size: 20px;">' . $cfg['headline'] . '</h2>
                </div>

                <div style="padding: 30px 40px;">
                    <p style="margin: 0 0 15px; color: #334155; font-size: 15px;">Hello <strong>' . htmlspecialchars($recipientName) . '</strong>,</p>
                    <p style="margin: 0 0 20px; color: #475569; font-size: 14px; line-height: 1.6;">' . $cfg['message'] . '</p>

                    <table style="width: 100%; border-collapse: collapse; background: #f8fafc; border-radius: 8px; overflow: hidden; margin: 20px 0;">
                        ' . $ticketBlock . '
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 14px; width: 40%;"><strong>Document Type</strong></td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-size: 14px;">' . htmlspecialchars($requestType) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 14px;"><strong>Status</strong></td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0;">
                                <span style="display: inline-block; background: ' . $cfg['bgColor'] . '; color: ' . $cfg['color'] . '; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; text-transform: uppercase;">' . htmlspecialchars($statusUpper) . '</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 12px 15px; color: #64748b; font-size: 14px;"><strong>Date Updated</strong></td>
                            <td style="padding: 12px 15px; color: #0f172a; font-size: 14px;">' . date('F j, Y g:i A') . '</td>
                        </tr>
                    </table>

                    <p style="margin: 20px 0 0; color: #475569; font-size: 14px; line-height: 1.6;"><strong>Next step:</strong> ' . $cfg['nextStep'] . '</p>
                </div>

                <div style="background: #f8fafc; padding: 20px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
                    <p style="margin: 0; color: #94a3b8; font-size: 12px;">&copy; ' . date('Y') . ' University of Makati · Registrar\'s Office</p>
                    <p style="margin: 6px 0 0; color: #94a3b8; font-size: 12px;">Email: registrar@umak.edu.ph | Contact: (02) 1234-5678</p>
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->AltBody = "UMak Registrar - Document Request Update\n\n";
        $mail->AltBody .= "Hello " . $recipientName . ",\n\n";
        $mail->AltBody .= $cfg['message'] . "\n\n";
        if (!empty($ticketNumber)) $mail->AltBody .= "Ticket #: " . $ticketNumber . "\n";
        $mail->AltBody .= "Document Type: " . $requestType . "\n";
        $mail->AltBody .= "Status: " . $statusUpper . "\n\n";
        $mail->AltBody .= "© " . date('Y') . " University of Makati · Registrar's Office";

        $mail->send();
        return ['success' => true, 'message' => 'Email sent to ' . $recipientEmail];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
    }
}
?>