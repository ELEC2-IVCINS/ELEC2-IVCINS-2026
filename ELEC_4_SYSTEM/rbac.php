<?php
// config/rbac.php

function checkRole($allowed_roles) {

    // ============================================
    // MUST BE LOGGED IN
    // ============================================
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../main/Front_Page.php');
        exit();
    }

    // ============================================
    // NORMALIZE THE ROLE
    // ============================================
    $role = strtolower(trim($_SESSION['role'] ?? ''));
    $_SESSION['role'] = $role;

    $valid_roles = ['student', 'faculty', 'alumni', 'admin'];

    // ============================================
    // IF ROLE IS COMPLETELY INVALID / UNRECOGNIZED
    // ============================================
    if (!in_array($role, $valid_roles, true)) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ../main/Front_Page.php?error=invalid_role');
        exit();
    }

    // ============================================
    // ROLE IS VALID BUT NOT ALLOWED ON THIS PAGE
    // ============================================
    if (!in_array($role, $allowed_roles, true)) {

        $dashboard_map = [
            'student' => 'User_Dashboard.php',
            'faculty' => 'Faculty_Dashboard.php',
            'alumni'   => 'Staff_Dashboard.php',
            'admin'   => 'Admin_Dashboard.php',
        ];

        $target = $dashboard_map[$role] ?? 'Front_Page.php';
        $current = basename($_SERVER['PHP_SELF'] ?? '');

        // Guard: never redirect a page to itself.
        if ($current === $target) {
            return;
        }

        // ============================================
        // SMART PATH: figure out correct relative path
        // ============================================
        $php_self = $_SERVER['PHP_SELF'] ?? '';
        
        if (strpos($php_self, '/modules/') !== false) {
            // Nasa /main/modules/ — kailangan umakyat ng 1 level
            $path = '../' . $target;
        } else {
            // Nasa /main/ — same folder
            $path = $target;
        }

        header('Location: ' . $path);
        exit();
    }
}
?>