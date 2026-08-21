<?php
// includes/logout.php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to Front_Page
header('Location: ../Front_Page.php');
exit();
?>