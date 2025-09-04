<?php include 'templates/header.php'; ?>

<?php
// PHP logic must be at the very top of the file before any HTML.
// Note: session_start() is handled in header.php, which is included below.
require_once 'config/db.php';
require_once 'log_activity.php'; // Include the logging helper

// Redirect to login page if the user is not logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$tickets = [];
$error = '';
$message = '';

// Handle form submission for client confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_ticket'])) {
    $confirmTicketId = trim($_POST['confirm_ticket_id']);
    
    // Update the ticket status in the database using the ticket's primary key (id) and the user's ID for verification
    $sql = "UPDATE tickets SET status = 'Closed', user_confirmed = TRUE WHERE id = ? AND issued_by = ?";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$confirmTicketId, $_SESSION['user_id']]);
        logActivity($pdo, $_SESSION['user_id'], "User confirmed fix for ticket #{$confirmTicketId}.");
        $message = "success|Ticket #{$confirmTicketId} has been successfully closed. Thank you for your confirmation.";
    } catch (PDOException $e) {
        $error = "error|There was an error updating the ticket status. Please try again.";
        error_log("Database Error: " . $e->getMessage());
    }
}

// Fetch all tickets for the logged-in user by their ID, excluding those already closed
$sql = "SELECT * FROM tickets WHERE issued_by = ? AND status != 'Closed' ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch phone numbers for all assigned technicians
$technician_phone_numbers = [];
$technician_names = [];
if (!empty($tickets)) {
    foreach ($tickets as $ticket) {
        if (!empty($ticket['assigned_to'])) {
            $technician_names[] = $ticket['assigned_to'];
        }
    }
    $technician_names = array_unique($technician_names);

    if (!empty($technician_names)) {
        $placeholders = implode(',', array_fill(0, count($technician_names), '?'));
        $sql_tech_info = "SELECT CONCAT(first_name, ' ', last_name) AS full_name, phone_number FROM users WHERE CONCAT(first_name, ' ', last_name) IN ($placeholders)";
        $stmt_tech_info = $pdo->prepare($sql_tech_info);
        $stmt_tech_info->execute($technician_names);
        
        while ($row = $stmt_tech_info->fetch(PDO::FETCH_ASSOC)) {
            $technician_phone_numbers[$row['full_name']] = $row['phone_number'];
        }
    }
}
?>

<style>
/* Style for each ticket container */
.ticket-item {
    background-color: #f7f7f7;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    padding: 20px;
    transition: transform 0.2s ease-in-out;
}

.ticket-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e9e9e9;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.ticket-id {
    font-size: 1.2em;
    font-weight: bold;
    color: #4a4a4a;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
    color: #fff;
    text-transform: capitalize;
}

/* Status-specific badge colors */
.status-open { background-color: #5cb85c; }
.status-in-progress { background-color: #f0ad4e; color: #333; }
.status-awaiting-user-confirmation { background-color: #5bc0de; }
.status-closed { background-color: #777; }

.ticket-body h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1.1em;
    color: #333;
}

.ticket-body p.submitted-date {
    color: #888;
    font-size: 0.9em;
    margin-top: 10px;
}

.ticket-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s ease;
    text-decoration: none;
    color: white;
    text-align: center;
}

.btn-success {
    background-color: #5cb85c;
}

.btn-success:hover {
    background-color: #449d44;
}

.message {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 1em;
}

.message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.message.info {
    background-color: #cce5ff;
    color: #004085;
    border: 1px solid #b8daff;
}
</style>

<div class="container">
    <section class="dashboard">
        <h2>Welcome to your dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>.</h2>
        <p>Here is a list of all your submitted tickets.</p>

        <?php if ($message): ?>
            <?php list($type, $msg) = explode('|', $message, 2); ?>
            <div class="message <?php echo $type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
            <div class="message info">You have not submitted any open tickets yet.</div>
        <?php else: ?>
            <div class="ticket-list">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <span class="ticket-id">#<?php echo htmlspecialchars($ticket['ticket_id']); ?></span>
                            <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower(htmlspecialchars($ticket['status']))); ?>">
                                <?php echo htmlspecialchars($ticket['status']); ?>
                            </span>
                        </div>
                        <div class="ticket-body">
                            <h4><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                            <p><strong>Submitted by:</strong> <?php echo htmlspecialchars($ticket['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($ticket['department']); ?></p>
                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                            <?php if (!empty($ticket['assigned_to'])): ?>
                                <p><strong>Assigned to:</strong> <?php echo htmlspecialchars($ticket['assigned_to']); ?></p>
                                <?php if (isset($technician_phone_numbers[$ticket['assigned_to']])): ?>
                                    <p><strong>Technician Phone:</strong> <?php echo htmlspecialchars($technician_phone_numbers[$ticket['assigned_to']]); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p><strong>Assigned to:</strong> Not yet assigned</p>
                            <?php endif; ?>
                            <p class="submitted-date">Submitted on: <?php echo date('F j, Y', strtotime($ticket['created_at'])); ?></p>
                        </div>
                        <div class="ticket-actions">
                            <?php if ($ticket['status'] === 'Awaiting User Confirmation'): ?>
                                <form action="user_dashboard.php" method="post" style="display: inline;">
                                    <input type="hidden" name="confirm_ticket_id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
                                    <button type="submit" name="confirm_ticket" class="btn btn-success">Confirm Resolution</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php include 'templates/footer.php'; ?>
