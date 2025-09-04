<?php
// Start the session to access and destroy session variables.
// The session_start() call in header.php is sufficient, but it's good practice
// to ensure the session is active here before attempting to destroy it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and log helper.
require_once 'config/db.php';
require_once 'log_activity.php';

// Log the logout event before destroying the session.
// We need to check if 'user_id' is set to avoid an error if the user tries to logout when they aren't logged in.
if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'Logged out');
}

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the home page after a successful logout.
header('Location: index.php');
exit();
?>
