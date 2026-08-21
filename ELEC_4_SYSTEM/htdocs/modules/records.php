<?php
    // ============================================
    // ENABLE ERROR REPORTING FOR DEBUGGING
    // ============================================
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // ============================================
    // START SESSION - MUST BE FIRST! (BEFORE ANY OUTPUT)
    // ============================================
    session_start();

    // ============================================
    // FAKE LOGIN SESSION - FOR TESTING ONLY
    // ============================================
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['user_id'] = 1;
        $_SESSION['full_name'] = 'Juan Dela Cruz';
        $_SESSION['email'] = 'test@umak.edu.ph';
        $_SESSION['role'] = 'admin';
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
            die('Database connection file not found. Please check your folder structure.');
        }
    }

    // ============================================
    // CHECK IF USER IS LOGGED IN
    // ============================================
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: Front_Page.php');
        exit();
    }

    // ============================================
    // GET USER DATA FROM SESSION
    // ============================================
    $user_id = $_SESSION['user_id'] ?? 1;
    $user_fullname = $_SESSION['full_name'] ?? 'Juan Dela Cruz';
    $user_email = $_SESSION['email'] ?? 'juan.delacruz@umak.edu.ph';
    $user_role = $_SESSION['role'] ?? 'admin';

    // Get initials for avatar
    $nameParts = explode(' ', $user_fullname);
    $user_initials = '';
    if (count($nameParts) >= 2) {
        $user_initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $user_initials = strtoupper(substr($user_fullname, 0, 2));
    }

    // ============================================
    // GET ADDITIONAL USER DATA FROM DATABASE
    // ============================================
    try {
        if (function_exists('getDBConnection')) {
            $pdo = getDBConnection();
            
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $userData = $stmt->fetch();
                
                if ($userData) {
                    $user_fullname = $userData['full_name'] ?? $user_fullname;
                    $user_email = $userData['email'] ?? $user_email;
                    $user_role = $userData['role'] ?? 'admin';
                }
            }
        }
        
        $user_college = $_SESSION['college'] ?? 'College of Information Technology';
        $user_course = $_SESSION['course'] ?? 'BS Information Technology';
        $user_year_level = $_SESSION['year_level'] ?? '3rd Year';
        $user_student_id = $_SESSION['student_id'] ?? '2026-' . str_pad($user_id, 5, '0', STR_PAD_LEFT);
        
        // ============================================
        // GET STUDENT RECORDS FROM DATABASE
        // ============================================
        $students = [];
        $searchQuery = '';
        $filterCollege = '';
        $filterYear = '';
        $filterStatus = '';
        
        // Get search/filter parameters
        if (isset($_GET['search'])) {
            $searchQuery = trim($_GET['search']);
        }
        if (isset($_GET['college'])) {
            $filterCollege = trim($_GET['college']);
        }
        if (isset($_GET['year'])) {
            $filterYear = trim($_GET['year']);
        }
        if (isset($_GET['status'])) {
            $filterStatus = trim($_GET['status']);
        }
        
        // Build query
        $sql = "SELECT id, student_id, full_name, email, college, course, year_level, 
                       enrollment_status, created_at, updated_at 
                FROM users 
                WHERE role = 'student'";
        $params = [];
        
        if (!empty($searchQuery)) {
            $sql .= " AND (student_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
            $searchParam = '%' . $searchQuery . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($filterCollege)) {
            $sql .= " AND college = ?";
            $params[] = $filterCollege;
        }
        
        if (!empty($filterYear)) {
            $sql .= " AND year_level = ?";
            $params[] = $filterYear;
        }
        
        if (!empty($filterStatus)) {
            $sql .= " AND enrollment_status = ?";
            $params[] = $filterStatus;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
        
        // ============================================
        // GET FILTER OPTIONS FROM DATABASE - FIXED
        // ============================================
        $colleges = [];
        $stmt = $pdo->query("SELECT DISTINCT college FROM users WHERE role = 'student' AND college IS NOT NULL AND college != '' ORDER BY college");
        // Fixed: Properly fetch data
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $colleges[] = $row['college'];
        }
        
        // If no colleges found, provide default options
        if (empty($colleges)) {
            $colleges = ['College of Information Technology', 'College of Engineering', 'College of Business', 'College of Arts and Sciences'];
        }
        
        $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        $statuses = ['Active', 'Inactive', 'Graduated', 'Transferred'];
        
    } catch (PDOException $e) {
        error_log("Database error in records.php: " . $e->getMessage());
        $students = [];
        // Provide fallback values if database query fails
        $colleges = ['College of Information Technology', 'College of Engineering', 'College of Business', 'College of Arts and Sciences'];
        $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        $statuses = ['Active', 'Inactive', 'Graduated', 'Transferred'];
    }

    // Update session
    $_SESSION['full_name'] = $user_fullname;
    $_SESSION['email'] = $user_email;
    $_SESSION['role'] = $user_role;
    $_SESSION['college'] = $user_college;
    $_SESSION['course'] = $user_course;
    $_SESSION['year_level'] = $user_year_level;
    $_SESSION['student_id'] = $user_student_id;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>UMak · Student Records</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <!-- Google Fonts: Noto Sans Osage -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Osage&display=swap" rel="stylesheet">
    <style>
        /* ========== VISUAL DESIGN ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

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

        body.dark-mode h1,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6,
        body.dark-mode p,
        body.dark-mode span,
        body.dark-mode div,
        body.dark-mode a,
        body.dark-mode label,
        body.dark-mode .card-title,
        body.dark-mode .section-title,
        body.dark-mode .dl-label,
        body.dark-mode .dl-sub,
        body.dark-mode .act-text,
        body.dark-mode .act-time,
        body.dark-mode .ann-title,
        body.dark-mode .ann-body,
        body.dark-mode .ann-by,
        body.dark-mode .ann-time,
        body.dark-mode .link-sm,
        body.dark-mode .offcanvas-header h3,
        body.dark-mode .offcanvas-section-title,
        body.dark-mode .dd-name,
        body.dark-mode .dd-email,
        body.dark-mode .dd-item,
        body.dark-mode .dd-label,
        body.dark-mode .status-row span,
        body.dark-mode .modal-title,
        body.dark-mode .brand-sub,
        body.dark-mode footer p,
        body.dark-mode .service-name,
        body.dark-mode .service-desc {
            color: #e6edf3 !important;
        }

        body.dark-mode .brand-name {
            color: #ffffff !important;
        }

        body.dark-mode .text-1,
        body.dark-mode .text-2,
        body.dark-mode .text-3 {
            color: #e6edf3 !important;
        }

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

        body.dark-mode .side-nav-brand {
            color: #ffffff !important;
        }

        body.dark-mode .side-nav-link {
            color: #b0b0c8 !important;
        }

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

        body.dark-mode .dd-item:hover {
            background: #3a3a55;
        }

        body.dark-mode .status-row:hover {
            background: #3a3a55;
        }

        body.dark-mode .modal {
            background: #0d1117;
            border-color: rgba(255,215,0,0.15);
        }

        body.dark-mode .modal-close {
            background: #3a3a55;
            color: #b0b0c8;
        }

        body.dark-mode .modal-close:hover {
            background: #4a4a6a;
            color: #e6edf3;
        }

        body.dark-mode .btn-primary {
            background: #1a4d7a;
            color: #ffffff !important;
        }

        body.dark-mode .btn-primary:hover {
            background: #2a6a9a;
        }

        body.dark-mode .fab {
            background: #1a4d7a;
            border-color: #ffd700;
        }

        body.dark-mode .fab:hover {
            background: #2a6a9a;
        }

        body.dark-mode .nav-overlay {
            background: rgba(0,0,0,0.6);
        }

        body.dark-mode .offcanvas-overlay {
            background: rgba(0,0,0,0.6);
        }

        body.dark-mode .modal-backdrop {
            background: rgba(0,0,0,0.6);
        }

        body.dark-mode footer {
            border-top-color: rgba(255,255,255,0.08) !important;
        }

        body.dark-mode .avatar-btn {
            background: rgba(255,215,0,0.08);
            border-color: rgba(255,215,0,0.12);
        }

        body.dark-mode .avatar-btn:hover {
            background: rgba(255,215,0,0.15);
        }

        body.dark-mode .avatar-circle {
            background: #ffd700;
            color: #0d1117 !important;
        }

        body.dark-mode .avatar-name {
            color: #e6edf3 !important;
        }

        body.dark-mode .icon-btn {
            color: rgba(255,255,255,0.6);
        }

        body.dark-mode .icon-btn:hover {
            color: #fff;
            background: rgba(255,215,0,0.12);
        }

        body.dark-mode .hamburger-btn {
            color: rgba(255,255,255,0.8);
        }

        body.dark-mode .hamburger-btn:hover {
            background: rgba(255,215,0,0.12);
        }

        body.dark-mode .hero-greeting h1 {
            color: #ffffff !important;
        }

        body.dark-mode .hero-greeting .sub {
            color: rgba(255,215,0,0.8) !important;
        }

        body.dark-mode .hero-greeting .desc {
            color: rgba(255,255,255,0.6) !important;
        }

        body.dark-mode .id-pill {
            background: rgba(255,215,0,0.12);
            border-color: rgba(255,215,0,0.15);
            color: rgba(255,215,0,0.8) !important;
        }

        body.dark-mode .college-pill {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.7) !important;
        }

        body.dark-mode .hero-overlay {
            background: linear-gradient(105deg, rgba(13,27,42,0.95) 0%, rgba(13,27,42,0.8) 55%, rgba(13,27,42,0.6) 100%);
            border-color: rgba(255,215,0,0.15);
        }

        body.dark-mode .record-status.active {
            background: rgba(5,150,105,0.2);
            color: #34d399 !important;
        }

        body.dark-mode .record-status.inactive {
            background: rgba(220,38,38,0.2);
            color: #ef4444 !important;
        }

        body.dark-mode .record-status.graduated {
            background: rgba(255,215,0,0.15);
            color: #ffd700 !important;
        }

        body.dark-mode .record-status.transferred {
            background: rgba(59,130,246,0.2);
            color: #60a5fa !important;
        }

        body.dark-mode .ann-icon {
            background: #3a3a55;
            color: #b0b0c8 !important;
        }

        body.dark-mode .service-card {
            background: #161b22;
            border-color: rgba(255,255,255,0.08);
        }

        body.dark-mode .service-card:hover {
            border-color: #ffd700;
        }

        body.dark-mode .service-name {
            color: #ffd700 !important;
        }

        body.dark-mode .service-desc {
            color: #b0b0c8 !important;
        }

        body.dark-mode .card-body a[style*="background:var(--surface-2)"] {
            background: #3a3a55 !important;
            color: #b0b0c8 !important;
        }

        body.dark-mode .card-body a[style*="background:var(--surface-2)"]:hover {
            background: rgba(255,215,0,0.15) !important;
        }

        body.dark-mode .offcanvas-section div[style*="background:var(--surface-2)"] {
            background: #3a3a55 !important;
        }

        body.dark-mode .offcanvas-section div[style*="background:var(--surface-2)"] div {
            color: #e6edf3 !important;
        }

        body.dark-mode .offcanvas-section div[style*="background:var(--surface-2)"] div:last-child {
            color: #8a8aaa !important;
        }

        body.dark-mode .notif-dot {
            border-color: #161b22;
        }

        body.dark-mode .dd-item.danger {
            color: #ef4444 !important;
        }

        body.dark-mode .dd-item.danger:hover {
            background: rgba(220,38,38,0.15);
        }

        body.dark-mode .side-nav-divider {
            background: rgba(255,255,255,0.08);
        }

        body.dark-mode .dd-divider {
            background: rgba(255,255,255,0.08);
        }

        body.dark-mode .service-modal {
            background: #0d1117;
            border-color: rgba(255,215,0,0.15);
        }

        body.dark-mode .service-modal-title {
            color: #e6edf3 !important;
        }

        body.dark-mode .service-modal-close {
            background: #3a3a55;
            color: #b0b0c8;
        }

        body.dark-mode .service-modal-close:hover {
            background: #4a4a6a;
            color: #e6edf3;
        }

        body.dark-mode .service-item {
            color: #b0b0c8 !important;
            border-color: transparent;
        }

        body.dark-mode .service-item:hover {
            background: #3a3a55 !important;
            border-color: rgba(255,255,255,0.08);
            color: #e6edf3 !important;
        }

        body.dark-mode .service-divider {
            background: rgba(255,255,255,0.08);
        }

        body.dark-mode .service-modal-backdrop {
            background: rgba(0,0,0,0.6);
        }

        body.dark-mode .service-item .desc {
            color: #8a8aaa !important;
        }

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
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-xl: 22px;
            --shadow-sm: 0 1px 3px rgba(0,51,102,0.06), 0 1px 2px rgba(0,51,102,0.04);
            --shadow-md: 0 4px 16px rgba(0,51,102,0.08), 0 1px 4px rgba(0,51,102,0.05);
            --shadow-lg: 0 8px 32px rgba(0,51,102,0.12);
        }

        * {
            font-family: 'Noto Sans Osage', sans-serif;
        }

        h1, h2, h3, h4, h5, h6, .section-title, .brand-name, .nav-link, .btn, .stat-value {
            font-family: 'Noto Sans Osage', sans-serif;
            letter-spacing: 0.01em;
        }

        /* ========== HEADER ========== */
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
        .brand-name { font-family: 'Noto Sans Osage', sans-serif; font-size: 1.2rem; font-weight: 700; color: #fff; letter-spacing: 0.01em; line-height: 1; }
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
            border-radius: var(--radius-sm);
            transition: background 0.15s;
        }
        .hamburger-btn:hover {
            background: rgba(255,215,0,0.12);
        }
        .hamburger-btn svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        .header-nav-links {
            display: flex;
            align-items: center;
            gap: 0.15rem;
            margin-left: auto;
            flex-wrap: nowrap;
        }

        /* ===== DROPDOWN CONTAINER ===== */
        .nav-dropdown {
            position: relative;
            display: inline-block;
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
            font-family: 'Noto Sans Osage', sans-serif;
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

        /* ===== DROPDOWN MENU ===== */
        .dropdown-popup {
            position: absolute;
            top: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%) scale(0.95) translateY(-8px);
            min-width: 200px;
            background: #ffffff;
            border-radius: 0px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.06);
            padding: 0.5rem 0;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), visibility 0.2s ease;
            z-index: 120;
        }

        .nav-dropdown:hover .dropdown-popup {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) scale(1) translateY(0);
            pointer-events: auto;
        }

        body.dark-mode .dropdown-popup {
            background: #1c2128;
            border-color: rgba(255,255,255,0.08);
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
        }

        .dropdown-popup .popup-item {
            display: block;
            padding: 0.6rem 1.2rem;
            font-size: 0.75rem;
            color: #1a2a3a;
            text-decoration: none;
            transition: background 0.15s ease;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-family: 'Noto Sans Osage', sans-serif;
            font-weight: 400;
            letter-spacing: 0.01em;
        }

        body.dark-mode .dropdown-popup .popup-item {
            color: #e6edf3;
        }

        .dropdown-popup .popup-item:hover {
            background: rgba(0,51,102,0.06);
        }

        body.dark-mode .dropdown-popup .popup-item:hover {
            background: rgba(255,215,0,0.08);
        }

        .dropdown-popup .popup-item .item-desc {
            display: block;
            font-size: 0.6rem;
            color: #8a9aaa;
            font-weight: 400;
            margin-top: 1px;
        }

        body.dark-mode .dropdown-popup .popup-item .item-desc {
            color: #8a8aaa;
        }

        .dropdown-popup .popup-divider {
            height: 1px;
            background: rgba(0,0,0,0.06);
            margin: 0.25rem 0.5rem;
        }

        body.dark-mode .dropdown-popup .popup-divider {
            background: rgba(255,255,255,0.06);
        }

        .dropdown-popup::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-bottom-color: #ffffff;
        }

        body.dark-mode .dropdown-popup::before {
            border-bottom-color: #1c2128;
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
        .side-nav.open {
            left: 0;
        }
        .side-nav-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.25rem 0.75rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        .side-nav-brand {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        .side-nav-brand span {
            color: var(--secondary-dark);
        }
        .side-nav-close {
            background: var(--surface-3);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 0px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-2);
            transition: background 0.15s;
        }
        .side-nav-close:hover {
            background: var(--surface-2);
            color: var(--text-1);
        }
        .side-nav-close svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2.5;
            fill: none;
        }
        .side-nav-body {
            padding: 0.75rem 1rem 1rem;
            flex: 1;
        }
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
        .side-nav-link:hover {
            background: var(--surface-2);
            color: var(--text-1);
        }
        .side-nav-link.active {
            background: rgba(0,51,102,0.06);
            color: var(--primary);
            font-weight: 600;
        }
        .side-nav-link svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
        }
        .side-nav-divider {
            height: 1px;
            background: var(--border);
            margin: 0.5rem 0.5rem;
        }
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
        .nav-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }
        .icon-btn {
            width: 36px; height: 36px;
            border-radius: 0px;
            border: none; background: transparent; cursor: pointer;
            color: rgba(255,255,255,0.7);
            display: flex; align-items: center; justify-content: center;
            transition: color 0.15s, background 0.15s; position: relative;
        }
        .icon-btn:hover { color: #fff; background: rgba(255,215,0,0.15); }
        .icon-btn svg { width: 18px; height: 18px; }
        .notif-dot {
            position: absolute; top: 5px; right: 5px;
            width: 7px; height: 7px;
            background: #ef4444; border-radius: 50%;
            border: 1.5px solid var(--primary);
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
            font-family: 'Noto Sans Osage', sans-serif;
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
        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.125rem 1.375rem 0;
        }
        .card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Noto Sans Osage', sans-serif;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-1);
            transition: color 0.3s ease;
        }
        .card-body { padding: 1rem 1.375rem 1.375rem; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 16px;
            border-radius: 0px;
            background: var(--primary); color: #fff;
            font-family: 'Noto Sans Osage', sans-serif;
            font-size: 0.8125rem; font-weight: 600;
            border: none; cursor: pointer; text-decoration: none;
            transition: background 0.15s;
        }
        .btn-primary:hover { background: var(--primary-light); }

        .btn-secondary {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 16px;
            border-radius: 0px;
            background: transparent;
            color: var(--text-2);
            font-family: 'Noto Sans Osage', sans-serif;
            font-size: 0.8125rem; font-weight: 500;
            border: 1px solid var(--border-md);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-secondary:hover {
            background: var(--surface-2);
            border-color: var(--text-3);
        }

        .btn-success {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 16px;
            border-radius: 0px;
            background: #16a34a;
            color: #fff;
            font-family: 'Noto Sans Osage', sans-serif;
            font-size: 0.8125rem; font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn-success:hover { background: #15803d; }

        body.dark-mode .btn-secondary {
            color: #b0b0c8;
            border-color: rgba(255,255,255,0.12);
        }
        body.dark-mode .btn-secondary:hover {
            background: #3a3a55;
            border-color: rgba(255,255,255,0.2);
        }

        .section-gap { margin-bottom: 1.5rem; }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .section-title {
            font-family: 'Noto Sans Osage', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-1);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        /* ========== SEARCH & FILTER ========== */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 0.75rem 0 1rem;
            align-items: center;
        }
        .filter-bar .search-input {
            flex: 1;
            min-width: 200px;
            padding: 8px 14px;
            border: 1px solid var(--border-md);
            border-radius: 0px;
            font-size: 0.8125rem;
            font-family: 'Noto Sans Osage', sans-serif;
            background: var(--surface);
            color: var(--text-1);
            transition: border-color 0.15s, background 0.15s, color 0.15s;
            max-width: 300px;
        }
        .filter-bar .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,51,102,0.08);
        }
        body.dark-mode .filter-bar .search-input {
            background: #1c2128;
            color: #e6edf3;
            border-color: rgba(255,255,255,0.12);
        }
        body.dark-mode .filter-bar .search-input:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255,215,0,0.12);
        }

        .filter-bar select {
            padding: 8px 12px;
            border: 1px solid var(--border-md);
            border-radius: 0px;
            font-size: 0.8125rem;
            font-family: 'Noto Sans Osage', sans-serif;
            background: var(--surface);
            color: var(--text-1);
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s, color 0.15s;
            min-width: 140px;
        }
        .filter-bar select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,51,102,0.08);
        }
        body.dark-mode .filter-bar select {
            background: #1c2128;
            color: #e6edf3;
            border-color: rgba(255,255,255,0.12);
        }
        body.dark-mode .filter-bar select:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255,215,0,0.12);
        }

        .filter-bar .filter-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* ========== TABLE ========== */
        .table-container {
            overflow-x: auto;
            margin-top: 0.5rem;
        }
        .records-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }
        .records-table th {
            text-align: left;
            padding: 0.75rem 0.75rem;
            background: var(--surface-2);
            color: var(--text-2);
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border);
            transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        .records-table td {
            padding: 0.75rem 0.75rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-1);
            transition: border-color 0.3s ease, color 0.3s ease;
            vertical-align: middle;
        }
        .records-table tr:last-child td {
            border-bottom: none;
        }
        .records-table tr:hover td {
            background: var(--surface-2);
            transition: background 0.15s;
        }
        body.dark-mode .records-table th {
            background: #1c2128;
            color: #8a8aaa;
            border-bottom-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .records-table td {
            color: #e6edf3;
            border-bottom-color: rgba(255,255,255,0.06);
        }
        body.dark-mode .records-table tr:hover td {
            background: #1c2128;
        }

        .record-status {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 0px;
            font-size: 0.65rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .record-status.active {
            background: #dcfce7;
            color: #166534;
        }
        .record-status.inactive {
            background: #fecaca;
            color: #991b1b;
        }
        .record-status.graduated {
            background: #fef3c7;
            color: #92400e;
        }
        .record-status.transferred {
            background: #dbeafe;
            color: #1e40af;
        }
        body.dark-mode .record-status.active {
            background: rgba(5,150,105,0.2);
            color: #34d399;
        }
        body.dark-mode .record-status.inactive {
            background: rgba(220,38,38,0.2);
            color: #ef4444;
        }
        body.dark-mode .record-status.graduated {
            background: rgba(255,215,0,0.15);
            color: #ffd700;
        }
        body.dark-mode .record-status.transferred {
            background: rgba(59,130,246,0.2);
            color: #60a5fa;
        }

        .table-actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
        .table-actions .action-btn {
            padding: 3px 10px;
            font-size: 0.65rem;
            font-weight: 500;
            border: 1px solid var(--border-md);
            border-radius: 0px;
            background: transparent;
            color: var(--text-2);
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'Noto Sans Osage', sans-serif;
        }
        .table-actions .action-btn:hover {
            background: var(--surface-2);
            border-color: var(--text-3);
        }
        .table-actions .action-btn.view-btn {
            color: var(--primary);
            border-color: var(--primary);
        }
        .table-actions .action-btn.view-btn:hover {
            background: rgba(0,51,102,0.06);
        }
        .table-actions .action-btn.edit-btn {
            color: #1a4d7a;
            border-color: #1a4d7a;
        }
        .table-actions .action-btn.edit-btn:hover {
            background: rgba(26,77,122,0.06);
        }
        body.dark-mode .table-actions .action-btn {
            color: #b0b0c8;
            border-color: rgba(255,255,255,0.12);
        }
        body.dark-mode .table-actions .action-btn:hover {
            background: #3a3a55;
        }
        body.dark-mode .table-actions .action-btn.view-btn {
            color: #ffd700;
            border-color: rgba(255,215,0,0.3);
        }
        body.dark-mode .table-actions .action-btn.view-btn:hover {
            background: rgba(255,215,0,0.08);
        }
        body.dark-mode .table-actions .action-btn.edit-btn {
            color: #7aa9d9;
            border-color: rgba(122,169,217,0.3);
        }
        body.dark-mode .table-actions .action-btn.edit-btn:hover {
            background: rgba(122,169,217,0.08);
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--text-3);
        }
        .empty-state .empty-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        .empty-state .empty-text {
            font-size: 0.95rem;
            font-weight: 500;
        }
        .empty-state .empty-desc {
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0px;
            padding: 1rem 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-3);
            font-weight: 600;
        }
        .stat-card .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-1);
            margin-top: 2px;
            transition: color 0.3s ease;
        }
        .stat-card .stat-sub {
            font-size: 0.65rem;
            color: var(--text-3);
            margin-top: 2px;
        }
        body.dark-mode .stat-card {
            background: #161b22;
            border-color: rgba(255,255,255,0.08);
        }
        body.dark-mode .stat-card .stat-number {
            color: #e6edf3;
        }

        /* ===== SERVICE MODAL ===== */
        .service-modal-backdrop {
            position: fixed; inset: 0; background: rgba(10,20,40,0.4);
            z-index: 500; display: none; align-items: center; justify-content: center;
            backdrop-filter: blur(3px);
            transition: background 0.3s ease;
        }
        .service-modal-backdrop.open { display: flex; }
        .service-modal {
            background: var(--surface);
            border-radius: 0px;
            box-shadow: var(--shadow-lg); width: 100%; max-width: 420px; max-height: 85vh; overflow: auto;
            animation: modalIn 0.2s ease;
            border: 2px solid rgba(255,215,0,0.2);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        @keyframes modalIn { from { opacity:0; transform: scale(0.94) translateY(-12px); } to { opacity:1; transform: scale(1) translateY(0); } }
        .service-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem 0.5rem; }
        .service-modal-title { font-family: 'Noto Sans Osage', sans-serif; font-size: 1rem; font-weight: 700; color: var(--text-1); transition: color 0.3s ease; }
        .service-modal-close { width: 30px; height: 30px; border-radius: 0px; border: none; background: var(--surface-3); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-2); transition: background 0.15s, color 0.3s ease; }
        .service-modal-close:hover { background: var(--surface-2); color: var(--text-1); }
        .service-modal-close svg { width: 15px; height: 15px; }
        .service-modal-body { padding: 0.5rem 1.5rem 1.5rem; }
        .service-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px;
            border-radius: 0px;
            cursor: pointer; transition: background 0.15s, color 0.15s;
            color: var(--text-2); font-size: 0.85rem;
            border: 1px solid transparent;
            margin-bottom: 4px;
            font-family: 'Noto Sans Osage', sans-serif;
        }
        .service-item:hover { background: var(--surface-2); border-color: var(--border); color: var(--text-1); }
        .service-item .label { font-weight: 500; }
        .service-item .desc { font-size: 0.7rem; color: var(--text-3); margin-left: auto; }
        .service-divider { height: 1px; background: var(--border); margin: 6px 0; }

        /* ===== MODAL BACKDROP ===== */
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(10,20,40,0.4);
            z-index: 400; display: none; align-items: center; justify-content: center;
            transition: background 0.3s ease;
        }
        .modal-backdrop.open { display: flex; }
        .modal {
            background: var(--surface);
            border-radius: 0px;
            box-shadow: var(--shadow-lg); width: 100%; max-width: 500px; max-height: 90vh; overflow: auto;
            animation: modalIn 0.15s ease;
            border: 2px solid rgba(255,215,0,0.2);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        @keyframes modalIn { from { opacity:0; transform: scale(0.96) translateY(-8px); } to { opacity:1; transform: scale(1) translateY(0); } }
        .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem 0; }
        .modal-title { font-family: 'Noto Sans Osage', sans-serif; font-size: 1rem; font-weight: 600; color: var(--text-1); transition: color 0.3s ease; }
        .modal-close { width: 30px; height: 30px; border-radius: 0px; border: none; background: var(--surface-3); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-2); transition: background 0.15s, color 0.3s ease; }
        .modal-close:hover { background: var(--surface-2); color: var(--text-1); }
        .modal-close svg { width: 15px; height: 15px; }
        .modal-body { padding: 1rem 1.5rem 1.5rem; }

        /* ===== DROPDOWN MENU ===== */
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
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .dropdown-menu.open { display: block; animation: dropIn 0.14s ease; }
        @keyframes dropIn { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform: translateY(0); } }
        .dd-header { padding: 14px 16px 12px; border-bottom: 1px solid var(--border); transition: border-color 0.3s ease; }
        .dd-name { font-size: 0.875rem; font-weight: 600; color: var(--text-1); transition: color 0.3s ease; }
        .dd-email { font-size: 0.75rem; color: var(--text-3); margin-top: 2px; transition: color 0.3s ease; }
        .dd-section { padding: 8px 8px; }
        .dd-label { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-3); padding: 4px 8px 6px; transition: color 0.3s ease; }
        .dd-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px;
            border-radius: 0px;
            font-size: 0.8125rem; color: var(--text-2); cursor: pointer;
            transition: background 0.12s, color 0.3s ease;
        }
        .dd-item:hover { background: var(--surface-2); }
        .dd-item.danger { color: #dc2626; }
        .dd-item.danger:hover { background: #fef2f2; }
        .dd-divider { height: 1px; background: var(--border); margin: 4px 8px; transition: background 0.3s ease; }

        /* ===== OFF-CANVAS ===== */
        .offcanvas {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.92);
            width: 520px;
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
            transition: background 0.3s ease, border-color 0.3s ease;
            flex-shrink: 0;
        }
        .offcanvas-header h3 {
            font-family: 'Noto Sans Osage', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-1);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
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
            transition: background 0.15s, color 0.3s ease;
        }
        .offcanvas-close:hover {
            background: var(--surface-2);
            color: var(--text-1);
        }
        .offcanvas-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 1.5rem;
        }
        .offcanvas-section {
            margin-bottom: 1.5rem;
        }
        .offcanvas-section:last-child {
            margin-bottom: 0;
        }
        .offcanvas-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-3);
            margin-bottom: 0.75rem;
            transition: color 0.3s ease;
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
        .offcanvas-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        /* ===== STUDENT DETAIL IN OFF-CANVAS ===== */
        .student-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1rem;
            font-size: 0.85rem;
        }
        .student-detail-grid .detail-label {
            color: var(--text-3);
            font-weight: 400;
        }
        .student-detail-grid .detail-value {
            color: var(--text-1);
            font-weight: 500;
            word-break: break-word;
        }
        .student-detail-grid .detail-full {
            grid-column: 1 / -1;
        }
        body.dark-mode .student-detail-grid .detail-value {
            color: #e6edf3;
        }
        body.dark-mode .student-detail-grid .detail-label {
            color: #8a8aaa;
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
            transition: all 0.2s ease, background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            z-index: 150;
        }
        .fab:hover {
            transform: scale(1.05);
            background: var(--primary-light);
            box-shadow: 0 8px 24px rgba(0,51,102,0.35);
        }
        .fab svg {
            width: 26px;
            height: 26px;
            stroke: #fff;
            stroke-width: 2;
            fill: none;
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

        /* ===== DARK MODE TOGGLE IN SIDE NAV ===== */
        .side-nav-dark-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 0.9rem;
            border-radius: 0px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-2);
            transition: background 0.12s, color 0.12s;
            margin-bottom: 2px;
            cursor: pointer;
        }
        .side-nav-dark-toggle:hover {
            background: var(--surface-2);
            color: var(--text-1);
        }
        .side-nav-dark-toggle .toggle-track {
            width: 40px;
            height: 22px;
            background: #ccc;
            border-radius: 12px;
            position: relative;
            transition: background 0.3s ease;
            flex-shrink: 0;
        }
        .side-nav-dark-toggle .toggle-track .toggle-thumb {
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            left: 2px;
            transition: transform 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        body.dark-mode .side-nav-dark-toggle .toggle-track {
            background: #ffd700;
        }
        body.dark-mode .side-nav-dark-toggle .toggle-track .toggle-thumb {
            transform: translateX(18px);
            background: #0d1117;
        }
        body.dark-mode .side-nav-dark-toggle {
            color: #e6edf3;
        }
        body.dark-mode .side-nav-dark-toggle:hover {
            background: #3a3a55;
        }

        @media(max-width:1024px){
            .stats-grid { grid-template-columns: repeat(2,1fr); }
        }
        @media(max-width:768px){
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .offcanvas { width: 85vw; max-height: 85vh; }
            .header-nav-links { gap: 0.05rem; }
            .header-nav-link { font-size: 0.6rem; padding: 0.25rem 0.4rem; }
            .dropdown-popup { min-width: 160px; }
            .dropdown-popup .popup-item { font-size: 0.65rem; padding: 0.4rem 0.8rem; }
            .student-detail-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .student-detail-grid .detail-full {
                grid-column: 1;
            }
            .filter-bar .search-input {
                max-width: 100%;
                min-width: 150px;
            }
            .filter-bar select {
                min-width: 120px;
            }
        }
        @media(max-width:480px){
            .stats-grid { grid-template-columns: 1fr; }
            .header-inner { padding: 0 0.5rem; }
            .page { padding: 1rem; }
            .header-nav-links { display: none !important; }
            .offcanvas { width: 92vw; max-height: 90vh; }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar .search-input {
                max-width: 100%;
            }
            .filter-bar select {
                width: 100%;
            }
            .filter-bar .filter-actions {
                justify-content: stretch;
            }
            .filter-bar .filter-actions .btn-primary,
            .filter-bar .filter-actions .btn-secondary {
                flex: 1;
                justify-content: center;
            }
            .table-actions {
                flex-direction: column;
                gap: 0.25rem;
            }
            .table-actions .action-btn {
                text-align: center;
            }
            .records-table {
                font-size: 0.7rem;
            }
            .records-table th,
            .records-table td {
                padding: 0.4rem 0.4rem;
            }
        }

        /* ===== RECORDS PAGE SPECIFIC ===== */
        .records-count {
            font-size: 0.75rem;
            color: var(--text-3);
        }
        body.dark-mode .records-count {
            color: #8a8aaa;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            padding: 1rem 0 0.25rem;
            flex-wrap: wrap;
        }
        .pagination .page-btn {
            padding: 4px 12px;
            border: 1px solid var(--border);
            border-radius: 0px;
            background: transparent;
            color: var(--text-2);
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'Noto Sans Osage', sans-serif;
        }
        .pagination .page-btn:hover {
            background: var(--surface-2);
        }
        .pagination .page-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .pagination .page-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        body.dark-mode .pagination .page-btn {
            border-color: rgba(255,255,255,0.08);
            color: #b0b0c8;
        }
        body.dark-mode .pagination .page-btn:hover {
            background: #3a3a55;
        }
        body.dark-mode .pagination .page-btn.active {
            background: #ffd700;
            color: #0d1117;
            border-color: #ffd700;
        }
    </style>
</head>
<body>

<!-- ========== SIDE NAV OVERLAY ========== -->
<div class="nav-overlay" id="sideNavOverlay" onclick="closeSideNav()"></div>

<!-- ========== SIDE NAV ========== -->
<div class="side-nav" id="sideNav">
    <div class="side-nav-header">
        <a href="#" class="side-nav-brand">UMak<span>·Registrar</span></a>
        <button class="side-nav-close" onclick="closeSideNav()">
            <svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="side-nav-body">
        <a href="User_Dashboard.php" class="side-nav-link">
            <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
            Dashboard
        </a>
        <a href="#" class="side-nav-link" onclick="handleSideNavAction('Notifications Center')">
            <svg viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Notifications
        </a>
        <a href="#" class="side-nav-link active" onclick="handleSideNavAction('Student Records')">
            <svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Student Records
        </a>
        <a href="#" class="side-nav-link" onclick="handleSideNavAction('Reports')">
            <svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Reports
        </a>
        <a href="#" class="side-nav-link" onclick="handleSideNavAction('Help Center')">
            <svg viewBox="0 0 24 24"><path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Help Center
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
            <div class="nav-dropdown">
                <button class="header-nav-link" onclick="window.location.href='User_Dashboard.php'">Dashboard</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('dashboard', 'View Statistics')">View Statistics<span class="item-desc">Overall summary</span></button>
                    <button class="popup-item" onclick="handlePopupAction('dashboard', 'Academic Calendar')">Academic Calendar<span class="item-desc">View schedule</span></button>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="header-nav-link" onclick="window.location.href='enrollment.php'">Enrollment</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('enrollment', 'Enlist Subjects')">Enlist Subjects<span class="item-desc">Add subjects</span></button>
                    <button class="popup-item" onclick="handlePopupAction('enrollment', 'Assessment')">Assessment<span class="item-desc">View fees</span></button>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="header-nav-link" onclick="window.location.href='grades.php'">Grades</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('grades', 'Encode Grades')">Encode Grades<span class="item-desc">Input grades</span></button>
                    <button class="popup-item" onclick="handlePopupAction('grades', 'View Grades')">View Grades<span class="item-desc">Student grades</span></button>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="header-nav-link" onclick="window.location.href='tor.php'">TOR</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('tor', 'Request TOR')">Request TOR<span class="item-desc">New request</span></button>
                    <button class="popup-item" onclick="handlePopupAction('tor', 'Processing')">Processing<span class="item-desc">In-progress</span></button>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="header-nav-link" onclick="window.location.href='certificates.php'">Certificates</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('certificates', 'Certificate of Enrollment')">COE<span class="item-desc">Enrollment</span></button>
                    <button class="popup-item" onclick="handlePopupAction('certificates', 'Good Moral')">Good Moral<span class="item-desc">Character</span></button>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="header-nav-link active" onclick="window.location.href='records.php'">Records</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('records', 'Search Student')">Search Student<span class="item-desc">Find student</span></button>
                    <button class="popup-item" onclick="handlePopupAction('records', 'Masterlist')">Masterlist<span class="item-desc">All students</span></button>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="header-nav-link" onclick="window.location.href='reports.php'">Reports</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('reports', 'By College')">By College<span class="item-desc">Per college</span></button>
                    <button class="popup-item" onclick="handlePopupAction('reports', 'By Course')">By Course<span class="item-desc">Per course</span></button>
                </div>
            </div>
            <div class="nav-dropdown">
                <button class="header-nav-link" onclick="window.location.href='clearance.php'">Clearance</button>
                <div class="dropdown-popup">
                    <button class="popup-item" onclick="handlePopupAction('clearance', 'Graduation Clearance')">Graduation<span class="item-desc">For graduation</span></button>
                    <button class="popup-item" onclick="handlePopupAction('clearance', 'Exit Clearance')">Exit<span class="item-desc">For leaving</span></button>
                </div>
            </div>
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
                        <div class="dd-item" onclick="openProfileModal()">Profile Settings</div>
                        <div class="dd-divider"></div>
                        <div class="dd-item danger" onclick="doSignOut()">Sign Out</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ========== SERVICE MODAL ========== -->
<div class="service-modal-backdrop" id="serviceModalBackdrop" onclick="if(event.target===this) closeServiceModal()">
    <div class="service-modal" id="serviceModalBox">
        <div class="service-modal-head">
            <span class="service-modal-title" id="serviceModalTitle">Records Services</span>
            <button class="service-modal-close" onclick="closeServiceModal()">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="service-modal-body" id="serviceModalBody"></div>
    </div>
</div>

<!-- ========== PROFILE SETTINGS MODAL ========== -->
<div class="modal-backdrop profile-modal" id="profileModalBackdrop" onclick="if(event.target===this) closeProfileModal()">
    <div class="modal" id="profileModalBox">
        <div class="modal-head">
            <span class="modal-title">Profile Settings</span>
            <button class="modal-close" onclick="closeProfileModal()" aria-label="Close profile settings">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="profile-avatar-section" style="display:flex;align-items:center;gap:1.25rem;padding:0.75rem 0 0.5rem;border-bottom:1px solid var(--border);margin-bottom:1.25rem;">
                <div class="profile-avatar" id="profileAvatar" style="width:56px;height:56px;border-radius:50%;background:var(--secondary);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;color:var(--primary);flex-shrink:0;"><?php echo $user_initials; ?></div>
                <div class="profile-avatar-actions" style="display:flex;flex-direction:column;gap:2px;">
                    <button class="change-photo-btn" id="changePhotoBtn" aria-label="Change photo" style="background:transparent;border:1px solid var(--border-md);border-radius:var(--radius-sm);padding:4px 12px;font-size:0.75rem;font-weight:500;color:var(--text-2);cursor:pointer;transition:all 0.15s;display:inline-flex;align-items:center;gap:6px;width:fit-content;font-family:'Noto Sans Osage',sans-serif;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Change Photo
                    </button>
                    <input type="file" id="photoInput" accept="image/jpeg,image/png" style="display:none">
                    <span class="helper-text" style="font-size:0.65rem;color:var(--text-3);">JPG or PNG, max 2MB</span>
                </div>
            </div>
            <form id="profileForm" novalidate>
                <div class="profile-form-group" style="display:flex;flex-direction:column;gap:4px;margin-bottom:14px;">
                    <label for="fullName" style="font-size:0.75rem;font-weight:600;color:var(--text-2);letter-spacing:0.02em;">Full Name</label>
                    <input type="text" id="fullName" value="<?php echo htmlspecialchars($user_fullname); ?>" required style="padding:8px 12px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-size:0.875rem;font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);transition:border-color 0.15s,background 0.15s,color 0.15s;width:100%;">
                    <div class="profile-error" id="fullNameError" style="color:#dc2626;font-size:0.7rem;margin-top:2px;display:none;">Full name is required.</div>
                </div>
                <div class="profile-form-group" style="display:flex;flex-direction:column;gap:4px;margin-bottom:14px;">
                    <label for="email" style="font-size:0.75rem;font-weight:600;color:var(--text-2);letter-spacing:0.02em;">Email</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled style="padding:8px 12px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-size:0.875rem;font-family:'Noto Sans Osage',sans-serif;background:var(--surface-2);color:var(--text-3);transition:border-color 0.15s,background 0.15s,color 0.15s;width:100%;cursor:not-allowed;">
                </div>
                <div class="profile-form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="profile-form-group" style="display:flex;flex-direction:column;gap:4px;margin-bottom:14px;">
                        <label for="studentId" style="font-size:0.75rem;font-weight:600;color:var(--text-2);letter-spacing:0.02em;">Student ID</label>
                        <input type="text" id="studentId" value="<?php echo htmlspecialchars($user_student_id ?? '2026-12345'); ?>" disabled style="padding:8px 12px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-size:0.875rem;font-family:'Noto Sans Osage',sans-serif;background:var(--surface-2);color:var(--text-3);transition:border-color 0.15s,background 0.15s,color 0.15s;width:100%;cursor:not-allowed;">
                    </div>
                    <div class="profile-form-group" style="display:flex;flex-direction:column;gap:4px;margin-bottom:14px;">
                        <label for="yearLevel" style="font-size:0.75rem;font-weight:600;color:var(--text-2);letter-spacing:0.02em;">Year Level</label>
                        <select id="yearLevel" style="padding:8px 12px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-size:0.875rem;font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);transition:border-color 0.15s,background 0.15s,color 0.15s;width:100%;">
                            <option value="1st" <?php echo ($user_year_level ?? '') == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2nd" <?php echo ($user_year_level ?? '') == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3rd" <?php echo ($user_year_level ?? '') == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4th" <?php echo ($user_year_level ?? '') == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                </div>
                <div class="profile-form-group" style="display:flex;flex-direction:column;gap:4px;margin-bottom:14px;">
                    <label for="college" style="font-size:0.75rem;font-weight:600;color:var(--text-2);letter-spacing:0.02em;">College</label>
                    <input type="text" id="college" value="<?php echo htmlspecialchars($user_college ?? 'College of Information Technology'); ?>" disabled style="padding:8px 12px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-size:0.875rem;font-family:'Noto Sans Osage',sans-serif;background:var(--surface-2);color:var(--text-3);transition:border-color 0.15s,background 0.15s,color 0.15s;width:100%;cursor:not-allowed;">
                </div>
                <div class="profile-form-group" style="display:flex;flex-direction:column;gap:4px;margin-bottom:14px;">
                    <label for="course" style="font-size:0.75rem;font-weight:600;color:var(--text-2);letter-spacing:0.02em;">Course</label>
                    <input type="text" id="course" value="<?php echo htmlspecialchars($user_course ?? 'BS Information Technology'); ?>" disabled style="padding:8px 12px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-size:0.875rem;font-family:'Noto Sans Osage',sans-serif;background:var(--surface-2);color:var(--text-3);transition:border-color 0.15s,background 0.15s,color 0.15s;width:100%;cursor:not-allowed;">
                </div>
                <div class="profile-success" id="profileSuccess" style="color:#16a34a;font-size:0.75rem;text-align:center;padding:6px 0;display:none;">Profile updated successfully!</div>
                <div class="profile-footer" style="border-top:1px solid var(--border);padding-top:1.25rem;margin-top:0.5rem;display:flex;justify-content:flex-end;gap:12px;">
                    <button type="button" class="btn-secondary" onclick="closeProfileModal()" style="background:transparent;border:1px solid var(--border-md);border-radius:var(--radius-sm);padding:7px 20px;font-size:0.8125rem;font-weight:500;color:var(--text-2);cursor:pointer;transition:all 0.15s;font-family:'Noto Sans Osage',sans-serif;">Cancel</button>
                    <button type="submit" class="btn-save" style="background:var(--primary);border:none;border-radius:var(--radius-sm);padding:7px 24px;font-size:0.8125rem;font-weight:600;color:#fff;cursor:pointer;transition:background 0.15s;font-family:'Noto Sans Osage',sans-serif;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== MODAL BACKDROP ========== -->
<div class="modal-backdrop" id="modalBackdrop" onclick="if(event.target===this) closeModal()">
    <div class="modal" id="modalBox">
        <div class="modal-head">
            <span class="modal-title" id="modalTitle"></span>
            <button class="modal-close" onclick="closeModal()">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<!-- ========== OFF-CANVAS OVERLAY ========== -->
<div class="offcanvas-overlay" id="offcanvasOverlay" onclick="closeOffcanvas()"></div>

<!-- ========== OFF-CANVAS PANEL - STUDENT DETAIL ========== -->
<div class="offcanvas" id="offcanvasPanel">
    <div class="offcanvas-header">
        <h3><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg> Student Details</h3>
        <button class="offcanvas-close" onclick="closeOffcanvas()">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:14px;height:14px"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="offcanvas-body" id="offcanvasBody">
        <div style="text-align:center;color:var(--text-3);padding:2rem 0;">Select a student to view details.</div>
    </div>
</div>

<!-- ========== MAIN CONTENT ========== -->
<main class="page">
    <!-- Hero Section -->
    <section class="hero-section fade-scroll" id="heroSection" style="position:relative;left:50%;right:50%;margin-left:-50vw;margin-right:-50vw;width:100vw;margin-top:-1.75rem;margin-bottom:2.25rem;padding:5rem 3rem;min-height:280px;overflow:hidden;background:var(--surface);transition:background 0.3s ease;">
        <div style="position:absolute;inset:0;background:linear-gradient(135deg, rgba(0,51,102,0.92) 0%, rgba(0,34,68,0.85) 100%);z-index:0;"></div>
        <div style="position:relative;z-index:1;max-width:900px;margin:0 auto;text-align:center;">
            <h1 style="font-size:2.5rem;font-weight:700;color:#ffffff;margin-bottom:0.5rem;letter-spacing:0.02em;text-shadow:0 4px 20px rgba(0,0,0,0.3);">
                Student Records
            </h1>
            <p style="font-size:1.1rem;color:rgba(255,255,255,0.85);max-width:640px;margin:0 auto;line-height:1.6;text-shadow:0 2px 12px rgba(0,0,0,0.3);">
                Manage and view all student records in one place. Search, filter, and access student information.
            </p>
        </div>
    </section>

    <!-- Statistics Cards -->
    <section style="margin-bottom:1.5rem;">
        <div class="stats-grid">
            <?php
                $totalStudents = count($students);
                $activeCount = 0;
                $inactiveCount = 0;
                $graduatedCount = 0;
                $transferredCount = 0;
                
                foreach ($students as $student) {
                    $status = $student['enrollment_status'] ?? 'Active';
                    switch ($status) {
                        case 'Active': $activeCount++; break;
                        case 'Inactive': $inactiveCount++; break;
                        case 'Graduated': $graduatedCount++; break;
                        case 'Transferred': $transferredCount++; break;
                        default: $activeCount++; break;
                    }
                }
            ?>
            <div class="stat-card fade-scroll">
                <div class="stat-label">Total Students</div>
                <div class="stat-number"><?php echo $totalStudents; ?></div>
                <div class="stat-sub">All registered students</div>
            </div>
            <div class="stat-card fade-scroll">
                <div class="stat-label">Active</div>
                <div class="stat-number" style="color:#166534;"><?php echo $activeCount; ?></div>
                <div class="stat-sub">Currently enrolled</div>
            </div>
            <div class="stat-card fade-scroll">
                <div class="stat-label">Inactive</div>
                <div class="stat-number" style="color:#991b1b;"><?php echo $inactiveCount; ?></div>
                <div class="stat-sub">Not currently enrolled</div>
            </div>
            <div class="stat-card fade-scroll">
                <div class="stat-label">Graduated</div>
                <div class="stat-number" style="color:#92400e;"><?php echo $graduatedCount; ?></div>
                <div class="stat-sub">Completed programs</div>
            </div>
        </div>
    </section>

    <!-- Records Table -->
    <section>
        <div class="section-header">
            <h2 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Student Masterlist
                <span class="records-count">(<?php echo $totalStudents; ?> records)</span>
            </h2>
            <button class="btn-primary" onclick="openAddStudentModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4v16m-8-8h16"/></svg>
                Add Student
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="card fade-scroll" style="margin-bottom:1.5rem;">
            <div class="card-body">
                <form method="GET" action="" class="filter-bar">
                    <input type="text" name="search" class="search-input" placeholder="Search by name, ID, or email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <select name="college">
                        <option value="">All Colleges</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?php echo htmlspecialchars($college); ?>" <?php echo $filterCollege == $college ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($college); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="year">
                        <option value="">All Years</option>
                        <?php foreach ($yearLevels as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>" <?php echo $filterYear == $year ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $filterStatus == $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary">Apply Filters</button>
                        <a href="records.php" class="btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card fade-scroll">
            <div class="card-body" style="padding:0.5rem 1.375rem 0.75rem;">
                <div class="table-container">
                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <div class="empty-text">No student records found</div>
                            <div class="empty-desc">Try adjusting your search or filter criteria.</div>
                        </div>
                    <?php else: ?>
                        <table class="records-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Full Name</th>
                                    <th>College</th>
                                    <th>Course</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['college'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                                $status = $student['enrollment_status'] ?? 'Active';
                                                $statusClass = strtolower($status);
                                            ?>
                                            <span class="record-status <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="action-btn view-btn" onclick="viewStudent(<?php echo $student['id']; ?>)">View</button>
                                                <button class="action-btn edit-btn" onclick="editStudent(<?php echo $student['id']; ?>)">Edit</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
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
// ========== DARK MODE ==========
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

// ========== SIDE NAV ==========
function openSideNav() {
    document.getElementById('sideNav').classList.add('open');
    document.getElementById('sideNavOverlay').classList.add('open');
    closeDropdown();
}
function closeSideNav() {
    document.getElementById('sideNav').classList.remove('open');
    document.getElementById('sideNavOverlay').classList.remove('open');
}

function handleSideNavAction(action) {
    if (action === 'Student Records') {
        return;
    }
    alert('📌 ' + action + '\n\nThis will open the ' + action + ' section.');
    closeSideNav();
}

// ========== DROPDOWN ==========
function toggleDropdown() {
    document.getElementById('profileDropdown').classList.toggle('open');
}
function closeDropdown() {
    document.getElementById('profileDropdown').classList.remove('open');
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('profileWrap').contains(e.target)) closeDropdown();
});

// ========== POPUP ACTIONS ==========
function handlePopupAction(category, action) {
    alert('📌 ' + category.toUpperCase() + ' → ' + action + '\n\nThis will open the ' + action + ' module.');
}

// ========== SERVICE MODAL ==========
var serviceData = {
    records: { 
        title: 'Records Services', 
        services: [
            { label: 'Search Student', desc: 'Find student by name or ID' },
            { label: 'Masterlist', desc: 'View all students' },
            { label: 'Statistics', desc: 'Student demographics' },
            { label: 'Update Status', desc: 'Active/Inactive/Graduated' },
            { label: 'Bulk Import', desc: 'Import students from CSV' },
            { label: 'Export Data', desc: 'Export to Excel/CSV' }
        ] 
    }
};

function openServiceModal(type) {
    var data = serviceData[type];
    if (!data) return;
    closeDropdown();
    var title = document.getElementById('serviceModalTitle');
    var body = document.getElementById('serviceModalBody');
    title.textContent = data.title;
    var html = '';
    data.services.forEach(function(service, index) {
        html += '<div class="service-item" onclick="handleServiceAction(\'' + type + '\', \'' + service.label + '\')"><span class="label">' + service.label + '</span><span class="desc">' + service.desc + '</span></div>';
        if (index < data.services.length - 1) html += '<div class="service-divider"></div>';
    });
    body.innerHTML = html;
    document.getElementById('serviceModalBackdrop').classList.add('open');
}

function closeServiceModal() {
    document.getElementById('serviceModalBackdrop').classList.remove('open');
}

function handleServiceAction(category, action) {
    alert('📌 ' + category.toUpperCase() + ' → ' + action + '\n\nThis will open the ' + action + ' module.');
    closeServiceModal();
}

// ========== PROFILE SETTINGS MODAL ==========
function openProfileModal() {
    closeDropdown();
    document.getElementById('profileSuccess').classList.remove('show');
    document.getElementById('fullNameError').classList.remove('show');
    document.getElementById('profileForm').reset();
    document.getElementById('fullName').value = '<?php echo addslashes($user_fullname); ?>';
    document.getElementById('email').value = '<?php echo addslashes($user_email); ?>';
    document.getElementById('studentId').value = '<?php echo addslashes($user_student_id ?? '2026-12345'); ?>';
    document.getElementById('college').value = '<?php echo addslashes($user_college ?? 'College of Information Technology'); ?>';
    document.getElementById('course').value = '<?php echo addslashes($user_course ?? 'BS Information Technology'); ?>';
    document.getElementById('yearLevel').value = '<?php echo addslashes($user_year_level ?? '3rd'); ?>';
    document.getElementById('profileAvatar').textContent = '<?php echo $user_initials; ?>';
    document.getElementById('profileModalBackdrop').classList.add('open');
}

function closeProfileModal() {
    document.getElementById('profileModalBackdrop').classList.remove('open');
    document.getElementById('fullNameError').classList.remove('show');
    document.getElementById('profileSuccess').classList.remove('show');
}

document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fullName = document.getElementById('fullName').value.trim();
    var errorEl = document.getElementById('fullNameError');
    var successEl = document.getElementById('profileSuccess');
    if (!fullName) {
        errorEl.classList.add('show');
        successEl.classList.remove('show');
        return;
    }
    errorEl.classList.remove('show');
    successEl.classList.add('show');
    var nameParts = fullName.split(' ');
    var initials = '';
    if (nameParts.length >= 2) {
        initials = nameParts[0].charAt(0).toUpperCase() + nameParts[1].charAt(0).toUpperCase();
    } else if (nameParts.length === 1) {
        initials = nameParts[0].charAt(0).toUpperCase();
    } else {
        initials = 'JD';
    }
    document.getElementById('profileAvatar').textContent = initials || 'JD';
    setTimeout(function() {
        closeProfileModal();
        alert('✅ Profile updated successfully!');
    }, 800);
});

