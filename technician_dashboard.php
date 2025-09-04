<?php include 'templates/header.php'; ?>

<?php
// PHP logic must be at the very top of the file before any HTML.
// Check if the user is a logged-in technician. If not, redirect to the login page.
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'technician') {
    header('Location: login.php');
    exit();
}

require_once 'config/db.php';
require_once 'log_activity.php'; // Include the logging helper
$message = '';
$assignedTickets = [];
$openTickets = [];

// Handle form submission to update ticket status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['complete_ticket'])) {
        $ticketId = $_POST['ticket_id'];
        
        try {
            // Update the status to 'Awaiting User Confirmation' instead of 'Fixed'
            $sql = "UPDATE tickets SET status = 'Awaiting User Confirmation' WHERE ticket_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ticketId]);
            
            logActivity($pdo, $_SESSION['user_id'], "Technician marked ticket #{$ticketId} as awaiting user confirmation.");
            $message = "success|Ticket #{$ticketId} has been marked as complete and is now awaiting user confirmation.";
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $message = "error|There was an error updating the ticket. Please try again.";
        }
    } elseif (isset($_POST['assign_ticket'])) {
        $ticketId = $_POST['ticket_id'];
        $technicianUsername = $_SESSION['username'];
        
        try {
            $sql = "UPDATE tickets SET status = 'In Progress', assigned_to = ? WHERE ticket_id = ? AND status = 'Open'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$technicianUsername, $ticketId]);
            if ($stmt->rowCount() > 0) {
                logActivity($pdo, $_SESSION['user_id'], "Assigned ticket #{$ticketId} to self.");
                $message = "success|You have been assigned Ticket #{$ticketId}.";
            } else {
                $message = "error|Ticket #{$ticketId} could not be assigned. It may already be assigned to someone else.";
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $message = "error|There was an error assigning the ticket. Please try again.";
        }
    }
}

// Fetch tickets assigned to the logged-in technician, excluding closed tickets
try {
    $technicianUsername = $_SESSION['username'];
    $sql = "SELECT ticket_id, subject, description, status FROM tickets WHERE assigned_to = ? AND status != 'Closed' ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$technicianUsername]);
    $assignedTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $message = "error|Could not load your assigned tickets.";
}

// Fetch open (unassigned) tickets
try {
    $sql = "SELECT ticket_id, subject, description FROM tickets WHERE status = 'Open' ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $openTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $message = "error|Could not load open tickets.";
}

?>


<div class="container">
    <section class="ticket-status">
        <h2>Technician Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>. Here are your assigned tickets.</p>
        
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

        <h3>My Assigned Tickets</h3>
        <?php if (empty($assignedTickets)): ?>
            <div class="message">You have no tickets currently assigned.</div>
        <?php else: ?>
            <div class="feature-grid">
                <?php foreach ($assignedTickets as $ticket): ?>
                    <div class="feature-item" style="text-align: left;">
                        <h4>Ticket #<?php echo htmlspecialchars($ticket['ticket_id']); ?>: <?php echo htmlspecialchars($ticket['subject']); ?></h4>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($ticket['description']); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge status-in-progress"><?php echo htmlspecialchars($ticket['status']); ?></span></p>
                        
                        <?php if ($ticket['status'] === 'In Progress'): ?>
                            <form action="technician_dashboard.php" method="post">
                                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['ticket_id']); ?>">
                                <button type="submit" name="complete_ticket" class="btn btn-primary">Mark as Complete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <hr>

    <section class="open-tickets">
        <h3>Open Tickets</h3>
        <p>Available tickets that you can assign to yourself.</p>
        <?php if (empty($openTickets)): ?>
            <div class="message">No open tickets at this time.</div>
        <?php else: ?>
            <div class="feature-grid">
                <?php foreach ($openTickets as $ticket): ?>
                    <div class="feature-item" style="text-align: left;">
                        <h4>Ticket #<?php echo htmlspecialchars($ticket['ticket_id']); ?>: <?php echo htmlspecialchars($ticket['subject']); ?></h4>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($ticket['description']); ?></p>
                        <form action="technician_dashboard.php" method="post">
                            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['ticket_id']); ?>">
                            <button type="submit" name="assign_ticket" class="btn btn-primary">Assign to Me</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'templates/footer.php'; ?>
