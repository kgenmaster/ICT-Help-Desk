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

include 'templates/admin_sidebar.php';

$total_tickets = 0;
$open_tickets = 0;
$in_progress_tickets = 0;
$closed_tickets = 0;
$message = '';
$admin_username = $_SESSION['username'];
$logs = []; // Initialize logs array

// Fetch only ticket counts for a quick overview
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tickets GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($status_counts as $row) {
        $status = strtolower($row['status']);
        $count = $row['count'];
        switch ($status) {
            case 'open':
                $open_tickets = $count;
                break;
            case 'in progress':
                $in_progress_tickets = $count;
                break;
            case 'closed':
                $closed_tickets = $count;
                break;
        }
    }
    $total_tickets = $open_tickets + $in_progress_tickets + $closed_tickets;

} catch (PDOException $e) {
    $message = "error|Error fetching ticket statistics: " . $e->getMessage();
}

// Fetch recent activity logs with better error handling
try {
    $stmt = $pdo->query("SELECT logs.activity, logs.created_at, users.username FROM logs JOIN users ON logs.user_id = users.id ORDER BY logs.created_at DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // This will now show an on-screen error message if the query fails
    $message = "error|Error fetching activity logs: " . $e->getMessage();
    error_log("Error fetching logs: " . $e->getMessage()); // Keep logging to file as well
}
?>

<style>
    /* New styles for the log table */
    .log-table-container {
        margin-top: 2rem;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px var(--shadow-light);
    }
    
    .log-table-container h3 {
        color: var(--text-color);
        margin-top: 0;
        margin-bottom: 1.5rem;
    }

    .log-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: var(--text-color);
    }

    .log-table th, .log-table td {
        padding: 12px;
        text-align: left;
    }

    .log-table thead th {
        background-color: var(--primary-color);
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid var(--border-color);
    }

    .log-table tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s ease;
    }

    .log-table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .log-table tbody tr:last-child {
        border-bottom: none;
    }

    .no-logs {
        text-align: center;
        color: var(--subtle-text);
        padding: 2rem;
    }
</style>

    <div class="header">
        <h2>Dashboard Overview</h2>
        <div class="user-info">Welcome, <?php echo htmlspecialchars($admin_username); ?></div>
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

    <div class="summary-cards">
        <div class="card total">
            <h3>Total Tickets</h3>
            <p><?php echo $total_tickets; ?></p>
        </div>
        <div class="card open">
            <h3>Open</h3>
            <p><?php echo $open_tickets; ?></p>
        </div>
        <div class="card in-progress">
            <h3>Assigned</h3>
            <p><?php echo $in_progress_tickets; ?></p>
        </div>
        <div class="card closed">
            <h3>Closed</h3>
            <p><?php echo $closed_tickets; ?></p>
        </div>
    </div>
    
    <div class="log-table-container">
        <h3>Recent Activity Log</h3>
        <?php if (empty($logs)): ?>
            <p class="no-logs">No recent activity logs found.</p>
        <?php else: ?>
            <table class="log-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><?php echo htmlspecialchars($log['activity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Help Desk Admin. All rights reserved.
    </footer>
    </main>
    </div>
</body>
</html>
