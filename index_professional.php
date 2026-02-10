<?php
// FUTURE AUTOMOTIVE - Professional Index Page
// Clean, intelligent entry point
require_once 'config.php';
require_once 'includes/functions.php';

// Redirect to login if not authenticated
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Redirect to dashboard if authenticated
header('Location: dashboard_professional.php');
exit();
?>