document.getElementById('changePhotoBtn').addEventListener('click', function() {
    document.getElementById('photoInput').click();
});

document.getElementById('photoInput').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png'].includes(file.type)) {
        alert('Please select a JPG or PNG image.');
        this.value = '';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        alert('File size exceeds 2MB. Please select a smaller image.');
        this.value = '';
        return;
    }
    var reader = new FileReader();
    reader.onload = function(ev) {
        var avatar = document.getElementById('profileAvatar');
        avatar.textContent = '📷';
        setTimeout(function() {
            avatar.textContent = '<?php echo $user_initials; ?>';
        }, 800);
        alert('✅ Photo updated successfully! (demo)');
    };
    reader.readAsDataURL(file);
    this.value = '';
});

// ========== MODAL ==========
function openModal(title, content) {
    closeDropdown();
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modalBackdrop').classList.add('open');
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.remove('open');
}

// ========== OFF-CANVAS ==========
function openOffcanvas() {
    document.getElementById('offcanvasPanel').classList.add('open');
    document.getElementById('offcanvasOverlay').classList.add('open');
    closeDropdown();
}
function closeOffcanvas() {
    document.getElementById('offcanvasPanel').classList.remove('open');
    document.getElementById('offcanvasOverlay').classList.remove('open');
}

// ========== STUDENT ACTIONS ==========
function viewStudent(id) {
    var students = <?php echo json_encode($students); ?>;
    var student = students.find(s => s.id == id);
    if (!student) {
        alert('Student not found.');
        return;
    }
    var body = document.getElementById('offcanvasBody');
    body.innerHTML = `
        <div class="student-detail-grid">
            <span class="detail-label">Student ID</span>
            <span class="detail-value">${student.student_id || 'N/A'}</span>
            
            <span class="detail-label">Full Name</span>
            <span class="detail-value">${student.full_name || 'N/A'}</span>
            
            <span class="detail-label">Email</span>
            <span class="detail-value">${student.email || 'N/A'}</span>
            
            <span class="detail-label">College</span>
            <span class="detail-value">${student.college || 'N/A'}</span>
            
            <span class="detail-label">Course</span>
            <span class="detail-value">${student.course || 'N/A'}</span>
            
            <span class="detail-label">Year Level</span>
            <span class="detail-value">${student.year_level || 'N/A'}</span>
            
            <span class="detail-label">Status</span>
            <span class="detail-value"><span class="record-status ${(student.enrollment_status || 'Active').toLowerCase()}">${student.enrollment_status || 'Active'}</span></span>
            
            <span class="detail-label">Registered</span>
            <span class="detail-value">${student.created_at ? new Date(student.created_at).toLocaleDateString() : 'N/A'}</span>
        </div>
        <div style="margin-top:1.25rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
            <button class="btn-primary" onclick="closeOffcanvas();editStudent(${student.id})">Edit Student</button>
            <button class="btn-secondary" onclick="alert('View grades for ${student.full_name}')">View Grades</button>
            <button class="btn-secondary" onclick="alert('View enrollment for ${student.full_name}')">Enrollment</button>
        </div>
    `;
    openOffcanvas();
}

