<?php
// ============================================
// ENABLE ERROR REPORTING FOR DEBUGGING
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// START SESSION
// ============================================
session_start();

// ============================================
// FAKE LOGIN SESSION - FOR TESTING ONLY
// ============================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['user_id'] = 1;
    $_SESSION['full_name'] = 'Juan Dela Cruz';
    $_SESSION['email'] = 'test@umak.edu.ph';
    $_SESSION['role'] = 'student';
    $_SESSION['logged_in'] = true;
    $_SESSION['college'] = 'College of Information Technology';
    $_SESSION['course'] = 'BS Information Technology';
    $_SESSION['year_level'] = '3rd Year';
    $_SESSION['student_id'] = '2026-00001';
}

// ============================================
// INCLUDE DATABASE CONNECTION
// ============================================
$configPath = dirname(__DIR__) . '/config/db_connect.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    $fallbackPath = '../config/db_connect.php';
    if (file_exists($fallbackPath)) {
        require_once $fallbackPath;
    } else {
        die('Database connection file not found.');
    }
}

// ============================================
// GET USER DATA FROM SESSION
// ============================================
$user_id = $_SESSION['user_id'] ?? 1;
$user_fullname = $_SESSION['full_name'] ?? 'Juan Dela Cruz';
$user_email = $_SESSION['email'] ?? 'juan.delacruz@umak.edu.ph';
$user_role = $_SESSION['role'] ?? 'student';

// Get initials for avatar
$nameParts = explode(' ', $user_fullname);
$user_initials = '';
if (count($nameParts) >= 2) {
    $user_initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
} else {
    $user_initials = strtoupper(substr($user_fullname, 0, 2));
}

// ============================================
// GET TOR DATA
// ============================================
$tor_requests = [];
$tor_status = 'No TOR request';

try {
    if (function_exists('getDBConnection')) {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM tor_requests WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $tor_requests = $stmt->fetchAll();
            
            if (!empty($tor_requests)) {
                $tor_status = $tor_requests[0]['status'] ?? 'Pending';
            }
        }
    }
} catch (PDOException $e) {
    error_log("Database error in tor: " . $e->getMessage());
    // Sample TOR data
    $tor_requests = [
        ['id' => 1, 'request_date' => '2026-01-15', 'purpose' => 'Job Application', 'status' => 'Processing', 'status_class' => 'status-processing'],
        ['id' => 2, 'request_date' => '2025-12-10', 'purpose' => 'Graduate School', 'status' => 'Completed', 'status_class' => 'status-completed']
    ];
    $tor_status = 'Processing';
}

// Sample TOR grades (academic record)
$tor_grades = [
    '1st Year 2024-2025' => [
        ['code' => 'IT 101', 'name' => 'Introduction to Computing', 'units' => 3, 'grade' => '1.50'],
        ['code' => 'IT 102', 'name' => 'Programming Logic and Design', 'units' => 3, 'grade' => '1.75'],
        ['code' => 'GE 101', 'name' => 'Understanding the Self', 'units' => 3, 'grade' => '2.00'],
        ['code' => 'GE 102', 'name' => 'Purposive Communication', 'units' => 3, 'grade' => '1.50'],
        ['code' => 'GE 103', 'name' => 'Mathematics in the Modern World', 'units' => 3, 'grade' => '1.75'],
    ],
    '2nd Year 2025-2026' => [
        ['code' => 'IT 201', 'name' => 'Data Structures and Algorithms', 'units' => 3, 'grade' => '1.25'],
        ['code' => 'IT 202', 'name' => 'Object-Oriented Programming', 'units' => 3, 'grade' => '1.50'],
        ['code' => 'IT 203', 'name' => 'Database Management Systems', 'units' => 3, 'grade' => '1.75'],
        ['code' => 'GE 104', 'name' => 'Readings in Philippine History', 'units' => 3, 'grade' => '2.00'],
        ['code' => 'GE 105', 'name' => 'Science, Technology, and Society', 'units' => 3, 'grade' => '1.50'],
    ],
    '3rd Year 2026-2027' => [
        ['code' => 'IT 301', 'name' => 'Web Development', 'units' => 3, 'grade' => '1.25'],
        ['code' => 'IT 302', 'name' => 'System Analysis and Design', 'units' => 3, 'grade' => '1.50'],
        ['code' => 'IT 303', 'name' => 'Computer Networks', 'units' => 3, 'grade' => '1.75'],
        ['code' => 'IT 304', 'name' => 'Information Assurance', 'units' => 3, 'grade' => '1.50'],
    ]
];

