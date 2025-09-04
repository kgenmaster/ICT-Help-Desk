<?php include 'templates/header.php'; ?>

<?php
// PHP logic must be at the very top of the file before any HTML.
// Start the session to get user information.
// Note: session_start() is handled in header.php, which is included below.

// Redirect to login page if the user is not logged in.
// This is a crucial security check.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php';
require_once 'log_activity.php'; // Include the logging helper

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from the session.
    $user_id = $_SESSION['user_id'];

    // Collect ticket data from the simplified form.
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);

    // Fetch required user information from the database
    $user_info = null;
    try {
        $sql_user = "SELECT first_name, last_name, email FROM users WHERE id = ?";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$user_id]);
        $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error (Fetching User Info): " . $e->getMessage());
        $message = "error|There was an error retrieving your information. Please try again.";
    }

    // Only proceed if user information was successfully fetched
    if ($user_info) {
        // Set values for the ticket based on user data and form input
        $ticket_id = 'TICKET-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        $full_name = htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']);
        $email = htmlspecialchars($user_info['email']);
        
        // This is a placeholder since the users table doesn't have a department column.
        $department = 'General IT'; 

        // Set default status as it is required by the tickets schema
        $status = 'Open';

        // Prepare the SQL statement to insert the new ticket.
        // The query is updated to match the database schema's columns.
        $sql = "INSERT INTO tickets (ticket_id, full_name, email, department, subject, description, status, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([$ticket_id, $full_name, $email, $department, $subject, $description, $status, $user_id]);
            logActivity($pdo, $_SESSION['user_id'], 'Submitted Ticket');
            $message = "success|Your ticket has been submitted successfully! Your Ticket ID is: <strong>$ticket_id</strong>";
        } catch (PDOException $e) {
            // Log the error for debugging purposes but show a generic message to the user.
            error_log("Database Error (Inserting Ticket): " . $e->getMessage());
            $message = "error|There was an error submitting your ticket. Please try again.";
        }
    }
}
?>


<div class="container">
    <section class="form-section">
        <h2>Submit a New Ticket</h2>
        <p>Please provide the subject and a detailed description of your issue. Your name and contact information are already on file.</p>

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
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="submit_ticket.php" method="post">
            <!-- Full Name, Email, and Department fields have been removed as they are now handled by the session -->
            
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            <div class="form-group">
                <label for="description">Description of Issue</label>
                <textarea id="description" name="description" rows="6" required></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </div>
        </form>
    </section>
</div>

<?php include 'templates/footer.php'; ?>
