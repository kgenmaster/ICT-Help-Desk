<?php
// Start the session at the very beginning of the page
session_start();
require_once 'config/db.php';
require_once 'log_activity.php'; // Include the logging helper

// --- VERY BASIC AUTHENTICATION ---
// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to the login page if not an admin
    header('Location: login.php');
    exit();
}
// --- END BASIC AUTHENTICATION ---

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $password = trim($_POST['password']);
    $role = 'technician'; // This is hardcoded to be a technician

    // No password hashing is being used, as requested.
    // For a production environment, it is highly recommended to hash passwords for security.

    try {
        // Check if username already exists
        $check_username_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check_username_stmt->execute([$username]);
        
        // Check if phone number already exists
        $check_phone_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone_number = ?");
        $check_phone_stmt->execute([$phone_number]);
        
        if ($check_username_stmt->fetchColumn() > 0) {
            $message = "error|Username already exists. Please choose a different one.";
        } elseif ($check_phone_stmt->fetchColumn() > 0) {
            $message = "error|Phone number is already in use. Please choose a different one.";
        } else {
            // Prepare the SQL statement to insert the new technician
            $sql = "INSERT INTO users (username, first_name, last_name, phone_number, password, role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $first_name, $last_name, $phone_number, $password, $role]);
            
            // Get the ID of the newly created user
            $newUserId = $pdo->lastInsertId();
            
            // Log the account creation activity
            logActivity($pdo, $newUserId, 'New user account created for ' . $username);
            $message = "success|Technician registered successfully!";
        }
    } catch (PDOException $e) {
        $message = "error|Error registering technician: " . $e->getMessage();
    }
}

// Include the admin header template
include 'templates/admin_sidebar.php';
?>

<div class="registration-wrapper">
    <div class="login-container">
        <h2>Register a Technician</h2>
        <?php if ($message): ?>
            <?php list($type, $msg) = explode('|', $message, 2); ?>
            <div class="message <?= htmlspecialchars($type) ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        <form action="register_technician.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn fullwidth-btn">Register Technician</button>
            </div>
        </form>
    </div>
</div>

<footer>
        &copy; <?php echo date("Y"); ?> Help Desk Admin. All rights reserved.
    </footer>
    </main>
    </div>
</body>
</html>