$total_units = 0;
$total_points = 0;
$grade_points = ['1.00' => 1.0, '1.25' => 1.25, '1.50' => 1.5, '1.75' => 1.75, '2.00' => 2.0, '2.25' => 2.25, '2.50' => 2.5, '2.75' => 2.75, '3.00' => 3.0, '5.00' => 5.0];

foreach ($tor_grades as $year => $subjects) {
    foreach ($subjects as $subject) {
        $total_units += $subject['units'];
        $grade_val = $grade_points[$subject['grade']] ?? 0;
        $total_points += $grade_val * $subject['units'];
    }
}
$gwa = $total_units > 0 ? round($total_points / $total_units, 2) : 0;

// ============================================
// UPDATE SESSION
// ============================================
$_SESSION['full_name'] = $user_fullname;
$_SESSION['email'] = $user_email;
$_SESSION['role'] = $user_role;
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>UMak · Registrar Portal · TOR</title>

    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Osage&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Noto Sans Osage', sans-serif;
            background: #f8f7f4;
            color: #1a2a3a;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        body.dark-mode {
            background: #0d1117;
            color: #e6edf3;
        }
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3,
        body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
        body.dark-mode p, body.dark-mode span, body.dark-mode div,
        body.dark-mode a, body.dark-mode label {
            color: #e6edf3 !important;
        }
        body.dark-mode .brand-name { color: #ffffff !important; }
        body.dark-mode .header {
            background: #161b22;
            border-bottom-color: #ffd700;
        }
        body.dark-mode .card {
            background: #161b22;
            border-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .side-nav {
            background: #0d1117;
            border-right-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .side-nav-header {
            background: #0d1117;
            border-bottom-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .side-nav-brand { color: #ffffff !important; }
        body.dark-mode .side-nav-link { color: #b0b0c8 !important; }
        body.dark-mode .side-nav-link:hover {
            background: #3a3a55;
            color: #e6edf3 !important;
        }
        body.dark-mode .side-nav-link.active {
            background: rgba(255,215,0,0.12);
            color: #ffd700 !important;
        }
        body.dark-mode .side-nav-close {
            background: #3a3a55;
            color: #b0b0c8;
        }
        body.dark-mode .side-nav-footer {
            border-top-color: rgba(255,255,255,0.08);
            color: #8a8aaa !important;
        }
        body.dark-mode .offcanvas {
            background: #0d1117;
            border-color: rgba(255,215,0,0.15);
        }
        body.dark-mode .offcanvas-header {
            background: #0d1117;
            border-bottom-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .offcanvas-close {
            background: #3a3a55;
            color: #b0b0c8;
        }
        body.dark-mode .dropdown-menu {
            background: #0d1117;
            border-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .dd-header {
            border-bottom-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .dd-item:hover { background: #3a3a55; }
        body.dark-mode .avatar-btn {
            background: rgba(255,215,0,0.08);
            border-color: rgba(255,215,0,0.12);
        }
        body.dark-mode .avatar-btn:hover { background: rgba(255,215,0,0.15); }
        body.dark-mode .avatar-circle {
            background: #ffd700;
            color: #0d1117 !important;
        }
        body.dark-mode .avatar-name { color: #e6edf3 !important; }
        body.dark-mode .icon-btn { color: rgba(255,255,255,0.6); }
        body.dark-mode .icon-btn:hover {
            color: #fff;
            background: rgba(255,215,0,0.12);
        }
        body.dark-mode .hamburger-btn { color: rgba(255,255,255,0.8); }
        body.dark-mode .hamburger-btn:hover { background: rgba(255,215,0,0.12); }
        body.dark-mode .nav-overlay { background: rgba(0,0,0,0.6); }
        body.dark-mode .offcanvas-overlay { background: rgba(0,0,0,0.6); }
        body.dark-mode footer { border-top-color: rgba(255,255,255,0.08) !important; }
        body.dark-mode .status-badge.processing {
            background: rgba(26,77,122,0.25);
            color: #7aa9d9 !important;
        }
        body.dark-mode .status-badge.completed {
            background: rgba(5,150,105,0.2);
            color: #34d399 !important;
        }
        body.dark-mode .status-badge.pending {
            background: rgba(184,134,11,0.2);
            color: #f5b342 !important;
        }
        body.dark-mode .tor-grade { color: #ffd700 !important; }
        body.dark-mode .stat-box {
            background: #161b22;
            border-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .stat-box .stat-value { color: #e6edf3 !important; }
        body.dark-mode .stat-box .stat-label { color: #8a8aaa !important; }

        :root {
            --primary: #003366;
            --primary-dark: #002244;
            --primary-light: #1a4d7a;
            --secondary: #FFD700;
            --secondary-light: #FFE44D;
            --secondary-dark: #E6C200;
            --surface: #ffffff;
            --surface-2: #f8f7f4;
            --surface-3: #f0eee9;
            --border: rgba(0,51,102,0.10);
            --border-md: rgba(0,51,102,0.18);
            --text-1: #1a2a3a;
            --text-2: #4a5a6a;
            --text-3: #8a9aaa;
            --shadow-sm: 0 1px 3px rgba(0,51,102,0.06), 0 1px 2px rgba(0,51,102,0.04);
            --shadow-md: 0 4px 16px rgba(0,51,102,0.08), 0 1px 4px rgba(0,51,102,0.05);
            --shadow-lg: 0 8px 32px rgba(0,51,102,0.12);
        }

        * { font-family: 'Noto Sans Osage', sans-serif; }

        .header {
            background: var(--primary);
            position: sticky; top: 0; z-index: 100;
            transition: background 0.3s ease;
            width: 100%;
            padding: 0;
        }
        .header-inner {
            max-width: 100%;
            margin: 0;
            padding: 0 0.75rem;
            height: 68px;
            display: flex; align-items: center; gap: 1rem;
        }
        .brand { display: flex; flex-direction: column; gap: 1px; min-width: 140px; text-decoration: none; }
        .brand-name { font-size: 1.2rem; font-weight: 700; color: #fff; letter-spacing: 0.01em; line-height: 1; }
        .brand-sub  { font-size: 0.55rem; color: rgba(255,255,255,0.5); font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; }

        .hamburger-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.85);
            cursor: pointer;
            padding: 4px;
            margin-right: 2px;
            border-radius: 6px;
            transition: background 0.15s;
        }
        .hamburger-btn:hover { background: rgba(255,215,0,0.12); }
        .hamburger-btn svg { width: 24px; height: 24px; stroke: currentColor; stroke-width: 2.2; fill: none; }

        .header-nav-links {
            display: flex;
            align-items: center;
            gap: 0.15rem;
            margin-left: auto;
            flex-wrap: nowrap;
        }

        .header-nav-link {
            color: rgba(255,255,255,0.6);
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            background: transparent !important;
            border: none !important;
            cursor: pointer;
            font-weight: 400;
            letter-spacing: 0.02em;
        }
        .header-nav-link:hover {
            color: rgba(255,255,255,0.95);
            background: rgba(255,215,0,0.08) !important;
        }
        .header-nav-link.active {
            color: #ffffff !important;
            background: rgba(255,215,0,0.12) !important;
            font-weight: 500;
        }

        .side-nav {
            position: fixed;
            top: 0;
            left: -320px;
            width: 300px;
            max-width: 80vw;
            height: 100vh;
            background: var(--surface);
            box-shadow: 4px 0 24px rgba(0,0,0,0.06);
            z-index: 300;
            transition: left 0.28s cubic-bezier(0.2, 0.9, 0.4, 1.1), background 0.3s ease;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
            overflow-y: auto;
        }
        .side-nav.open { left: 0; }
        .side-nav-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.25rem 0.75rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }
        .side-nav-brand {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        .side-nav-brand span { color: var(--secondary-dark); }
        .side-nav-close {
            background: var(--surface-3);
            border: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-2);
            transition: background 0.15s;
        }
        .side-nav-close:hover { background: var(--surface-2); color: var(--text-1); }
        .side-nav-close svg { width: 16px; height: 16px; stroke: currentColor; stroke-width: 2.5; fill: none; }
        .side-nav-body { padding: 0.75rem 1rem 1rem; flex: 1; }
        .side-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.6rem 0.9rem;
            border-radius: 0px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-2);
            text-decoration: none;
            transition: background 0.12s, color 0.12s;
            margin-bottom: 2px;
            cursor: pointer;
        }
        .side-nav-link:hover { background: var(--surface-2); color: var(--text-1); }
        .side-nav-link.active {
            background: rgba(0,51,102,0.06);
            color: var(--primary);
            font-weight: 600;
        }
        .side-nav-link svg { width: 18px; height: 18px; stroke: currentColor; stroke-width: 2; fill: none; flex-shrink: 0; }
        .side-nav-divider { height: 1px; background: var(--border); margin: 0.5rem 0.5rem; }
        .side-nav-footer {
            padding: 0.75rem 1rem 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.75rem;
            color: var(--text-3);
        }
        .nav-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10,20,40,0.35);
            backdrop-filter: blur(2px);
            z-index: 299;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }
        .nav-overlay.open { opacity: 1; visibility: visible; }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }

        .avatar-btn {
            display: flex; align-items: center; gap: 6px;
            background: rgba(255,215,0,0.12);
            border: 1px solid rgba(255,215,0,0.2);
            border-radius: 0px;
            padding: 3px 8px 3px 3px;
            cursor: pointer; transition: background 0.15s;
        }
        .avatar-btn:hover { background: rgba(255,215,0,0.2); }
        .avatar-circle {
            width: 26px; height: 26px; border-radius: 0px;
            background: var(--secondary);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 700;
            color: var(--primary);
        }
        .avatar-name { font-size: 0.75rem; color: rgba(255,255,255,0.85); font-weight: 400; }

        .page {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1.75rem 1.5rem 3rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0px;
            box-shadow: var(--shadow-sm);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .card-body { padding: 1rem 1.375rem 1.375rem; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 16px;
            border-radius: 0px;
            background: var(--primary); color: #fff;
            font-size: 0.8125rem; font-weight: 600;
            border: none; cursor: pointer; text-decoration: none;
            transition: background 0.15s;
        }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }

        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-1);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0px;
            padding: 1rem 1.25rem;
            box-shadow: var(--shadow-sm);
        }
        .stat-box .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        .stat-box .stat-label {
            font-size: 0.75rem;
            color: var(--text-3);
            margin-top: 2px;
        }

        .tor-status-bar {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--surface-2);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }
        .tor-status-bar .status-label { font-size: 0.75rem; color: var(--text-3); font-weight: 500; }
        .tor-status-bar .status-value { font-size: 1rem; font-weight: 600; }
        .status-badge.processing { background: #e8f0fe; color: #1a4d7a; padding: 4px 16px; }
        .status-badge.completed { background: #e6f7e6; color: #1e7e34; padding: 4px 16px; }
        .status-badge.pending { background: #fef9e7; color: #b8860b; padding: 4px 16px; }

        .tor-year-section {
            margin-bottom: 1.5rem;
        }
        .tor-year-section:last-child { margin-bottom: 0; }
        .tor-year-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-1);
            padding: 0.5rem 0 0.25rem;
            border-bottom: 2px solid var(--border);
            margin-bottom: 0.5rem;
        }

        .tor-item {
            display: grid;
            grid-template-columns: 100px 1fr 60px 60px;
            gap: 0.75rem;
            padding: 0.4rem 0;
            border-bottom: 1px solid var(--border);
            align-items: center;
            font-size: 0.85rem;
        }
        .tor-item:last-child { border-bottom: none; }
        .tor-item .code { font-weight: 500; color: var(--text-2); }
        .tor-item .name { color: var(--text-1); }
        .tor-item .units { text-align: center; }
        .tor-item .grade {
            text-align: center;
            font-weight: 700;
            color: var(--primary);
        }

        .tor-request-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--border);
        }
        .tor-request-item:last-child { border-bottom: none; }
        .tor-request-item .req-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .tor-request-item .req-date { font-size: 0.7rem; color: var(--text-3); }
        .tor-request-item .req-purpose { font-size: 0.8rem; color: var(--text-2); }
        .tor-request-item .req-status {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 0px;
        }

        .fade-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s cubic-bezier(0.2, 0.9, 0.4, 1), transform 0.8s cubic-bezier(0.2, 0.9, 0.4, 1);
        }
        .fade-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-menu {
            position: absolute; right: 0; top: calc(100% + 8px);
            width: 260px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0px;
            box-shadow: var(--shadow-lg);
            z-index: 200;
            display: none;
            overflow: hidden;
        }
        .dropdown-menu.open { display: block; animation: dropIn 0.14s ease; }
        @keyframes dropIn { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform: translateY(0); } }
        .dd-header { padding: 14px 16px 12px; border-bottom: 1px solid var(--border); }
        .dd-name { font-size: 0.875rem; font-weight: 600; color: var(--text-1); }
        .dd-email { font-size: 0.75rem; color: var(--text-3); margin-top: 2px; }
        .dd-section { padding: 8px 8px; }
        .dd-label { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-3); padding: 4px 8px 6px; }
        .dd-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px;
            border-radius: 0px;
            font-size: 0.8125rem; color: var(--text-2); cursor: pointer;
            transition: background 0.12s;
        }
        .dd-item:hover { background: var(--surface-2); }
        .dd-item.danger { color: #dc2626; }
        .dd-item.danger:hover { background: #fef2f2; }
        .dd-divider { height: 1px; background: var(--border); margin: 4px 8px; }

        .offcanvas {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.92);
            width: 460px;
            max-width: 90vw;
            max-height: 80vh;
            background: var(--surface);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            z-index: 250;
            border-radius: 0px;
            transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1), 
                        opacity 0.3s ease,
                        background 0.3s ease,
                        box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 2px solid rgba(255,215,0,0.15);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .offcanvas.open {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .offcanvas-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            flex-shrink: 0;
        }
        .offcanvas-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-1);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .offcanvas-close {
            width: 30px;
            height: 30px;
            border-radius: 0px;
            border: none;
            background: var(--surface-3);
            color: var(--text-2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
        }
        .offcanvas-close:hover { background: var(--surface-2); color: var(--text-1); }
        .offcanvas-body { flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem; }
        .offcanvas-section { margin-bottom: 1.5rem; }
        .offcanvas-section:last-child { margin-bottom: 0; }
        .offcanvas-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-3);
            margin-bottom: 0.75rem;
        }
        .offcanvas-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10,20,40,0.5);
            backdrop-filter: blur(4px);
            z-index: 249;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease, background 0.3s ease;
        }
        .offcanvas-overlay.open { opacity: 1; visibility: visible; }

        .profile-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1rem;
            font-size: 0.85rem;
        }
        .profile-info-grid .info-label { color: var(--text-3); font-weight: 400; }
        .profile-info-grid .info-value { color: var(--text-1); font-weight: 500; word-break: break-word; }
        .profile-header-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 0.75rem 0 1.25rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1.25rem;
        }
        .profile-header-card .profile-avatar-large {
            width: 64px;
            height: 64px;
            border-radius: 0px;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            flex-shrink: 0;
        }
        .profile-header-card .profile-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-1);
        }
        .profile-header-card .profile-role {
            font-size: 0.8rem;
            color: var(--text-3);
        }

        .fab {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 56px;
            height: 56px;
            border-radius: 0px;
            background: var(--primary);
            border: 2px solid var(--secondary);
            box-shadow: 0 6px 18px rgba(0,51,102,0.25);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 150;
        }
        .fab:hover {
            transform: scale(1.05);
            background: var(--primary-light);
            box-shadow: 0 8px 24px rgba(0,51,102,0.35);
        }
        .fab svg { width: 26px; height: 26px; stroke: #fff; stroke-width: 2; fill: none; }

        @media(max-width:768px){
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .offcanvas { width: 85vw; max-height: 85vh; }
            .header-nav-links { gap: 0.05rem; }
            .header-nav-link { font-size: 0.6rem; padding: 0.25rem 0.4rem; }
            .profile-info-grid { grid-template-columns: 1fr; gap: 0.5rem; }
            .profile-header-card { flex-direction: column; text-align: center; }
            .tor-item { grid-template-columns: 1fr; gap: 0.25rem; padding: 0.5rem 0; }
            .tor-item .units, .tor-item .grade { text-align: left; }
            .tor-status-bar { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
        @media(max-width:480px){
            .stats-grid { grid-template-columns: 1fr; }
            .header-nav-links { display: none !important; }
            .page { padding: 1rem; }
            .offcanvas { width: 92vw; max-height: 90vh; }
        }
    </style>
</head>
<body>

<!-- ========== SIDE NAV ========== -->
<div class="nav-overlay" id="sideNavOverlay" onclick="closeSideNav()"></div>
<div class="side-nav" id="sideNav">
    <div class="side-nav-header">
        <a href="#" class="side-nav-brand">UMak<span>·Registrar</span></a>
        <button class="side-nav-close" onclick="closeSideNav()">
            <svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="side-nav-body">
        <a href="User_Dashboard.php" class="side-nav-link">
            <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="enrollment.php" class="side-nav-link">
            <svg viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Enrollment
        </a>
        <a href="grades.php" class="side-nav-link">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Grades
        </a>
        <a href="tor.php" class="side-nav-link active">
            <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            TOR
        </a>
        <a href="certificates.php" class="side-nav-link">
            <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Certificates
        </a>
        <div class="side-nav-divider"></div>
        <div class="side-nav-dark-toggle" onclick="toggleDarkModeFromSideNav()">
            <span style="display:flex;align-items:center;gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                Dark Mode
            </span>
            <span class="toggle-track">
                <span class="toggle-thumb"></span>
            </span>
        </div>
    </div>
    <div class="side-nav-footer">&copy; 2026 UMak · Registrar</div>
</div>

<!-- ========== HEADER ========== -->
<header class="header">
    <div class="header-inner">
        <button class="hamburger-btn" onclick="openSideNav()" aria-label="Open menu">
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <a class="brand" href="User_Dashboard.php">
            <span class="brand-name">UMak<span style="color:#FFD700;">·Registrar</span></span>
            <span class="brand-sub">University of Makati · Registrar Portal</span>
        </a>
        <div style="flex:1;"></div>
        <nav class="header-nav-links" id="headerNavLinks">
            <a href="User_Dashboard.php" class="header-nav-link">Dashboard</a>
            <a href="enrollment.php" class="header-nav-link">Enrollment</a>
            <a href="grades.php" class="header-nav-link">Grades</a>
            <a href="tor.php" class="header-nav-link active">TOR</a>
            <a href="certificates.php" class="header-nav-link">Certificates</a>
        </nav>
        <div class="nav-actions">
            <div style="position:relative;" id="profileWrap">
                <button class="avatar-btn" onclick="toggleDropdown()">
                    <div class="avatar-circle"><?php echo $user_initials; ?></div>
                    <span class="avatar-name"><?php echo htmlspecialchars(explode(' ', $user_fullname)[0]); ?></span>
                    <svg style="width:12px;height:12px;color:rgba(255,255,255,0.5);margin-left:1px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="dropdown-menu" id="profileDropdown">
                    <div class="dd-header">
                        <div class="dd-name"><?php echo htmlspecialchars($user_fullname); ?></div>
                        <div class="dd-email"><?php echo htmlspecialchars($user_email); ?></div>
                    </div>
                    <div class="dd-divider"></div>
                    <div class="dd-section">
                        <div class="dd-label">Account</div>
                        <div class="dd-item" onclick="openOffcanvas()">My Profile</div>
                        <div class="dd-divider"></div>
                        <div class="dd-item danger" onclick="doSignOut()">Sign Out</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ========== OFF-CANVAS ========== -->
<div class="offcanvas-overlay" id="offcanvasOverlay" onclick="closeOffcanvas()"></div>
<div class="offcanvas" id="offcanvasPanel">
    <div class="offcanvas-header">
        <h3><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg> My Profile</h3>
        <button class="offcanvas-close" onclick="closeOffcanvas()">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:14px;height:14px"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="offcanvas-body">
        <div class="profile-header-card">
            <div class="profile-avatar-large"><?php echo $user_initials; ?></div>
            <div>
                <div class="profile-name"><?php echo htmlspecialchars($user_fullname); ?></div>
                <div class="profile-role"><?php echo htmlspecialchars($user_role); ?></div>
            </div>
        </div>
        <div class="offcanvas-section">
            <div class="offcanvas-section-title">Student Information</div>
            <div class="profile-info-grid">
                <span class="info-label">Student ID</span>
                <span class="info-value"><?php echo htmlspecialchars($_SESSION['student_id'] ?? '2026-XXXXX'); ?></span>
                <span class="info-label">Full Name</span>
                <span class="info-value"><?php echo htmlspecialchars($user_fullname); ?></span>
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo htmlspecialchars($user_email); ?></span>
                <span class="info-label">College</span>
                <span class="info-value"><?php echo htmlspecialchars($_SESSION['college'] ?? 'College of Information Technology'); ?></span>
                <span class="info-label">Course</span>
                <span class="info-value"><?php echo htmlspecialchars($_SESSION['course'] ?? 'BS Information Technology'); ?></span>
                <span class="info-label">Year Level</span>
                <span class="info-value"><?php echo htmlspecialchars($_SESSION['year_level'] ?? '3rd Year'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ========== MAIN CONTENT ========== -->
<main class="page">
    <div class="section-header">
        <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Transcript of Records (TOR)
        </h2>
        <div style="display:flex;gap:0.5rem;">
            <button class="btn-primary" onclick="alert('Request TOR')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                Request TOR
            </button>
            <button class="btn-primary btn-success" onclick="alert('Download TOR')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid fade-scroll">
        <div class="stat-box">
            <div class="stat-value"><?php echo number_format($gwa, 2); ?></div>
            <div class="stat-label">GWA</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $total_units; ?></div>
            <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo count($tor_grades); ?></div>
            <div class="stat-label">Academic Years</div>
        </div>
    </div>

    <!-- TOR Status -->
    <div class="tor-status-bar fade-scroll">
        <div>
            <span class="status-label">TOR Request Status</span>
            <div>
                <span class="status-value status-badge <?php echo strtolower($tor_status); ?>">
                    <?php echo htmlspecialchars($tor_status); ?>
                </span>
            </div>
        </div>
        <div style="margin-left:auto;display:flex;gap:0.75rem;">
            <button class="btn-primary" style="padding:4px 14px;font-size:0.75rem;" onclick="alert('Track TOR request')">Track Request</button>
        </div>
    </div>

    <!-- Academic Record -->
    <div class="card fade-scroll">
        <div class="card-body">
            <div style="margin-bottom:1rem;font-weight:600;color:var(--text-1);font-size:0.95rem;">
                Academic Record
                <span style="font-weight:400;color:var(--text-3);font-size:0.75rem;margin-left:8px;">
                    Bachelor of Science in Information Technology
                </span>
            </div>
            
            <?php foreach ($tor_grades as $year => $subjects): ?>
                <div class="tor-year-section">
                    <div class="tor-year-title"><?php echo htmlspecialchars($year); ?></div>
                    <div style="display:grid;grid-template-columns:100px 1fr 60px 60px;gap:0.75rem;padding:0.25rem 0 0.35rem;font-weight:600;font-size:0.65rem;color:var(--text-3);border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:0.05em;">
                        <span>Code</span>
                        <span>Subject</span>
                        <span style="text-align:center;">Units</span>
                        <span style="text-align:center;">Grade</span>
                    </div>
                    <?php foreach ($subjects as $subject): ?>
                        <div class="tor-item">
                            <span class="code"><?php echo htmlspecialchars($subject['code']); ?></span>
                            <span class="name"><?php echo htmlspecialchars($subject['name']); ?></span>
                            <span class="units"><?php echo $subject['units']; ?></span>
                            <span class="grade"><?php echo htmlspecialchars($subject['grade']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <div style="display:flex;justify-content:flex-end;padding:0.75rem 0 0;font-size:0.85rem;font-weight:600;color:var(--text-1);border-top:2px solid var(--border);margin-top:0.5rem;">
                Total Units: <?php echo $total_units; ?> | GWA: <?php echo number_format($gwa, 2); ?>
            </div>
        </div>
    </div>

    <!-- Request History -->
    <div style="margin-top:1.5rem;">
        <div class="section-header">
            <h2 class="section-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Request History
            </h2>
        </div>
        <div class="card fade-scroll">
            <div class="card-body">
                <?php if (empty($tor_requests)): ?>
                    <div style="padding:1rem 0;text-align:center;color:var(--text-3);">
                        No TOR requests found.
                    </div>
                <?php else: ?>
                    <?php foreach ($tor_requests as $req): ?>
                        <div class="tor-request-item">
                            <div class="req-info">
                                <span style="font-weight:500;color:var(--text-1);">TOR Request</span>
                                <span class="req-date">Requested: <?php echo date('M d, Y', strtotime($req['request_date'] ?? 'now')); ?></span>
                                <span class="req-purpose">Purpose: <?php echo htmlspecialchars($req['purpose'] ?? 'Not specified'); ?></span>
                            </div>
                            <span class="req-status <?php echo htmlspecialchars($req['status_class'] ?? 'status-pending'); ?>" style="background:var(--surface-3);color:var(--text-2);padding:4px 12px;">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<footer style="border-top:1px solid var(--border);padding:1.25rem;text-align:center;transition:border-color 0.3s ease;margin-top:1.5rem;">
    <p style="font-size:.75rem;color:var(--text-3);transition:color 0.3s ease;">
        &copy; 2026 University of Makati · Registrar's Office · All rights reserved.
    </p>
</footer>

<button class="fab" onclick="openOffcanvas()" aria-label="Open profile panel">
    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
    </svg>
</button>

<script>
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
}

const savedDarkMode = localStorage.getItem('darkMode');
if (savedDarkMode === 'true') {
    document.body.classList.add('dark-mode');
}

function toggleDarkModeFromSideNav() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
}

function openSideNav() {
    document.getElementById('sideNav').classList.add('open');
    document.getElementById('sideNavOverlay').classList.add('open');
    closeDropdown();
}
function closeSideNav() {
    document.getElementById('sideNav').classList.remove('open');
    document.getElementById('sideNavOverlay').classList.remove('open');
}

function toggleDropdown() {
    document.getElementById('profileDropdown').classList.toggle('open');
}
function closeDropdown() {
    document.getElementById('profileDropdown').classList.remove('open');
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('profileWrap').contains(e.target)) closeDropdown();
});

function openOffcanvas() {
    document.getElementById('offcanvasPanel').classList.add('open');
    document.getElementById('offcanvasOverlay').classList.add('open');
    closeDropdown();
}
function closeOffcanvas() {
    document.getElementById('offcanvasPanel').classList.remove('open');
    document.getElementById('offcanvasOverlay').classList.remove('open');
}

function doSignOut() {
    if (confirm('Are you sure you want to sign out?')) {
        window.location.href = '../includes/logout.php';
    }
}

var fadeObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.15, rootMargin: "0px 0px -30px 0px" });

document.querySelectorAll('.fade-scroll').forEach(function(el) { fadeObserver.observe(el); });
</script>
</body>
</html>