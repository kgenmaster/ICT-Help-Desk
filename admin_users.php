<?php
session_start();

// PHP logic must be at the very top of the file before any HTML.
// Start the session to ensure you can check for the user's logged-in status.
// This is already handled in header.php

// Include the database connection file.
require_once 'config/db.php';
require_once 'log_activity.php'; // Include the logging helper

// --- BASIC AUTHENTICATION ---
// Check if the user is logged in and has the 'admin' role.
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to the login page if not an admin
    header('Location: login.php');
    exit();
}
// --- END BASIC AUTHENTICATION ---

$message = '';
$users = [];

// Handle form submissions for deleting or editing users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $userIdToDelete = $_POST['user_id'];
        
        // Prevent an admin from deleting their own account
        if ($userIdToDelete == $_SESSION['user_id']) {
            $message = "error|You cannot delete your own account.";
        } else {
            try {
                // Prepare the SQL statement to delete the user from the database.
                $sql = "DELETE FROM users WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userIdToDelete]);
                
                logActivity($pdo, $_SESSION['user_id'], "Admin deleted user ID: {$userIdToDelete}.");
                $message = "success|User with ID {$userIdToDelete} has been deleted.";
            } catch (PDOException $e) {
                error_log("Database Error: " . $e->getMessage());
                $message = "error|Error deleting user.";
            }
        }
    } elseif (isset($_POST['update_user_info'])) {
        $userIdToUpdate = $_POST['user_id'];
        $newFirstName = trim($_POST['first_name']);
        $newLastName = trim($_POST['last_name']);
        $newPhone = trim($_POST['phone_number']);

        try {
            // Update only the non-unique and non-primary key fields.
            $sql = "UPDATE users SET first_name = ?, last_name = ?, phone_number = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newFirstName, $newLastName, $newPhone, $userIdToUpdate]);

            logActivity($pdo, $_SESSION['user_id'], "Admin updated information for user ID: {$userIdToUpdate}.");
            $message = "success|User information for ID {$userIdToUpdate} has been updated.";
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $message = "error|Error updating user information.";
        }
    }
}

// Fetch all users to display them on the dashboard, excluding admins.
try {
    $sql = "SELECT id, username, first_name, last_name, role, phone_number FROM users WHERE role != 'admin' ORDER BY id ASC";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $message = "error|Could not load user data.";
}

include 'templates/admin_sidebar.php';
?>

<style>
/* --- General Table Styles --- */
.table-container {
    overflow-x: auto;
    margin-top: 20px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.data-table th, .data-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

.data-table th {
    background-color: #f4f4f4;
    font-weight: bold;
}

/* --- Form and Input Styles --- */
.data-table form {
    display: flex;
    gap: 5px;
    align-items: center;
}

.data-table input[type="text"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box; /* Ensures padding doesn't affect the width */
    transition: border-color 0.3s ease;
}

.data-table input[type="text"]:focus {
    border-color: #007bff;
    outline: none;
}

/* --- Button Styles --- */
.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s ease;
    white-space: nowrap;
}

/* New style for small buttons */
.btn-small {
    padding: 6px 10px;
    width: 100%;
    margin: 6px 0;
    text-align: center;
    font-size: 0.85em;
}

.btn-primary {
    background-color: #0b7a26;
    color: white;
}

.btn-primary:hover {
    background-color: #2e463a;
}

.btn-danger {
    background-color: #cc2529;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}
</style>

<div class="container">
    <section class="admin-dashboard">
        <h2>User Management</h2>
        <p>Manage users and technicians, and their basic information.</p>
        
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

        <?php if (empty($users)): ?>
            <div class="message info">No users or technicians found.</div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Phone Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <form action="admin_users.php" method="post" style="display:contents;">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <td><input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required></td>
                                    <td><input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required></td>
                                    <td><input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>"></td>
                                    <td>
                                        <button type="submit" name="update_user_info" class="btn btn-small btn-primary">Update</button>
                                        <button type="submit" name="delete_user" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<footer>
        &copy; <?php echo date("Y"); ?> Help Desk Admin. All rights reserved.
    </footer>
    </main>
    </div>
</body>
</html>