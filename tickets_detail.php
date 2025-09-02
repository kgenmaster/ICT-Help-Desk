<?php
require_once 'config/db.php';

// --- VERY BASIC AUTHENTICATION ---
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_login.php');
    exit();
}
// --- END BASIC AUTHENTICATION ---

$tickets = [];
$message = '';

// Handle status updates from the tickets page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $ticketIdToUpdate = trim($_POST['ticket_id']);
    $newStatus = trim($_POST['status']);
    $assignedTo = !empty(trim($_POST['assigned_to'])) ? trim($_POST['assigned_to']) : null;

    if (!empty($ticketIdToUpdate) && !empty($newStatus)) {
        try {
            $stmt = $pdo->prepare("UPDATE tickets SET status = ?, assigned_to = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newStatus, $assignedTo, $ticketIdToUpdate]);
            $message = "success|Ticket #$ticketIdToUpdate updated successfully!";
        } catch (PDOException $e) {
            $message = "error|Error updating ticket #$ticketIdToUpdate: " . $e->getMessage();
        }
    } else {
        $message = "error|Invalid parameters for updating ticket.";
    }
}


// Fetch tickets for display, optionally filtered by status
$statusFilter = $_GET['status'] ?? 'all';
$query = "SELECT id, ticket_id, subject, full_name, email, department, description, status, assigned_to, created_at, updated_at FROM tickets";
$params = [];

if ($statusFilter !== 'all') {
    $query .= " WHERE status = ?";
    $params[] = str_replace('%20', ' ', $statusFilter);
}

$query .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "error|Error fetching tickets: " . $e->getMessage();
}

include 'templates/admin_header.php';
?>

    <div class="header">
        <h2>Tickets Management</h2>
        <div class="user-info">Viewing: <?= htmlspecialchars(ucwords(str_replace('%20', ' ', $statusFilter))); ?> Tickets</div>
    </div>

    <?php if ($message): ?>
        <?php list($type, $msg) = explode('|', $message, 2); ?>
        <div class="message <?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="ticket-list-container">
        <h3>Ticket Details</h3>
        <?php if (empty($tickets)): ?>
            <p class="no-tickets">No <?= htmlspecialchars(strtolower(str_replace('%20', ' ', $statusFilter))); ?> tickets found.</p>
        <?php else: ?>
            <ul class="ticket-list">
                <?php foreach ($tickets as $ticket): ?>
                    <li class="ticket-item<?= str_replace(' ', '-', strtolower($ticket['status'])); ?>">
                        <div class="ticket-header" data-ticket-id="<?= htmlspecialchars($ticket['id']); ?>">
                            <div class="ticket-info">
                                <span class="ticket-id">#<?= htmlspecialchars($ticket['ticket_id']); ?></span>
                                <span class="ticket-subject"><?= htmlspecialchars($ticket['subject']); ?></span>
                            </div>
                            <span class="status-badge status-<?= str_replace(' ', '-', strtolower($ticket['status'])); ?>">
                                <?= htmlspecialchars($ticket['status']); ?>
                            </span>
                        </div>
                        <div class="ticket-details" id="details-<?= htmlspecialchars($ticket['id']); ?>">
                            <div class="detail-row">
                                <p><strong>Requester:</strong> <?= htmlspecialchars($ticket['full_name']); ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($ticket['email']); ?></p>
                                <p><strong>Department:</strong> <?= htmlspecialchars($ticket['department']); ?></p>
                            </div>
                            <div class="detail-row">
                                <p><strong>Assigned To:</strong> <?= htmlspecialchars($ticket['assigned_to'] ?: 'Unassigned'); ?></p>
                                <p><strong>Submitted:</strong> <?= date('M d, Y', strtotime($ticket['created_at'])); ?></p>
                                <p><strong>Last Updated:</strong> <?= date('M d, Y H:i', strtotime($ticket['updated_at'])); ?></p>
                            </div>
                            <div class="detail-row-desc full-width">
                                <p><strong>Description:</strong></p>
                                <p class="description-text"><?= nl2br(htmlspecialchars($ticket['description'])); ?></p>
                            </div>
                            <div class="management-options">
                                <form action="tickets_detail.php?status=<?= htmlspecialchars($statusFilter); ?>" method="post" class="ticket-action-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']); ?>">
                                    
                                    <div class="form-field">
                                        <label for="status-<?= $ticket['id']; ?>">Status:</label>
                                        <select name="status" id="status-<?= $ticket['id']; ?>">
                                            <option value="Open" <?= ($ticket['status'] == 'Open') ? 'selected' : ''; ?>>Open</option>
                                            <option value="In Progress" <?= ($ticket['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Closed" <?= ($ticket['status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label for="assigned-to-<?= $ticket['id']; ?>">Assigned To:</label>
                                        <input type="text" id="assigned-to-<?= $ticket['id']; ?>" name="assigned_to" placeholder="e.g., John Doe" value="<?= htmlspecialchars($ticket['assigned_to']); ?>">
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
    
    <footer>
        &copy; <?= date("Y"); ?> Help Desk Admin. All rights reserved.
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
    });
    </script>
        </main>
    </div>
</body>
</html>