<?php
// Determine if we are on an admin page
$isAdminPage = (strpos($_SERVER['REQUEST_URI'], 'admin') !== false);

// If this is an admin page, we do not want to use the public header/footer
if ($isAdminPage) {
    // Include the dedicated admin dashboard logic and templates
    require_once 'admin_dashboard.php';
} else {
    // This is the public-facing section
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Help Desk</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="index.php">ICT Help Desk</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="submit_ticket.php">Submit a Ticket</a></li>
                    <li><a href="check_status.php">Check Ticket Status</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="container">
            <section class="hero">
                <h2>Welcome to the ICT Help Desk</h2>
                <p>Your one-stop solution for all IT-related issues. Our team is here to help you.</p>
                <div class="cta-buttons">
                    <a href="submit_ticket.php" class="btn btn-primary">Submit a Ticket</a>
                    <a href="check_status.php" class="btn btn-secondary">Check Status</a>
                </div>
            </section>

            <section class="features">
                <h3>Our Services</h3>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h4>Hardware Support</h4>
                        <p>Assistance with desktops, laptops, printers, and other peripherals.</p>
                    </div>
                    <div class="feature-item">
                        <h4>Software Troubleshooting</h4>
                        <p>Help with operating systems, software installation, and application errors.</p>
                    </div>
                    <div class="feature-item">
                        <h4>Network Connectivity</h4>
                        <p>Support for Wi-Fi, VPN, and other network-related issues.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ICT Help Desk. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php
}
?>