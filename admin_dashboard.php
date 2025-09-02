<?php
require_once 'config/db.php';

// --- VERY BASIC AUTHENTICATION ---
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_login.php');
    exit();
}
// --- END BASIC AUTHENTICATION ---

$total_tickets = 0;
$open_tickets = 0;
$in_progress_tickets = 0;
$closed_tickets = 0;
$message = '';

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

include 'templates/admin_header.php';
?>

    <div class="header">
        <h2>Dashboard Overview</h2>
        <div class="user-info">Welcome, Adminstrator</div>
    </div>

    <?php if ($message): ?>
        <?php list($type, $msg) = explode('|', $message, 2); ?>
        <div class="message <?= htmlspecialchars($type) ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="summary-cards">
    <div class="card total">
        <h3>Total Tickets</h3>
        <p><?= $total_tickets; ?></p>
    </div>
    <div class="card open">
        <h3>Open</h3>
        <p><?= $open_tickets; ?></p>
    </div>
    <div class="card in-progress">
        <h3>In Progress</h3>
        <p><?= $in_progress_tickets; ?></p>
    </div>
    <div class="card closed">
        <h3>Closed</h3>
        <p><?= $closed_tickets; ?></p>
    </div>
</div>
    
    <footer>
        &copy; <?= date("Y"); ?> Help Desk Admin. All rights reserved.
    </footer>
        </main>
    </div>
</body>
</html>