function editStudent(id) {
    var students = <?php echo json_encode($students); ?>;
    var student = students.find(s => s.id == id);
    if (!student) {
        alert('Student not found.');
        return;
    }
    
    var content = `
        <div style="display:flex;flex-direction:column;gap:12px;font-size:0.875rem;">
            <div style="display:grid;grid-template-columns:120px 1fr;gap:8px 12px;padding-top:4px;">
                <span style="color:var(--text-3);">Student ID</span>
                <span><strong>${student.student_id || 'N/A'}</strong></span>
                <span style="color:var(--text-3);">Full Name</span>
                <input type="text" id="editName" value="${student.full_name || ''}" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                <span style="color:var(--text-3);">Status</span>
                <select id="editStatus" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                    <option value="Active" ${student.enrollment_status == 'Active' ? 'selected' : ''}>Active</option>
                    <option value="Inactive" ${student.enrollment_status == 'Inactive' ? 'selected' : ''}>Inactive</option>
                    <option value="Graduated" ${student.enrollment_status == 'Graduated' ? 'selected' : ''}>Graduated</option>
                    <option value="Transferred" ${student.enrollment_status == 'Transferred' ? 'selected' : ''}>Transferred</option>
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px;">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn-primary" onclick="saveStudentEdit(${student.id})">Save Changes</button>
            </div>
        </div>
    `;
    openModal('Edit Student', content);
}

