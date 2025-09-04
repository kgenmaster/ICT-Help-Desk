<?php include 'templates/header.php'; ?>

<?php
// PHP logic must be at the very top of the file before any HTML.
// Start the session at the very beginning of the page.

// Include the database connection file.
require_once 'config/db.php';
require_once 'log_activity.php'; // Include the logging helper


$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // Prepare the SQL statement to select the user and their role from the database.
        // We select the user's password hash and role to verify against the submitted data.
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if a user with that username was found
        if ($user) {
            // Verify the submitted password against the hashed password from the database.
            // In a real application, you would use password_hash() when the user signs up.
            if ($password === $user['password']) {
                // Authentication successful.
                // Set session variables with the user's data from the database.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['role'];

                // Redirect based on the user's role.
                if ($user['role'] === 'user') {
                    header('Location: user_dashboard.php');
                } else if ($user['role'] === 'technician') {
                    header('Location: technician_dashboard.php');
                } else if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                }
                logActivity($pdo, $_SESSION['user_id'], 'Logged In');
                exit();
            } else {
                $message = "Invalid username or password.";
            }
        } else {
            $message = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $message = "An error occurred during login. Please try again.";
        // Log the error for debugging purposes.
        error_log("Database Error: " . $e->getMessage());
    }
}
?>

<div class="registration-wrapper">

    <div class="login-container">
        <h2>Log In</h2>
        <?php if ($message): ?>
            <div class="message error">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn fullwidth-btn">Login</button>
            </div>
        </form>
        <p class="mt-3">Don't have an account? <a href="signup.php" class="link-style"><strong>Sign-Up.</strong></a></p>
    </div>

</div>

<?php include 'templates/footer.php'; ?>
