<?php
require_once 'config/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);

    // Generate a unique ticket ID
    $ticket_id = 'TICKET-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    $sql = "INSERT INTO tickets (ticket_id, full_name, email, department, subject, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$ticket_id, $full_name, $email, $department, $subject, $description]);
        $message = "success|Your ticket has been submitted successfully! Your Ticket ID is: <strong>$ticket_id</strong>";
    } catch (PDOException $e) {
        $message = "error|There was an error submitting your ticket. Please try again.";
    }
}
?>

<?php include 'templates/header.php'; ?>

<div class="container">
    <section class="form-section">
        <h2>Submit a New Ticket</h2>
        <p>Please fill out the form below to submit a new ticket. Provide as much detail as possible.</p>

        <?php if ($message): ?>
            <?php list($type, $msg) = explode('|', $message, 2); ?>
            <div class="message <?php echo $type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="submit_ticket.php" method="post">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department">
            </div>
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