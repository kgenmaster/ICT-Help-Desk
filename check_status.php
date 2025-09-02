<?php
require_once 'config/db.php';
$ticket = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = trim($_POST['ticket_id']);

    if (empty($ticket_id)) {
        $error = 'Please enter a valid Ticket ID.';
    } else {
        $sql = "SELECT * FROM tickets WHERE ticket_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $error = "No ticket found with the ID: <strong>$ticket_id</strong>";
        }
    }
}
?>

<?php include 'templates/header.php'; ?>

<div class="container">
    <section class="form-section">
        <h2>Check Ticket Status</h2>
        <p>Enter your Ticket ID below to check the current status of your request.</p>

        <form action="check_status.php" method="post">
            <div class="form-group">
                <label for="ticket_id">Ticket ID</label>
                <input type="text" id="ticket_id" name="ticket_id" required value="<?php echo isset($_POST['ticket_id']) ? htmlspecialchars($_POST['ticket_id']) : ''; ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Check Status</button>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($ticket): ?>
            <div class="ticket-status">
                <h3>Ticket Details</h3>
                <p><strong>Ticket ID:</strong> <?php echo htmlspecialchars($ticket['ticket_id']); ?></p>
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?></p>
                <p><strong>Status:</strong> <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower(htmlspecialchars($ticket['status']))); ?>"><?php echo htmlspecialchars($ticket['status']); ?></span></p>
                <p><strong>Submitted On:</strong> <?php echo date('F j, Y, g:i a', strtotime($ticket['created_at'])); ?></p>
                <h4>Description</h4>
                <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'templates/footer.php'; ?>