function saveStudentEdit(id) {
    var name = document.getElementById('editName').value.trim();
    var status = document.getElementById('editStatus').value;
    if (!name) {
        alert('Full name is required.');
        return;
    }
    closeModal();
    alert('✅ Student #' + id + ' updated successfully!\n\nName: ' + name + '\nStatus: ' + status + '\n\n(In a real application, this would save to the database.)');
}

function openAddStudentModal() {
    var content = `
        <div style="display:flex;flex-direction:column;gap:12px;font-size:0.875rem;">
            <div style="display:grid;grid-template-columns:120px 1fr;gap:8px 12px;padding-top:4px;">
                <span style="color:var(--text-3);">Student ID</span>
                <input type="text" id="newStudentId" placeholder="e.g., 2026-12345" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                <span style="color:var(--text-3);">Full Name</span>
                <input type="text" id="newFullName" placeholder="e.g., Maria Santos" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                <span style="color:var(--text-3);">Email</span>
                <input type="email" id="newEmail" placeholder="student@umak.edu.ph" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                <span style="color:var(--text-3);">College</span>
                <input type="text" id="newCollege" placeholder="e.g., College of IT" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                <span style="color:var(--text-3);">Course</span>
                <input type="text" id="newCourse" placeholder="e.g., BSIT" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                <span style="color:var(--text-3);">Year Level</span>
                <select id="newYearLevel" style="padding:6px 10px;border:1px solid var(--border-md);border-radius:var(--radius-sm);font-family:'Noto Sans Osage',sans-serif;background:var(--surface);color:var(--text-1);width:100%;">
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year" selected>3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px;">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn-primary" onclick="saveNewStudent()">Add Student</button>
            </div>
        </div>
    `;
    openModal('Add New Student', content);
}

