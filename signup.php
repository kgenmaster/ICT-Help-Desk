<?php include 'templates/header.php'; ?>

<?php
// PHP logic must be at the very top of the file before any HTML.
// Note: session_start() is handled in header.php
require_once 'config/db.php';
require_once 'log_activity.php'; // Include the logging helper

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Basic validation
    if ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if username already exists
            $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $message = "Username is already taken.";
            } else {
                // Check if email already exists
                $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $message = "Email address is already registered.";
                } else {
                    // Password is now stored without hashing, as requested.
                    // This is for demonstration purposes only and is NOT secure.
                    $sql = "INSERT INTO users (username, first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, 'user')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $firstName, $lastName, $email, $password]);

                    logActivity($pdo, $newUserId, 'New user account created');

                    $message = "success|Account created successfully! You can now log in.";
                }
            }
        } catch (PDOException $e) {
            $message = "error|There was an error creating your account. Please try again.";
            error_log("Database Error: " . $e->getMessage());
        }
    }
}
?>

<div class="registration-wrapper">

    <div class="login-container">
        <h2>Sign Up</h2>
        <?php if ($message): ?>
            <?php 
            if (strpos($message, '|') !== false) {
                list($type, $msg) = explode('|', $message, 2);
            } else {
                $type = 'error'; // Default to error if no type is specified
                $msg = $message;
            }
            ?>
            <div class="message <?php echo $type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
        <form action="signup.php" method="post">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn fullwidth-btn">Sign Up</button>
            </div>
        </form>
        <p class="mt-3">Already have an account? <a href="login.php" class="link-style"><strong>Log In.</strong></a></p>
    </div>

</div>

<?php include 'templates/footer.php'; ?>
