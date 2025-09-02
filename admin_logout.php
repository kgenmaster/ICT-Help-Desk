<?php
// Start the session to access session variables.
session_start();

// Unset all session variables.
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to the login page.
header("Location: admin_login.php");
exit;
