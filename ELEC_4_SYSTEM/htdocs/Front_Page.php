<?php
// ============================================
// ENABLE ERROR REPORTING FOR DEBUGGING
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// START SESSION - MUST BE FIRST!
// ============================================
session_start();

// TEMPORARY: Clear session to see Front_Page
session_unset();
session_destroy();
session_start();
// ============================================
// INCLUDE DATABASE CONNECTION
// ============================================
// Front_Page.php is inside htdocs folder
// config folder is outside htdocs (one level up)
$configPath = __DIR__ . '/config/db_connect.php';

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Fallback: try relative path from htdocs
    $fallbackPath = '../config/db_connect.php';
    if (file_exists($fallbackPath)) {
        require_once $fallbackPath;
    } else {
        // If still not found, show error and use fallback values
        error_log("Database connection file not found at: " . $configPath);
        // Set default values so page still displays
        $totalStudents = 15000;
        $graduationRate = 98;
        $totalPrograms = 45;
        $yearsOfExcellence = 50;
        $dbAvailable = false;
    }
}

// ============================================
// CHECK IF USER IS ALREADY LOGGED IN
// ============================================
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: User_Dashboard.php');
    exit();
}

// ============================================
// GET STATISTICS FROM DATABASE
// ============================================
$totalStudents = 15000;  // Default fallback
$graduationRate = 98;    // Default fallback
$totalPrograms = 45;     // Default fallback
$yearsOfExcellence = 50; // Default fallback