function saveNewStudent() {
    var studentId = document.getElementById('newStudentId').value.trim();
    var fullName = document.getElementById('newFullName').value.trim();
    var email = document.getElementById('newEmail').value.trim();
    var college = document.getElementById('newCollege').value.trim();
    var course = document.getElementById('newCourse').value.trim();
    var yearLevel = document.getElementById('newYearLevel').value;
    
    if (!studentId || !fullName || !email) {
        alert('Student ID, Full Name, and Email are required.');
        return;
    }
    
    closeModal();
    alert('✅ Student added successfully!\n\n' + 
          'Student ID: ' + studentId + '\n' +
          'Name: ' + fullName + '\n' +
          'Email: ' + email + '\n' +
          'College: ' + (college || 'N/A') + '\n' +
          'Course: ' + (course || 'N/A') + '\n' +
          'Year: ' + yearLevel + '\n\n' +
          '(In a real application, this would be saved to the database.)');
}

function doSignOut() {
    if (confirm('Are you sure you want to sign out?')) {
        window.location.href = '../includes/logout.php';
    }
}

// ========== FADE SCROLL REVEAL ==========
var fadeObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.15, rootMargin: "0px 0px -30px 0px" });

document.querySelectorAll('.fade-scroll').forEach(function(el) { fadeObserver.observe(el); });

// ========== HEADER NAV ACTIVE STATE ==========
document.querySelectorAll('.header-nav-link').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.header-nav-link').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
    });
});
</script>
</body>
</html>