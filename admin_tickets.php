<?php
// PHP logic must be at the very top of the file before any HTML.
// Start the session at the very beginning of the page.
session_start();

require_once 'config/db.php';
include 'log_activity.php';

// Check if the user is a logged-in admin. If not, redirect to the login page.
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch the administrator's username from the session for display.
$admin_username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

$openAndAssignedTickets = [];
$closedTickets = [];
$message = '';

// --- FETCH LIST OF TECHNICIANS ---
// This list will be used to populate the "Assigned To" dropdown.
$technicians = [];
try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE role = 'technician' ORDER BY username ASC");
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error but don't stop the page from loading.
    error_log("Error fetching technicians: " . $e->getMessage());
}
// --- END FETCH LIST OF TECHNICIANS ---


// Handle status updates from the tickets page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $ticketIdToUpdate = trim($_POST['ticket_id']);
    $newStatus = trim($_POST['status']);
    $assignedTo = !empty(trim($_POST['assigned_to'])) ? trim($_POST['assigned_to']) : null;

    if (!empty($ticketIdToUpdate) && !empty($newStatus)) {
        try {
            // Note: The `assigned_to` field can be null if a ticket is unassigned.
            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, assigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newStatus, $assignedTo, $ticketIdToUpdate]);
            
            // Log the activity
            logActivity($pdo, $user_id, "Updated ticket #$ticketIdToUpdate status to '$newStatus'");

            $message = "success|Ticket #$ticketIdToUpdate updated successfully!";
        } catch (PDOException $e) {
            $message = "error|Error updating ticket #$ticketIdToUpdate: " . $e->getMessage();
        }
    } else {
        $message = "error|Invalid parameters for updating ticket.";
    }
}


// --- FETCH TICKETS FOR DISPLAY ---
// Fetch Open and In Progress tickets
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE status IN ('Open', 'In Progress') ORDER BY created_at DESC");
    $stmt->execute();
    $openAndAssignedTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "error|Error fetching open and in progress tickets: " . $e->getMessage();
    error_log("Error fetching open and in progress tickets: " . $e->getMessage());
}

// Fetch Closed tickets
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE status = 'Closed' ORDER BY created_at DESC");
    $stmt->execute();
    $closedTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "error|Error fetching closed tickets: " . $e->getMessage();
    error_log("Error fetching closed tickets: " . $e->getMessage());
}
// --- END FETCH TICKETS FOR DISPLAY ---