// Check if the database connection function exists
if (function_exists('getDBConnection')) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            // Get total students
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'active'");
            $result = $stmt->fetch();
            if ($result && $result['total'] > 0) {
                $totalStudents = (int)$result['total'];
            }
            
            // Get total academic programs
            $stmt = $pdo->query("SELECT COUNT(DISTINCT program) as total FROM users WHERE program IS NOT NULL AND program != ''");
            $result = $stmt->fetch();
            if ($result && $result['total'] > 0) {
                $totalPrograms = (int)$result['total'];
            }
            
            // Get graduation rate
            $stmt = $pdo->query("SELECT 
                ROUND(
                    (SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND graduation_year IS NOT NULL) * 100.0 / 
                    NULLIF((SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'), 0)
                ) as rate
            ");
            $result = $stmt->fetch();
            if ($result && $result['rate'] > 0) {
                $graduationRate = (int)$result['rate'];
            }
            
            // Get years of operation
            $stmt = $pdo->query("SELECT YEAR(MIN(created_at)) as start_year FROM users");
            $result = $stmt->fetch();
            if ($result && $result['start_year']) {
                $yearsOfExcellence = date('Y') - (int)$result['start_year'];
                if ($yearsOfExcellence < 1) $yearsOfExcellence = 50;
            }
            
        } catch (PDOException $e) {
            // Use default values if query fails
            error_log("Database error: " . $e->getMessage());
        }
    }
} else {
    // Log if connection function is missing
    error_log("getDBConnection function not found in db_connect.php");
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>University of Makati · Registrar's Office</title>
    
    <!-- Google Identity Services SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    
    <!-- Google Fonts: Fira Sans Condensed -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    
    <style>
        /* ========== VISUAL DESIGN ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body { 
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: 'Fira Sans Condensed', sans-serif;
            background: #faf9f6;
            color: #1a2a3a;
        }

        :root {
            --primary: #003366;
            --primary-dark: #002244;
            --primary-light: #1a4d7a;
            --secondary: #FFD700;
            --secondary-light: #FFE44D;
            --secondary-dark: #E6C200;
            --gray-50: #f8f7f4;
            --gray-100: #f0eee9;
            --gray-200: #e0ddd6;
            --gray-600: #4a4a4a;
            --gray-800: #2a2a2a;
        }

        /* Fira Sans Condensed is the only font */
        * {
            font-family: 'Fira Sans Condensed', sans-serif;
        }

        h1, h2, h3, h4, h5, h6, .section-title, .brand-name, .nav-link-custom, .btn, .stat-value {
            font-family: 'Fira Sans Condensed', sans-serif;
            letter-spacing: 0.01em;
        }

        .nav-link-custom { 
            color: rgba(255,255,255,0.9); 
            font-weight: 400; 
            font-size: 0.95rem;
            transition: all 0.2s; 
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            border-radius: 0px;
            white-space: nowrap;
            text-decoration: none;
            display: inline-block;
        }
        .nav-link-custom:hover { 
            color: var(--secondary); 
            background: rgba(255,215,0,0.15);
        }

        .login-btn {
            border: 2px solid var(--secondary);
            border-radius: 0px;
            padding: 0.5rem 1.8rem;
            transition: all 0.2s ease;
            background: transparent;
            color: white !important;
        }
        .login-btn:hover {
            background: var(--secondary);
            color: var(--primary) !important;
            border-color: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-outline-light {
            border: 2px solid rgba(255,255,255,0.4);
            color: white;
            font-weight: 400;
            padding: 0.6rem 1.8rem;
            border-radius: 0px;
            transition: all 0.2s;
            font-size: 0.95rem;
            background: transparent;
        }
        .btn-outline-light:hover {
            background: var(--secondary);
            color: var(--primary);
            border-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -8px rgba(0,0,0,0.25);
        }

        .btn-primary-gold {
            background: var(--secondary);
            color: var(--primary);
            font-weight: 400;
            padding: 0.6rem 2rem;
            border-radius: 0px;
            transition: all 0.2s;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(255,215,0,0.35);
            border: none;
        }
        .btn-primary-gold:hover {
            background: var(--secondary-light);
            transform: translateY(-2px);
            box-shadow: 0 20px 30px -8px rgba(255,215,0,0.4);
        }

        .btn-primary-blue {
            background: var(--primary);
            color: white;
            font-weight: 400;
            padding: 0.6rem 2rem;
            border-radius: 0px;
            transition: all 0.2s;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(0,51,102,0.3);
            border: none;
        }
        .btn-primary-blue:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 20px 30px -8px rgba(0,51,102,0.35);
        }

        .hero-overlay {
            background: transparent;
        }

        /* ========== FIXED HERO SECTION - NO WHITE GAP ========== */
        .hero-section {
            position: relative;
            width: 100%;
            height: 100vh;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            background-color: var(--primary);
        }

        .hero-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        .slide-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            filter: brightness(0.55);
        }
        
        .slide-image.active {
            opacity: 1;
            z-index: 1;
        }

        .hero-content-wrapper {
            position: relative;
            z-index: 30;
            width: 100%;
            min-height: 100%;
            display: flex;
            align-items: center;
            padding: 5rem 2rem 3rem;
        }

        @media (min-width: 768px) {
            .hero-content-wrapper {
                padding: 6rem 2.5rem 4rem;
            }
        }

        @media (min-width: 1024px) {
            .hero-content-wrapper {
                padding: 7rem 3rem 5rem;
            }
        }

        .hero-text-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }
        
        .stat-item {
            text-align: center;
            padding: 1.5rem 1rem;
            transition: all 0.3s ease;
            background: white;
            border-radius: 0px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 2px solid rgba(255,215,0,0.2);
        }
        
        .stat-item:hover {
            transform: translateY(-6px);
            border-color: var(--secondary);
            box-shadow: 0 12px 35px rgba(255,215,0,0.2);
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            font-family: 'Fira Sans Condensed', sans-serif;
            color: var(--primary);
            line-height: 1.2;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            .stat-value {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .social-icon { 
            transition: all 0.2s;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 0px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .social-icon:hover { 
            transform: translateY(-3px);
            background: var(--secondary);
        }
        .social-icon:hover svg {
            color: var(--primary);
        }

        .card {
            background: white;
            border-radius: 0px;
            padding: 1.8rem;
            border: 2px solid rgba(255,215,0,0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-8px);
            border-color: var(--secondary);
            box-shadow: 0 30px 45px -15px rgba(255,215,0,0.2);
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--secondary), var(--secondary-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .card:hover::before {
            transform: scaleX(1);
        }

        .contact-card {
            background: white;
            border-radius: 0px;
            padding: 2rem;
            border: 2px solid rgba(255,215,0,0.15);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .contact-card:hover {
            border-color: var(--secondary);
            transform: translateY(-4px);
            box-shadow: 0 25px 35px -12px rgba(255,215,0,0.2);
        }

        .contact-icon {
            width: 3.2rem;
            height: 3.2rem;
            background: var(--primary);
            border-radius: 0px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            flex-shrink: 0;
            box-shadow: 0 10px 20px -5px rgba(0,51,102,0.25);
        }

        .input-corporate { 
            border: 2px solid #e0ddd6; 
            padding: 0.8rem 1.2rem; 
            border-radius: 0px; 
            width: 100%; 
            transition: all 0.2s;
            background: white;
            font-size: 0.95rem;
            font-family: 'Fira Sans Condensed', sans-serif;
        }
        .input-corporate:focus { 
            border-color: var(--secondary); 
            outline: none; 
            box-shadow: 0 0 0 5px rgba(255,215,0,0.2);
            transform: scale(1.02);
        }
        .input-corporate:read-only {
            background-color: #f3f1ec;
            cursor: not-allowed;
        }
        
        /* Validation styles */
        .input-corporate.input-error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        
        .input-corporate.input-success {
            border-color: #10b981;
        }
        
        .field-error {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }

        .section-tag {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--primary);
            display: inline-block;
            margin-bottom: 0.5rem;
            background: rgba(255,215,0,0.15);
            padding: 0.3rem 1.2rem;
            border-radius: 0px;
            border: 2px solid rgba(255,215,0,0.3);
        }

        .section-title {
            font-family: 'Fira Sans Condensed', sans-serif;
            font-size: 2.6rem;
            font-weight: 700;
            color: var(--primary-dark);
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .modal {
            transition: opacity 0.2s ease;
        }

        .value-prop-card {
            background: white;
            border-radius: 0px;
            padding: 2rem;
            border: 2px solid rgba(255,215,0,0.15);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .value-prop-card:hover {
            transform: translateY(-8px);
            border-color: var(--secondary);
            box-shadow: 0 30px 45px -15px rgba(255,215,0,0.2);
        }
        .value-prop-card:hover .icon-wrapper {
            background: var(--secondary);
            color: var(--primary);
        }

        .icon-wrapper {
            width: 3.8rem;
            height: 3.8rem;
            background: rgba(255,215,0,0.15);
            border-radius: 0px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .section-divider {
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--secondary), transparent);
            width: 100%;
            max-width: 250px;
            margin: 2rem auto;
            border-radius: 0px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }

        .scroll-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.7s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }

        .scroll-reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .reveal-delay-1 { transition-delay: 0.05s; }
        .reveal-delay-2 { transition-delay: 0.1s; }
        .reveal-delay-3 { transition-delay: 0.15s; }
        .reveal-delay-4 { transition-delay: 0.2s; }
        .reveal-delay-5 { transition-delay: 0.25s; }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1efe8;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--secondary);
            border-radius: 0px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-dark);
        }
        
        .core-value-card {
            background: white;
            border-radius: 0px;
            padding: 1.8rem;
            border: 2px solid rgba(255,215,0,0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .core-value-card:hover {
            transform: translateY(-8px);
            border-color: var(--secondary);
            box-shadow: 0 30px 45px -15px rgba(255,215,0,0.2);
        }
        .core-value-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--secondary), var(--secondary-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .core-value-card:hover::before {
            transform: scaleX(1);
        }
        .core-value-card h4 {
            font-family: 'Fira Sans Condensed', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 0.75rem;
            color: var(--primary);
        }
        .core-value-card p {
            color: #4a4a4a;
            line-height: 1.6;
        }

        /* Google Sign-In Button Styles */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 2px solid #e0ddd6;
        }

        .divider span {
            padding: 0 0.75rem;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        #googleSignInButton {
            display: flex;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
        
        /* Gold accent */
        .gold-accent {
            color: var(--secondary);
        }
        
        .bg-gold {
            background: var(--secondary);
        }
        
        .border-gold {
            border-color: var(--secondary);
        }
        
        .text-primary-dark {
            color: var(--primary-dark);
        }
        
        .bg-primary-dark {
            background: var(--primary-dark);
        }
        
        /* UMak badge */
        .umak-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,215,0,0.12);
            border: 2px solid rgba(255,215,0,0.3);
            padding: 0.3rem 1.2rem;
            border-radius: 0px;
            color: white;
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        
        .umak-badge .dot {
            width: 8px;
            height: 8px;
            background: var(--secondary);
            border-radius: 50%;
            display: inline-block;
            animation: pulse-dot 2s infinite;
        }
        
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        /* ========== ALIGNMENT FIXES ========== */
        .hero-text-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
        }

        .hero-text-content .umak-badge {
            margin-bottom: 0.75rem;
        }

        .hero-text-content h1 {
            margin-bottom: 0.75rem;
        }

        .hero-text-content p {
            margin-bottom: 1.25rem;
        }

        .hero-text-content .flex-wrap {
            margin-bottom: 0.75rem;
        }

        .hero-text-content .border-l-4 {
            margin-bottom: 0.75rem;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        /* Ensure consistent spacing */
        .section-padding {
            padding-top: 4rem;
            padding-bottom: 4rem;
        }

        @media (min-width: 768px) {
            .section-padding {
                padding-top: 5rem;
                padding-bottom: 5rem;
            }
        }

        /* ===== MODAL BORDER RADIUS REMOVED ===== */
        .bg-white.rounded-2xl {
            border-radius: 0px !important;
        }

        .rounded-2xl {
            border-radius: 0px !important;
        }

        .rounded-lg {
            border-radius: 0px !important;
        }

        .rounded-full {
            border-radius: 0px !important;
        }

        .rounded-xl {
            border-radius: 0px !important;
        }

        .rounded-3xl {
            border-radius: 0px !important;
        }

        /* Contact icon box */
        .contact-icon {
            border-radius: 0px !important;
        }

        /* Input fields */
        .input-corporate {
            border-radius: 0px !important;
        }

        /* Any tailwind rounded classes */
        [class*="rounded-"] {
            border-radius: 0px !important;
        }

        /* Buttons in modals */
        .bg-\[\#003366\].hover\:bg-\[\#1a4d7a\] {
            border-radius: 0px !important;
        }

        /* Google sign-in button container - override */
        #googleSignInButton iframe {
            border-radius: 0px !important;
        }

        /* ===== MODERATE LEFT AND RIGHT SPACING ===== */
        .max-w-7xl {
            max-width: 100% !important;
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .px-6 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .lg\:px-8 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .px-4 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .px-5 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .px-8 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /* Remove side padding from sections */
        section .max-w-7xl,
        footer .max-w-7xl,
        header .max-w-7xl {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /* Hero content - moderate side padding */
        .hero-content-wrapper .max-w-7xl {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /* Contact form container */
        .max-w-2xl.mx-auto {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /* Stats grid - moderate padding */
        #statsGrid {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /* ===== MODALS - RESTORE ORIGINAL SLIM WIDTH ===== */
        .modal .max-w-md {
            max-width: 28rem !important;
        }

        .modal .bg-white {
            padding: 2rem !important;
            max-width: 28rem !important;
        }

        /* Login modal */
        #loginModal .bg-white {
            max-width: 28rem !important;
        }

        /* OTP modal */
        #otpModal .bg-white {
            max-width: 28rem !important;
        }

        /* Register modal */
        #registerModal .bg-white {
            max-width: 28rem !important;
        }

        /* Footer */
        footer .max-w-7xl {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /* Container for all content */
        .w-full.bg-\[\#faf9f6\] {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        /* Cards and content inside should still have internal padding */
        .card, .contact-card, .value-prop-card, .core-value-card {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        /* Mobile menu */
        #mobile-menu {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        /* Stats items */
        .stat-item {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        /* Modal close button - keep it positioned correctly */
        .modal .absolute {
            position: absolute;
        }
    </style>
</head>
 
<body class="antialiased">

<div class="w-full bg-[#faf9f6]">

    <!-- ========== HEADER ========== -->
    <header class="fixed top-0 left-0 w-full z-50 bg-[#003366]">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex flex-col leading-tight">
                    <div class="brand-name" style="font-family: 'Fira Sans Condensed', sans-serif; font-weight: 700; font-size: 1.6rem; line-height: 1.2; color: white; letter-spacing: 0.02em;">UMak<span style="color: #FFD700;">·Registrar</span></div>
                    <span class="text-white/70 text-xs font-medium tracking-wide">University of Makati · Official Records</span>
                </div>
                
                <div class="flex items-center space-x-8">
                    <nav class="flex items-center space-x-6">
                        <a href="#about" class="nav-link-custom">About</a>
                        <a href="#services" class="nav-link-custom">Services</a>
                        <a href="#contact" class="nav-link-custom">Contact</a>
                        <a href="#" id="navLoginBtn" class="nav-link-custom login-btn">Log in</a>
                    </nav>
                    <button id="mobile-menu-btn" class="md:hidden text-white p-2">
                        <svg width="24" height="24" fill="none" stroke="currentColor"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </button>
                </div>
            </div>
        </div>
        
        <div id="mobile-menu" class="hidden bg-[#003366] border-t-2 border-[#FFD700]/30 px-6 py-4">
            <a href="#about" class="block text-white/80 py-2 hover:text-[#FFD700]">About</a>
            <a href="#services" class="block text-white/80 py-2 hover:text-[#FFD700]">Services</a>
            <a href="#contact" class="block text-white/80 py-2 hover:text-[#FFD700]">Contact</a>
            <a href="#" id="mobileNavLoginBtn" class="block text-white/80 py-2 hover:text-[#FFD700] mt-2 px-4 py-2 border-2 border-[#FFD700] rounded-full text-center hover:bg-[#FFD700] hover:text-[#003366] transition" style="border-radius:0px !important;">Log in</a>
        </div>
    </header>

    <!-- ========== HERO SLIDESHOW - FULL SCREEN NO WHITE GAP ========== -->
    <section class="hero-section">
        <div class="hero-slideshow">
            <!-- Image paths are relative to htdocs folder -->
            <img src="../images/umak_1.png" alt="University of Makati Aerial View" class="slide-image active" data-slide="0">
            <img src="../images/umak_2.png" alt="UMak Main Facade" class="slide-image" data-slide="1">
            <img src="../images/umak_3.png" alt="UMak Official Logo" class="slide-image" data-slide="2">
            <img src="../images/umak_-4.png" alt="UMak Track and Field Stadium" class="slide-image" data-slide="3">
        </div>
        
        <div class="absolute inset-0 z-10 hero-overlay"></div>
        
        <div class="hero-content-wrapper">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full">
                <div class="grid lg:grid-cols-2 gap-12 items-start">
                    <div class="hero-text-content">
                        <span class="umak-badge">
                            <span class="dot"></span>
                            UNIVERSITY OF MAKATI · EST. 1972
                        </span>
                        
                        <h1 class="font-['Fira_Sans_Condensed'] text-5xl lg:text-6xl font-bold text-white leading-tight mb-3">
                            Registrar's <span style="color: #FFD700;">Office</span>
                        </h1>
                        
                        <p class="text-xl text-white/90 max-w-xl mb-4 leading-relaxed">
                            Official academic records and enrollment services for the University of Makati community. Excellence in student records management.
                        </p>
                        
                        <div class="flex flex-wrap gap-3 mb-3">
                            <a href="#" id="join-now-btn" class="btn-primary-gold">Get Started</a>
                            <a href="#services" class="btn-outline-light">Our Services</a>
                        </div>

                        <p class="text-white/80 text-lg italic border-l-4 border-[#FFD700] pl-4 mb-3">
                            "Serving the UMak community with integrity and excellence"
                        </p>

                        <div class="flex items-center space-x-4 pt-2">
                            <span class="text-sm text-white/60">Follow us:</span>
                            
                            <a href="#" class="social-icon hover:bg-[#1877f2]">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                            
                            <a href="#" class="social-icon hover:bg-black">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.104c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 0021.688-4.096 13.87 13.87 0 001.302-6.655c0-.198-.01-.396-.03-.594a10.12 10.12 0 002.48-2.566z" />
                                </svg>
                            </a>
                            
                            <a href="#" class="social-icon hover:bg-gradient-to-br hover:from-[#833AB4] hover:via-[#FD1D1D] hover:to-[#FCAF45]">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="hidden lg:block"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== QUICK STATS ========== -->
    <section class="py-16 bg-[#faf9f6]">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="stats-grid" id="statsGrid">
                <div class="stat-item scroll-reveal reveal-delay-1">
                    <div class="stat-value">
                        <span class="counter" data-target="<?php echo $totalStudents; ?>" data-suffix="K+">0</span>
                    </div>
                    <div class="text-sm text-gray-500 mt-2">Students Enrolled</div>
                </div>
                
                <div class="stat-item scroll-reveal reveal-delay-2">
                    <div class="stat-value">
                        <span class="counter" data-target="<?php echo $graduationRate; ?>" data-suffix="%">0</span>
                    </div>
                    <div class="text-sm text-gray-500 mt-2">Graduation Rate</div>
                </div>
                
                <div class="stat-item scroll-reveal reveal-delay-3">
                    <div class="stat-value">
                        <span class="counter" data-target="<?php echo $totalPrograms; ?>" data-suffix="+">0</span>
                    </div>
                    <div class="text-sm text-gray-500 mt-2">Academic Programs</div>
                </div>
                
                <div class="stat-item scroll-reveal reveal-delay-4">
                    <div class="stat-value">
                        <span class="counter" data-target="<?php echo $yearsOfExcellence; ?>" data-suffix="+">0</span>
                    </div>
                    <div class="text-sm text-gray-500 mt-2">Years of Excellence</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== ABOUT SECTION ========== -->
    <section id="about" class="section-padding bg-[#faf9f6]">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="section-tag scroll-reveal reveal-delay-1"> ABOUT UMak REGISTRAR </span>
                <h2 class="section-title scroll-reveal reveal-delay-2">Excellence in Academic Records</h2>
                <p class="text-gray-600 text-lg scroll-reveal reveal-delay-3">
                    The University of Makati Registrar's Office is committed to providing accurate, timely, and efficient academic records management for all students.
                </p>
                <div class="section-divider"></div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8 mb-12">
                <div class="card scroll-reveal reveal-delay-1 text-left">
                    <h3 class="text-2xl font-semibold text-[#003366] mb-3">Our Mission</h3>
                    <p class="text-gray-600 leading-relaxed text-lg">To provide accurate, timely, and accessible academic records management while ensuring the highest standards of integrity and service excellence for the UMak community.</p>
                </div>
                <div class="card scroll-reveal reveal-delay-2 text-left">
                    <h3 class="text-2xl font-semibold text-[#003366] mb-3">Our Vision</h3>
                    <p class="text-gray-600 leading-relaxed text-lg">To be the model of excellence in academic records management, recognized for innovation, reliability, and student-centered service in the Philippines.</p>
                </div>
            </div>

            <div class="text-center mb-10">
                <span class="inline-block bg-[#FFD700]/15 text-[#003366] text-sm font-semibold tracking-wider uppercase px-5 py-2 rounded-full border-2 border-[#FFD700]/30 scroll-reveal reveal-delay-1" style="border-radius:0px !important;">Core Values</span>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="core-value-card scroll-reveal reveal-delay-1 text-left">
                    <h4 class="text-xl font-bold text-[#003366] mb-2">Integrity</h4>
                    <p class="text-gray-600">Upholding the highest ethical standards in records management and student services.</p>
                </div>
                <div class="core-value-card scroll-reveal reveal-delay-2 text-left">
                    <h4 class="text-xl font-bold text-[#003366] mb-2">Excellence</h4>
                    <p class="text-gray-600">Striving for excellence in every transaction, from enrollment to graduation.</p>
                </div>
                <div class="core-value-card scroll-reveal reveal-delay-3 text-left">
                    <h4 class="text-xl font-bold text-[#003366] mb-2">Innovation</h4>
                    <p class="text-gray-600">Continuously improving processes to serve the UMak community better.</p>
                </div>
                <div class="core-value-card scroll-reveal reveal-delay-4 text-left">
                    <h4 class="text-xl font-bold text-[#003366] mb-2">Service</h4>
                    <p class="text-gray-600">Committed to compassionate and professional assistance for every student.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== SERVICES SECTION ========== -->
    <section id="services" class="section-padding bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="section-tag scroll-reveal reveal-delay-1"> OUR SERVICES </span>
                <h2 class="section-title scroll-reveal reveal-delay-2">What We Offer</h2>
                <p class="text-gray-600 text-lg scroll-reveal reveal-delay-3">
                    Comprehensive academic services designed to support your educational journey at UMak.
                </p>
                <div class="section-divider"></div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="value-prop-card scroll-reveal reveal-delay-1 text-left">
                    <div class="icon-wrapper">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-[#003366] mb-3">Enrollment Services</h3>
                    <p class="text-gray-500">New student registration, course changes, and program validation for all UMak students.</p>
                </div>
                <div class="value-prop-card scroll-reveal reveal-delay-2 text-left">
                    <div class="icon-wrapper">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-[#003366] mb-3">Transcripts &amp; Records</h3>
                    <p class="text-gray-500">Official transcripts, diploma requests, and academic verification letters for alumni and current students.</p>
                </div>
                <div class="value-prop-card scroll-reveal reveal-delay-3 text-left">
                    <div class="icon-wrapper">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-[#003366] mb-3">Graduation Services</h3>
                    <p class="text-gray-500">Graduation applications, clearance processing, and diploma issuance for UMak graduates.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== CONTACT ========== -->
    <section id="contact" class="section-padding bg-[#faf9f6]">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="section-tag scroll-reveal reveal-delay-1"> REACH US </span>
                <h2 class="section-title scroll-reveal reveal-delay-2">Get in Touch</h2>
                <p class="text-gray-600 text-lg scroll-reveal reveal-delay-3">
                    Have questions about enrollment, records, or our services at UMak?
                </p>
                <div class="section-divider"></div>
            </div>
            
            <div class="grid lg:grid-cols-3 gap-8 mb-16">
                <div class="contact-card scroll-reveal reveal-delay-1 text-left">
                    <div class="flex items-start gap-4">
                        <div class="contact-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-[#003366] text-lg mb-2">UMak Campus</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                J.P. Rizal Extension, West Rembo<br>
                                Makati City, Metro Manila
                            </p>
                        </div>
                    </div>
                </div>

                <div class="contact-card scroll-reveal reveal-delay-2 text-left">
                    <div class="flex items-start gap-4">
                        <div class="contact-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-[#003366] text-lg mb-2">Contact</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                (02) 8123-4567<br>
                                +63 917 123 4567
                            </p>
                        </div>
                    </div>
                </div>

                <div class="contact-card scroll-reveal reveal-delay-3 text-left">
                    <div class="flex items-start gap-4">
                        <div class="contact-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-[#003366] text-lg mb-2">Email</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                registrar@umak.edu.ph<br>
                                admissions@umak.edu.ph
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-2xl shadow-xl border-2 border-[#FFD700]/20 p-8 scroll-reveal reveal-delay-4 text-left" style="border-radius:0px !important;">
                    <h3 class="text-2xl font-bold text-[#003366] mb-6 text-center">Send Us a Message</h3>
                    <form id="contactForm" class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <input type="text" name="first_name" placeholder="First name" class="input-corporate">
                            <input type="text" name="last_name" placeholder="Last name" class="input-corporate">
                        </div>
                        <input type="email" name="email" placeholder="Email address" class="input-corporate">
                        <input type="text" name="subject" placeholder="Subject" class="input-corporate">
                        <textarea rows="4" name="message" placeholder="Your message" class="input-corporate"></textarea>
                        <button type="submit" class="w-full bg-[#003366] hover:bg-[#1a4d7a] text-white font-semibold py-3 px-6 rounded-lg transition transform hover:scale-[1.02]" style="border-radius:0px !important;">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== FOOTER ========== -->
    <footer class="bg-[#003366] py-12 border-t-4 border-[#FFD700]">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="col-span-1 md:col-span-2">
                    <span class="text-white font-bold text-2xl tracking-tight">UMak<span style="color: #FFD700;">·Registrar</span></span>
                    <p class="text-white/40 text-sm mt-2 max-w-md">Official Records Management · University of Makati</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#about" class="text-white/40 hover:text-[#FFD700] transition">About</a></li>
                        <li><a href="#services" class="text-white/40 hover:text-[#FFD700] transition">Services</a></li>
                        <li><a href="#contact" class="text-white/40 hover:text-[#FFD700] transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Follow Us</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-white/40 hover:text-[#FFD700] transition">Facebook</a></li>
                        <li><a href="#" class="text-white/40 hover:text-[#FFD700] transition">Twitter</a></li>
                        <li><a href="#" class="text-white/40 hover:text-[#FFD700] transition">Instagram</a></li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-col md:flex-row justify-between items-center text-sm pt-8 border-t border-white/10">
                <span class="text-white/40">© 2026 University of Makati · Registrar's Office · All rights reserved.</span>
            </div>
        </div>
    </footer>
</div>

<!-- ========== LOGIN MODAL WITH GOOGLE SIGN-IN ========== -->
<div id="loginModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4 hidden modal">
    <div class="bg-white max-w-md w-full p-8 relative transform transition-all scale-95 hover:scale-100 border-2 border-[#FFD700]/30" style="border-radius:0px !important;">
        <button id="closeLoginModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        <h3 class="text-2xl font-bold text-[#003366] mb-6 text-center">Log in to UMak Portal</h3>
        
        <!-- Google Sign-In Button -->
        <div id="googleSignInButton"></div>
        
        <div class="divider">
            <span>or continue with email</span>
        </div>
        
        <form id="loginForm">
            <div class="space-y-4">
                <input type="email" name="email" placeholder="Email address" class="input-corporate" required>
                <input type="password" name="password" placeholder="Password" class="input-corporate" required>
                <div class="text-right mb-2">
                    <a href="forgot_password.php" id="forgotPasswordLink" class="text-xs text-[#003366] hover:underline">Forgot Password?</a>
                </div>
                <div id="loginError" class="text-red-500 text-sm hidden"></div>
                <button type="submit" id="loginSubmitBtn" class="w-full bg-[#003366] hover:bg-[#1a4d7a] text-white font-semibold py-3 transition" style="border-radius:0px !important;">
                    Log in
                </button>
                <p class="text-center text-sm text-gray-500">
                    Don't have an account? 
                    <a href="#" id="switchToRegister" class="text-[#003366] font-semibold hover:underline">Get Started</a>
                </p>
            </div>
        </form>
    </div>
</div>

<!-- ========== OTP VERIFICATION MODAL ========== -->
<div id="otpModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4 hidden modal">
    <div class="bg-white max-w-md w-full p-8 relative transform transition-all scale-95 hover:scale-100 border-2 border-[#FFD700]/30" style="border-radius:0px !important;">
        <button id="closeOtpModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        <h3 class="text-2xl font-bold text-[#003366] mb-4 text-center">Verification Code</h3>
        <p id="otpInfoText" class="text-sm text-gray-600 mb-4">We sent a 6-digit code to your email. Enter it below to continue.</p>
        <div id="otpError" class="text-red-500 text-sm hidden mb-3"></div>
        <div id="otpSuccess" class="text-green-600 text-sm hidden mb-3"></div>
        <form id="otpForm">
            <input type="text" name="otp_code" id="otp_code" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit code" class="input-corporate w-full text-center tracking-[0.35em] text-lg" required>
            <button type="submit" id="otpSubmitBtn" class="w-full bg-[#003366] hover:bg-[#1a4d7a] text-white font-semibold py-3 transition mt-4" style="border-radius:0px !important;">
                Verify Code
            </button>
        </form>
        <p id="otpDebugHint" class="text-xs text-gray-500 mt-2 hidden"></p>
        <div class="mt-4 text-center text-sm text-gray-600">
            Didn't receive the code? <button id="resendOtpBtn" class="text-[#003366] font-semibold hover:underline">Resend</button>
        </div>
        <p class="text-center text-sm text-gray-500 mt-3">
            <a href="#" id="otpBackToLogin" class="text-[#003366] hover:underline">Back to login</a>
        </p>
    </div>
</div>

<!-- ========== REGISTER MODAL (WITHOUT CAPTCHA) ========== -->
<div id="registerModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden modal overflow-y-auto" style="padding: 1rem;">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white max-w-md w-full p-6 relative my-8 transform transition-all scale-95 hover:scale-100 border-2 border-[#FFD700]/30" style="border-radius:0px !important;">
            <button id="closeRegisterModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl z-10">&times;</button>
            
            <div class="mb-6 text-center">
                <h3 class="text-2xl font-bold text-[#003366]">Create Account</h3>
                <p class="text-sm text-gray-500 mt-1">Sign up to get started at UMak</p>
            </div>
            
            <form id="registerForm" class="space-y-4" novalidate>
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="text-sm font-medium text-gray-700 block mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="full_name" placeholder="Juan Dela Cruz" class="input-corporate w-full" required>
                    <div class="field-error text-red-500 text-xs hidden" id="full_name_error"></div>
                </div>

                <!-- Date of Birth -->
                <div>
                    <label for="birthdate" class="text-sm font-medium text-gray-700 block mb-1">Date of Birth <span class="text-red-500">*</span></label>
                    <input type="date" name="birthdate" id="birthdate" class="input-corporate w-full" required>
                    <div class="field-error text-red-500 text-xs hidden" id="birthdate_error"></div>
                </div>

                <!-- Email Address -->
                <div>
                    <label for="register_email" class="text-sm font-medium text-gray-700 block mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="register_email" placeholder="name@umak.edu.ph" class="input-corporate w-full" required>
                    <p class="text-xs text-gray-400 mt-1">Use your @umak.edu.ph email if available</p>
                    <div class="field-error text-red-500 text-xs hidden" id="email_error"></div>
                </div>

                <!-- Mobile Number -->
                <div>
                    <label for="mobile" class="text-sm font-medium text-gray-700 block mb-1">Mobile Number <span class="text-red-500">*</span></label>
                    <input type="tel" name="mobile" id="mobile" placeholder="09XXXXXXXXX" class="input-corporate w-full" required>
                    <p class="text-xs text-gray-400 mt-1">Format: 09XXXXXXXXX (e.g., 09171234567)</p>
                    <div class="field-error text-red-500 text-xs hidden" id="mobile_error"></div>
                </div>

                <!-- Student/Employee ID -->
                <div>
                    <label for="user_id_number" class="text-sm font-medium text-gray-700 block mb-1">Student / Employee ID <span class="text-red-500">*</span></label>
                    <input type="text" name="user_id_number" id="user_id_number" placeholder="e.g., UM-2024-0001" class="input-corporate w-full" required>
                    <p class="text-xs text-gray-400 mt-1">Enter your official UMak ID number</p>
                    <div class="field-error text-red-500 text-xs hidden" id="user_id_number_error"></div>
                </div>

                <!-- Role / User Type -->
                <div>
                    <label for="user_role" class="text-sm font-medium text-gray-700 block mb-1">User Type <span class="text-red-500">*</span></label>
                    <select name="role" id="user_role" class="input-corporate w-full" required>
                        <option value="">Select user type...</option>
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="staff">Staff</option>
                    </select>
                    <div class="field-error text-red-500 text-xs hidden" id="user_role_error"></div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="text-sm font-medium text-gray-700 block mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" class="input-corporate w-full" required>
                    <div class="field-error text-red-500 text-xs hidden" id="password_error"></div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="text-sm font-medium text-gray-700 block mb-1">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter your password" class="input-corporate w-full" required>
                    <div class="field-error text-red-500 text-xs hidden" id="confirm_password_error"></div>
                </div>

                <!-- Password Requirements -->
                <div class="bg-gray-50 p-3 border border-gray-200">
                    <p class="text-xs text-gray-600 font-medium mb-1">Password must contain:</p>
                    <ul class="text-xs text-gray-500 space-y-0.5 list-disc list-inside">
                        <li>8+ characters</li>
                        <li>At least 1 uppercase letter</li>
                        <li>At least 1 lowercase letter</li>
                        <li>At least 1 number</li>
                        <li>At least 1 special character (!@#$%^&*)</li>
                    </ul>
                </div>

                <!-- Terms & Conditions -->
                <div class="flex items-start gap-2">
                    <input type="checkbox" id="terms" name="terms" class="mt-1" required>
                    <label for="terms" class="text-sm text-gray-600">I agree to the <a href="#" id="termsLink" class="text-[#003366] font-semibold hover:underline">Terms and Conditions</a> <span class="text-red-500">*</span></label>
                </div>

                <!-- Error / Success Messages -->
                <div id="registerError" class="text-red-500 text-sm hidden"></div>
                <div id="registerSuccess" class="text-green-500 text-sm hidden"></div>

                <!-- Submit Button -->
                <button type="submit" id="registerSubmitBtn" class="w-full bg-[#003366] hover:bg-[#1a4d7a] text-white font-semibold py-3 transition transform hover:scale-[1.02]" style="border-radius:0px !important;">
                    Sign Up
                </button>

                <!-- Login Link -->
                <p class="text-center text-sm text-gray-500">
                    Already have an account? 
                    <a href="#" id="switchToLogin" class="text-[#003366] font-semibold hover:underline">Log in</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        // Mobile menu toggle
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (menuBtn) {
            menuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
        }

        // Modal elements
        const loginModal = document.getElementById('loginModal');
        const otpModal = document.getElementById('otpModal');
        const registerModal = document.getElementById('registerModal');
        const closeLogin = document.getElementById('closeLoginModal');
        const closeOtp = document.getElementById('closeOtpModal');
        const closeRegister = document.getElementById('closeRegisterModal');
        const switchToRegister = document.getElementById('switchToRegister');
        const switchToLogin = document.getElementById('switchToLogin');
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');

        // Open login modal function
        window.openLoginModal = function() {
            if (loginModal) loginModal.classList.remove('hidden');
            if (otpModal) otpModal.classList.add('hidden');
            if (registerModal) registerModal.classList.add('hidden');
            const loginError = document.getElementById('loginError');
            if (loginError) loginError.classList.add('hidden');
            const loginBtn = document.getElementById('loginSubmitBtn');
            if (loginBtn) {
                loginBtn.disabled = false;
                loginBtn.innerHTML = 'Log in';
            }
        }

        // Open OTP modal function
        function openOtpModal(debugOtp = null, destinationEmail = null) {
            if (!otpModal) return;
            otpModal.classList.remove('hidden');
            if (loginModal) loginModal.classList.add('hidden');
            if (registerModal) registerModal.classList.add('hidden');
            const otpError = document.getElementById('otpError');
            const otpSuccess = document.getElementById('otpSuccess');
            const otpInfoText = document.getElementById('otpInfoText');
            const otpDebugHint = document.getElementById('otpDebugHint');
            const otpSubmitBtn = document.getElementById('otpSubmitBtn');

            if (otpError) otpError.classList.add('hidden');
            if (otpSuccess) otpSuccess.classList.add('hidden');
            if (otpSubmitBtn) {
                otpSubmitBtn.disabled = false;
                otpSubmitBtn.innerHTML = 'Verify Code';
            }
            if (otpInfoText) {
                otpInfoText.textContent = destinationEmail ? `A 6-digit code was sent to ${destinationEmail}. Enter it below to continue.` : 'A 6-digit code was sent to your email. Enter it below to continue.';
            }
            if (otpDebugHint) {
                if (debugOtp) {
                    otpDebugHint.textContent = `Test OTP: ${debugOtp}`;
                    otpDebugHint.classList.remove('hidden');
                } else {
                    otpDebugHint.classList.add('hidden');
                }
            }
            const otpInput = document.getElementById('otp_code');
            if (otpInput) otpInput.value = '';
        }

        function closeOtpModal() {
            if (otpModal) otpModal.classList.add('hidden');
        }

        function openRegisterModal() {
            if (registerModal) registerModal.classList.remove('hidden');
            if (loginModal) loginModal.classList.add('hidden');
            if (otpModal) otpModal.classList.add('hidden');
            const registerError = document.getElementById('registerError');
            const registerSuccess = document.getElementById('registerSuccess');
            if (registerError) registerError.classList.add('hidden');
            if (registerSuccess) registerSuccess.classList.add('hidden');
            // Reset form fields
            const form = document.getElementById('registerForm');
            if (form) form.reset();
        }

        window.closeLoginModal = function() {
            if (loginModal) loginModal.classList.add('hidden');
        }

        window.closeRegisterModal = function() {
            if (registerModal) registerModal.classList.add('hidden');
        }

        window.closeOtpModal = closeOtpModal;

        // Forgot password link handler
        if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', (e) => {
                e.preventDefault();
                closeLoginModal();
                window.location.href = 'forgot_password.php';
            });
        }

        // Login button handlers
        const navLoginBtn = document.getElementById('navLoginBtn');
        const mobileNavLoginBtn = document.getElementById('mobileNavLoginBtn');
        
        if (navLoginBtn) {
            navLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openLoginModal();
            });
        }
        
        if (mobileNavLoginBtn) {
            mobileNavLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openLoginModal();
            });
        }

        // Join Now button handler
        const joinNowBtn = document.getElementById('join-now-btn');
        if (joinNowBtn) {
            joinNowBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openRegisterModal();
            });
        }

        // Switch between login and register
        if (switchToRegister) {
            switchToRegister.addEventListener('click', (e) => {
                e.preventDefault();
                openRegisterModal();
            });
        }

        if (switchToLogin) {
            switchToLogin.addEventListener('click', (e) => {
                e.preventDefault();
                openLoginModal();
            });
        }

        // Close modal buttons
        if (closeLogin) {
            closeLogin.addEventListener('click', closeLoginModal);
        }

        if (closeOtp) {
            closeOtp.addEventListener('click', closeOtpModal);
        }

        if (closeRegister) {
            closeRegister.addEventListener('click', closeRegisterModal);
        }

        // OTP back to login
        const otpBackToLogin = document.getElementById('otpBackToLogin');
        if (otpBackToLogin) {
            otpBackToLogin.addEventListener('click', (e) => {
                e.preventDefault();
                closeOtpModal();
                openLoginModal();
            });
        }

        // Close modals on outside click
        if (loginModal) {
            loginModal.addEventListener('click', (e) => {
                if (e.target === loginModal) closeLoginModal();
            });
        }

        if (otpModal) {
            otpModal.addEventListener('click', (e) => {
                if (e.target === otpModal) closeOtpModal();
            });
        }

        if (registerModal) {
            registerModal.addEventListener('click', (e) => {
                if (e.target === registerModal) closeRegisterModal();
            });
        }

        // ========== VALIDATION FUNCTIONS ==========
        function showFieldError(fieldId, message) {
            const errorDiv = document.getElementById(fieldId + '_error');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            }
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.add('input-error');
                field.classList.remove('input-success');
            }
        }
        
        function hideFieldError(fieldId) {
            const errorDiv = document.getElementById(fieldId + '_error');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.remove('input-error');
                field.classList.add('input-success');
                setTimeout(() => {
                    field.classList.remove('input-success');
                }, 2000);
            }
        }
        
        function validateFullName(value) {
            if (!value || value.trim() === '') {
                showFieldError('full_name', 'Full name is required');
                return false;
            }
            if (!/^[a-zA-Z\s\-']+$/.test(value)) {
                showFieldError('full_name', 'Name can only contain letters, spaces, hyphens, and apostrophes');
                return false;
            }
            if (value.length < 2) {
                showFieldError('full_name', 'Name must be at least 2 characters');
                return false;
            }
            if (value.length > 50) {
                showFieldError('full_name', 'Name cannot exceed 50 characters');
                return false;
            }
            hideFieldError('full_name');
            return true;
        }
        
        function validateBirthdate(value) {
            if (!value) {
                showFieldError('birthdate', 'Birthdate is required');
                return false;
            }
            const birthDate = new Date(value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            if (age < 18) {
                showFieldError('birthdate', 'You must be at least 18 years old');
                return false;
            }
            if (age > 100) {
                showFieldError('birthdate', 'Please enter a valid birthdate');
                return false;
            }
            hideFieldError('birthdate');
            return true;
        }
        
        function validateEmail(email) {
            if (!email || email.trim() === '') {
                showFieldError('register_email', 'Email is required');
                return false;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showFieldError('register_email', 'Invalid email format');
                return false;
            }
            hideFieldError('register_email');
            return true;
        }
        
        function validateMobile(phone) {
            if (!phone || phone.trim() === '') {
                showFieldError('mobile', 'Mobile number is required');
                return false;
            }
            let cleaned = phone.replace(/[\s\-+]/g, '');
            if (!/^(09|\+639|639)\d{9}$/.test(cleaned)) {
                showFieldError('mobile', 'Invalid mobile number. Must be a valid Philippine number (e.g., 09171234567)');
                return false;
            }
            hideFieldError('mobile');
            return true;
        }
        
        function validatePassword(password) {
            if (!password) {
                showFieldError('password', 'Password is required');
                return false;
            }
            if (password.length < 8) {
                showFieldError('password', 'Password must be at least 8 characters');
                return false;
            }
            if (!/[A-Z]/.test(password)) {
                showFieldError('password', 'Password must contain at least one uppercase letter');
                return false;
            }
            if (!/[a-z]/.test(password)) {
                showFieldError('password', 'Password must contain at least one lowercase letter');
                return false;
            }
            if (!/[0-9]/.test(password)) {
                showFieldError('password', 'Password must contain at least one number');
                return false;
            }
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                showFieldError('password', 'Password must contain at least one special character (!@#$%^&*)');
                return false;
            }
            hideFieldError('password');
            return true;
        }
        
        function validateConfirmPassword(password, confirm) {
            if (password !== confirm) {
                showFieldError('confirm_password', 'Passwords do not match');
                return false;
            }
            hideFieldError('confirm_password');
            return true;
        }

        // ========== NEW VALIDATION FUNCTIONS ==========

        // Validate User ID Number
        function validateUserId(value) {
            if (!value || value.trim() === '') {
                showFieldError('user_id_number', 'Student/Employee ID is required');
                return false;
            }
            if (value.length < 5) {
                showFieldError('user_id_number', 'ID must be at least 5 characters');
                return false;
            }
            hideFieldError('user_id_number');
            return true;
        }

        // Validate Role
        function validateRole(value) {
            if (!value || value === '') {
                showFieldError('user_role', 'Please select your user type');
                return false;
            }
            hideFieldError('user_role');
            return true;
        }

        // ========== REAL-TIME VALIDATION ==========
        const fullNameInput = document.getElementById('full_name');
        if (fullNameInput) {
            fullNameInput.addEventListener('blur', function() { validateFullName(this.value); });
        }
        const birthdateInput = document.getElementById('birthdate');
        if (birthdateInput) {
            birthdateInput.addEventListener('blur', function() { validateBirthdate(this.value); });
        }
        const emailInput = document.getElementById('register_email');
        if (emailInput) {
            emailInput.addEventListener('blur', function() { validateEmail(this.value); });
        }
        const mobileInput = document.getElementById('mobile');
        if (mobileInput) {
            mobileInput.addEventListener('blur', function() { validateMobile(this.value); });
        }
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('blur', function() {
                validatePassword(this.value);
                const confirmInput = document.getElementById('confirm_password');
                if (confirmInput && confirmInput.value) {
                    validateConfirmPassword(this.value, confirmInput.value);
                }
            });
        }
        const confirmPasswordInput = document.getElementById('confirm_password');
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('blur', function() {
                const password = document.getElementById('password').value;
                validateConfirmPassword(password, this.value);
            });
        }

        // User ID validation
        const userIdInput = document.getElementById('user_id_number');
        if (userIdInput) {
            userIdInput.addEventListener('blur', function() { validateUserId(this.value); });
        }

        // Role validation
        const roleSelect = document.getElementById('user_role');
        if (roleSelect) {
            roleSelect.addEventListener('change', function() { validateRole(this.value); });
        }

        // Terms and Conditions link
        const termsLink = document.getElementById('termsLink');
        if (termsLink) {
            termsLink.addEventListener('click', function(e) {
                e.preventDefault();
                alert('Terms and Conditions:\n\n1. You must be a legitimate UMak student, faculty, or staff member.\n2. You agree to provide accurate and truthful information.\n3. You are responsible for maintaining the confidentiality of your account.\n4. You agree to use the system only for legitimate academic purposes.\n5. Any misuse of the system may result in disciplinary action.\n6. The Registrar\'s Office reserves the right to verify all information provided.');
            });
        }

        // ========== COUNTDOWN TIMER ==========
        let countdownInterval = null;
        function startCountdown(seconds, button, errorDiv) {
            let remaining = seconds;
            button.disabled = true;
            errorDiv.classList.remove('hidden');
            if (countdownInterval) clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    button.disabled = false;
                    button.innerHTML = 'Log in';
                    errorDiv.textContent = 'You can now try again.';
                    setTimeout(() => { errorDiv.classList.add('hidden'); }, 3000);
                } else {
                    button.innerHTML = `Locked (${remaining}s)`;
                    errorDiv.textContent = `Too many failed attempts. Please wait ${remaining} seconds before trying again.`;
                }
            }, 1000);
        }

        // ========== LOGIN FORM HANDLER ==========
        const loginForm = document.getElementById('loginForm');
        const loginError = document.getElementById('loginError');
        const loginSubmitBtn = document.getElementById('loginSubmitBtn');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
                if (loginError) loginError.classList.add('hidden');
                if (loginSubmitBtn) {
                    loginSubmitBtn.disabled = true;
                    loginSubmitBtn.innerHTML = '<span class="spinner"></span> Logging in...';
                }
                const formData = new FormData(loginForm);
                const url = '../includes/login.php';
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(Object.fromEntries(formData.entries()))
                    });
                    const result = await response.json();
                    if (result.success && result.requires_otp) {
                        if (loginError) {
                            loginError.textContent = result.message || 'Enter the OTP sent to your email.';
                            loginError.classList.remove('hidden');
                        }
                        openOtpModal(result.otp, formData.get('email'));
                        if (loginSubmitBtn) {
                            loginSubmitBtn.disabled = false;
                            loginSubmitBtn.innerHTML = 'Log in';
                        }
                        return;
                    }
                    if (result.success && result.redirect) {
                        window.location.href = result.redirect;
                        return;
                    }
                    const message = result.message || 'Login failed. Please try again.';
                    if (message.includes('seconds') && (message.includes('wait') || message.includes('locked'))) {
                        const secondsMatch = message.match(/(\d+)\s+seconds/);
                        if (secondsMatch && loginSubmitBtn && loginError) {
                            let seconds = parseInt(secondsMatch[1]);
                            startCountdown(seconds, loginSubmitBtn, loginError);
                            return;
                        }
                    }
                    if (loginError) {
                        loginError.textContent = message;
                        loginError.classList.remove('hidden');
                    }
                    if (loginSubmitBtn) {
                        loginSubmitBtn.disabled = false;
                        loginSubmitBtn.innerHTML = 'Log in';
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    if (loginError) {
                        loginError.textContent = 'Connection error. Please try again.';
                        loginError.classList.remove('hidden');
                    }
                    if (loginSubmitBtn) {
                        loginSubmitBtn.disabled = false;
                        loginSubmitBtn.innerHTML = 'Log in';
                    }
                }
            });
        }
        
        // ========== REGISTER FORM HANDLER ==========
        const registerForm = document.getElementById('registerForm');
        const registerError = document.getElementById('registerError');
        const registerSuccess = document.getElementById('registerSuccess');
        const registerSubmitBtn = document.getElementById('registerSubmitBtn');

        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                let isValid = true;
                
                // Validate all fields
                const fullName = document.getElementById('full_name').value;
                if (!validateFullName(fullName)) isValid = false;
                
                const birthdate = document.getElementById('birthdate').value;
                if (!validateBirthdate(birthdate)) isValid = false;
                
                const email = document.getElementById('register_email').value;
                if (!validateEmail(email)) isValid = false;
                
                const mobile = document.getElementById('mobile').value;
                if (!validateMobile(mobile)) isValid = false;
                
                const userId = document.getElementById('user_id_number').value;
                if (!validateUserId(userId)) isValid = false;
                
                const role = document.getElementById('user_role').value;
                if (!validateRole(role)) isValid = false;
                
                const password = document.getElementById('password').value;
                if (!validatePassword(password)) isValid = false;
                
                const confirmPassword = document.getElementById('confirm_password').value;
                if (!validateConfirmPassword(password, confirmPassword)) isValid = false;
                
                const terms = document.getElementById('terms').checked;
                if (!terms) {
                    if (registerError) { 
                        registerError.textContent = 'Please agree to the Terms and Conditions'; 
                        registerError.classList.remove('hidden'); 
                    }
                    isValid = false;
                }
                
                if (!isValid) {
                    if (registerSubmitBtn) { 
                        registerSubmitBtn.disabled = false; 
                        registerSubmitBtn.innerHTML = 'Sign Up'; 
                    }
                    return;
                }
                
                if (registerError) registerError.classList.add('hidden');
                if (registerSuccess) registerSuccess.classList.add('hidden');
                
                if (registerSubmitBtn) {
                    registerSubmitBtn.disabled = true;
                    registerSubmitBtn.innerHTML = '<span class="spinner"></span> Creating account...';
                }
                
                const formData = new FormData(registerForm);
                try {
                    const response = await fetch('../includes/register.php', { 
                        method: 'POST', 
                        body: formData 
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        if (registerSuccess) { 
                            registerSuccess.textContent = result.message || 'Account created successfully! Please check your email for verification.'; 
                            registerSuccess.classList.remove('hidden'); 
                        }
                        registerForm.reset();
                        
                        // Close register modal and open login after delay
                        setTimeout(() => { 
                            closeRegisterModal(); 
                            openLoginModal(); 
                        }, 2500);
                    } else {
                        if (registerError) { 
                            registerError.textContent = result.message || 'Registration failed. Please try again.'; 
                            registerError.classList.remove('hidden'); 
                        }
                        if (registerSubmitBtn) { 
                            registerSubmitBtn.disabled = false; 
                            registerSubmitBtn.innerHTML = 'Sign Up'; 
                        }
                    }
                } catch (error) {
                    console.error('Registration error:', error);
                    if (registerError) { 
                        registerError.textContent = 'Connection error: ' + error.message; 
                        registerError.classList.remove('hidden'); 
                    }
                    if (registerSubmitBtn) { 
                        registerSubmitBtn.disabled = false; 
                        registerSubmitBtn.innerHTML = 'Sign Up'; 
                    }
                }
            });
        }

        // ========== OTP VERIFICATION HANDLER ==========
        const otpForm = document.getElementById('otpForm');
        const otpError = document.getElementById('otpError');
        const otpSuccess = document.getElementById('otpSuccess');
        const otpSubmitBtn = document.getElementById('otpSubmitBtn');
        const resendOtpBtn = document.getElementById('resendOtpBtn');

        if (otpForm) {
            otpForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (otpError) otpError.classList.add('hidden');
                if (otpSuccess) otpSuccess.classList.add('hidden');
                if (otpSubmitBtn) {
                    otpSubmitBtn.disabled = true;
                    otpSubmitBtn.innerHTML = '<span class="spinner"></span> Verifying...';
                }

                const formData = new FormData(otpForm);
                try {
                    const response = await fetch('../includes/verify_otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(Object.fromEntries(formData.entries()))
                    });
                    const result = await response.json();
                    if (result.success) {
                        if (otpSuccess) {
                            otpSuccess.textContent = result.message || 'OTP verified.';
                            otpSuccess.classList.remove('hidden');
                        }
                        window.location.href = result.redirect || 'User_Dashboard.php';
                    } else {
                        if (otpError) {
                            otpError.textContent = result.message;
                            otpError.classList.remove('hidden');
                        }
                        if (otpSubmitBtn) {
                            otpSubmitBtn.disabled = false;
                            otpSubmitBtn.innerHTML = 'Verify Code';
                        }
                    }
                } catch (error) {
                    console.error('OTP verification error:', error);
                    if (otpError) {
                        otpError.textContent = 'Connection error. Please try again.';
                        otpError.classList.remove('hidden');
                    }
                    if (otpSubmitBtn) {
                        otpSubmitBtn.disabled = false;
                        otpSubmitBtn.innerHTML = 'Verify Code';
                    }
                }
            });
        }

        if (resendOtpBtn) {
            resendOtpBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (otpError) otpError.classList.add('hidden');
                if (otpSuccess) otpSuccess.classList.add('hidden');
                if (resendOtpBtn) resendOtpBtn.disabled = true;

                try {
                    const response = await fetch('../includes/resend_otp.php', { method: 'POST' });
                    const result = await response.json();
                    if (result.success) {
                        if (otpSuccess) {
                            otpSuccess.textContent = result.message || 'OTP resent successfully.';
                            otpSuccess.classList.remove('hidden');
                        }
                        const otpDebugHint = document.getElementById('otpDebugHint');
                        if (otpDebugHint && result.otp) {
                            otpDebugHint.textContent = `Test OTP: ${result.otp}`;
                            otpDebugHint.classList.remove('hidden');
                        }
                    } else {
                        if (otpError) {
                            otpError.textContent = result.message;
                            otpError.classList.remove('hidden');
                        }
                    }
                } catch (error) {
                    console.error('Resend OTP error:', error);
                    if (otpError) {
                        otpError.textContent = 'Unable to resend OTP. Please try again.';
                        otpError.classList.remove('hidden');
                    }
                } finally {
                    if (resendOtpBtn) resendOtpBtn.disabled = false;
                }
            });
        }

        // ========== CONTACT FORM ==========
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => {
                e.preventDefault();
                alert('Thank you for your message! We will get back to you soon.');
                contactForm.reset();
            });
        }

        // ========== STATS ANIMATIONS ==========
        let isAnimating = false;
        function resetCountersToZero() {
            document.querySelectorAll('.counter').forEach(counter => {
                const suffix = counter.getAttribute('data-suffix') || '';
                counter.textContent = '0' + suffix;
            });
        }
        function animateNumbers() {
            if (isAnimating) return;
            isAnimating = true;
            document.querySelectorAll('.counter').forEach(counter => {
                if (counter.animationFrameId) cancelAnimationFrame(counter.animationFrameId);
                const target = parseInt(counter.getAttribute('data-target'));
                const suffix = counter.getAttribute('data-suffix') || '';
                let current = 0;
                const duration = 1500, stepTime = 20, steps = duration / stepTime, increment = target / steps;
                let currentStep = 0;
                const updateCounter = () => {
                    currentStep++;
                    current += increment;
                    if (currentStep < steps) {
                        counter.textContent = Math.floor(current) + suffix;
                        counter.animationFrameId = requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target + suffix;
                        counter.animationFrameId = null;
                    }
                };
                counter.animationFrameId = requestAnimationFrame(updateCounter);
            });
            setTimeout(() => { isAnimating = false; }, 1600);
        }

        // ========== SCROLL REVEAL ==========
        document.addEventListener('DOMContentLoaded', function() {
            const statsGrid = document.getElementById('statsGrid');
            if (statsGrid) {
                const statsObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => { if (entry.isIntersecting) { resetCountersToZero(); setTimeout(() => animateNumbers(), 100); } });
                }, { threshold: 0.3 });
                statsObserver.observe(statsGrid);
                const rect = statsGrid.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 50) setTimeout(() => animateNumbers(), 500);
            }
            
            document.querySelectorAll('.scroll-reveal').forEach(el => {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
                }, { threshold: 0.15 });
                observer.observe(el);
            });
            
            // Ensure hero section takes full viewport height on load and resize
            function setHeroHeight() {
                const hero = document.querySelector('.hero-section');
                if (hero) {
                    hero.style.height = window.innerHeight + 'px';
                }
            }
            setHeroHeight();
            window.addEventListener('resize', setHeroHeight);
        });
    })();

    // ========== GOOGLE SIGN-IN ==========
    function handleGoogleCredentialResponse(response) {
        console.log("Google Sign-In response received");
        const loginError = document.getElementById('loginError');
        if (loginError) loginError.classList.add('hidden');
        
        fetch('../includes/google-login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ credential: response.credential })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || 'User_Dashboard.php';
            } else {
                if (loginError) {
                    loginError.textContent = data.message || 'Google login failed.';
                    loginError.classList.remove('hidden');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (loginError) {
                loginError.textContent = 'Connection error. Please try again.';
                loginError.classList.remove('hidden');
            }
        });
    }

    function initializeGoogleSignIn() {
        console.log("Initializing Google Sign-In...");
        if (typeof google !== 'undefined' && google.accounts) {
            console.log("Google API loaded, rendering button...");
            const redirectUri = window.location.origin + '/Front_Page.php';
            google.accounts.id.initialize({
                client_id: '508652382424-nohaa80puuhvfhpumvjkcdl6248qbt4k.apps.googleusercontent.com',
                callback: handleGoogleCredentialResponse,
                ux_mode: 'redirect',
                redirect_uri: redirectUri,
                auto_select: false
            });
            google.accounts.id.renderButton(
                document.getElementById('googleSignInButton'),
                { theme: 'outline', size: 'large', width: '100%', text: 'signin_with', shape: 'rectangular', logo_alignment: 'left' }
            );
        } else {
            console.log("Google API not loaded yet, retrying...");
            setTimeout(initializeGoogleSignIn, 500);
        }
    }

    // Run when page is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(initializeGoogleSignIn, 500); });
    } else {
        setTimeout(initializeGoogleSignIn, 500);
    }

    // ========== HERO SLIDESHOW ==========
    (function() {
        const slides = document.querySelectorAll('.slide-image');
        if (!slides.length) return;
        let currentIndex = 0, slideInterval;
        const INTERVAL_TIME = 5000;
        function showSlide(index) {
            slides.forEach((slide, i) => slide.classList.remove('active'));
            if (slides[index]) slides[index].classList.add('active');
        }
        function nextSlide() { currentIndex = (currentIndex + 1) % slides.length; showSlide(currentIndex); }
        function startSlideshow() { if (slideInterval) clearInterval(slideInterval); slideInterval = setInterval(nextSlide, INTERVAL_TIME); }
        showSlide(0);
        startSlideshow();
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            heroSection.addEventListener('mouseenter', () => { if (slideInterval) { clearInterval(slideInterval); slideInterval = null; } });
            heroSection.addEventListener('mouseleave', () => { if (!slideInterval) startSlideshow(); });
        }
    })();
</script>
</body>
</html>