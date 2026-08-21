<?php
// test_login_verify.php - ILAGAY SA HTDOCS FOLDER

session_start();

// Force login - bypass ang database
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Juan Dela Cruz';
$_SESSION['email'] = 'test@umak.edu.ph';
$_SESSION['role'] = 'student';
$_SESSION['logged_in'] = true;

// Redirect to dashboard
header('Location: User_Dashboard.php');
exit();
?>