include 'templates/admin_sidebar.php';
?>

    <div class="header">
        <h2>Tickets Management</h2>
    </div>

    <?php if ($message): ?>
        <?php 
        if (strpos($message, '|') !== false) {
            list($type, $msg) = explode('|', $message, 2);
        } else {
            $type = 'error'; // Default to error if no type is specified
            $msg = $message;
        }
        ?>
        <div class="message <?php echo htmlspecialchars($type); ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div class="ticket-list-container">
        <h3>Open & In Progress Tickets</h3>
        <?php if (empty($openAndAssignedTickets)): ?>
            <p class="no-tickets">No open or in progress tickets found.</p>
        <?php else: ?>
            <ul class="ticket-list">
                <?php foreach ($openAndAssignedTickets as $ticket): ?>
                    <li class="ticket-item ticket-item-<?php echo str_replace(' ', '-', strtolower($ticket['status'])); ?>">
                        <div class="ticket-header" data-ticket-id="<?php echo htmlspecialchars($ticket['id']); ?>">
                            <div class="ticket-info">
                                <span class="ticket-id">#<?php echo htmlspecialchars($ticket['ticket_id']); ?></span>
                                <span class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($ticket['status'])); ?>">
                                <?php echo htmlspecialchars($ticket['status']); ?>
                            </span>
                        </div>
                        <div class="ticket-details" id="details-<?php echo htmlspecialchars($ticket['id']); ?>">
                            <div class="detail-row">
                                <p><strong>Requester:</strong> <?php echo htmlspecialchars($ticket['full_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($ticket['department']); ?></p>
                            </div>
                            <div class="detail-row">
                                <p><strong>Assigned To:</strong> <?php echo htmlspecialchars($ticket['assigned_to'] ?: 'Unassigned'); ?></p>
                                <p><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></p>
                            </div>
                            <div class="detail-row-desc full-width">
                                <p><strong>Description:</strong></p>
                                <p class="description-text"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                            </div>
                            
                            <!-- The update form is only for non-closed tickets -->
                            <div class="management-options">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="ticket-action-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
                                    
                                    <div class="form-field">
                                        <label for="status-<?php echo $ticket['id']; ?>">Status:</label>
                                        <select name="status" id="status-<?php echo $ticket['id']; ?>">
                                            <option value="Open" <?php echo ($ticket['status'] == 'Open') ? 'selected' : ''; ?>>Open</option>
                                            <option value="In Progress" <?php echo ($ticket['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Closed" <?php echo ($ticket['status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label for="assigned-to-<?php echo $ticket['id']; ?>">Assigned To:</label>
                                        <select name="assigned_to" id="assigned-to-<?php echo $ticket['id']; ?>" <?php echo ($ticket['status'] !== 'In Progress') ? 'disabled' : ''; ?>>
                                            <option value="">-- Unassigned --</option>
                                            <?php foreach ($technicians as $technician): ?>
                                                <option value="<?php echo htmlspecialchars($technician['username']); ?>" <?php echo ($ticket['assigned_to'] === $technician['username']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($technician['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Ticket</button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Separated section for Closed tickets -->
    <div class="ticket-list-container fixed-tickets-container" style="margin-top: 40px;">
        <h3 ">Closed Tickets</h3>
        <?php if (empty($closedTickets)): ?>
            <p class="no-tickets">No closed tickets found.</p>
        <?php else: ?>
            <ul class="ticket-list">
                <?php foreach ($closedTickets as $ticket): ?>
                    <li class="ticket-item ticket-item-closed">
                        <div class="ticket-header" data-ticket-id="<?php echo htmlspecialchars($ticket['id']); ?>">
                            <div class="ticket-info">
                                <span class="ticket-id">#<?php echo htmlspecialchars($ticket['ticket_id']); ?></span>
                                <span class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></span>
                            </div>
                            <span class="status-badge status-closed">
                                <?php echo htmlspecialchars($ticket['status']); ?>
                            </span>
                        </div>
                        <div class="ticket-details" id="details-<?php echo htmlspecialchars($ticket['id']); ?>">
                            <div class="detail-row">
                                <p><strong>Requester:</strong> <?php echo htmlspecialchars($ticket['full_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($ticket['department']); ?></p>
                            </div>
                            <div class="detail-row">
                                <p><strong>Assigned To:</strong> <?php echo htmlspecialchars($ticket['assigned_to'] ?: 'Unassigned'); ?></p>
                                <p><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></p>
                            </div>
                            <div class="detail-row-desc full-width">
                                <p><strong>Description:</strong></p>
                                <p class="description-text"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                            </div>
                            <div class="management-options fixed-ticket-info">
                                <p class="text-green-500 font-semibold">This ticket has been closed and can no longer be updated.</p>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <footer>
        &copy; <?php echo date("Y"); ?> Help Desk Admin. All rights reserved.
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ticketHeaders = document.querySelectorAll('.ticket-header');
        
        ticketHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const ticketId = header.dataset.ticketId;
                const details = document.getElementById(`details-${ticketId}`);
                details.classList.toggle('visible');
            });
        });

        // Function to handle the state of the assigned_to dropdown
        const updateAssignedToState = (statusSelect, assignedToSelect) => {
            if (statusSelect.value === 'In Progress') {
                assignedToSelect.disabled = false;
            } else {
                assignedToSelect.disabled = true;
                // Optionally clear the assigned technician if status is changed away from 'In Progress'
                if (statusSelect.value !== 'In Progress') {
                    assignedToSelect.value = '';
                }
            }
        };

        const statusSelects = document.querySelectorAll('select[name="status"]');
        statusSelects.forEach(statusSelect => {
            const ticketId = statusSelect.id.split('-')[1];
            const assignedToSelect = document.getElementById(`assigned-to-${ticketId}`);
            
            // Initial state check on page load
            updateAssignedToState(statusSelect, assignedToSelect);
            
            // Event listener for changes
            statusSelect.addEventListener('change', () => {
                updateAssignedToState(statusSelect, assignedToSelect);
            });
        });
    });
    </script>
        </main>
    </div>
</body>
</html>
