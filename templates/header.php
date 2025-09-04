<?php
// Always start the session at the very beginning of the page
// to ensure you can check for the user's logged-in status.
session_start();
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
                <?php
                // Check if the user is logged in
                if (isset($_SESSION['user_id'])) {
                    // Logged-in users
                    if ($_SESSION['user_type'] === 'technician') {
                        // Technician-specific links
                        ?>
                        <li><a href="technician_dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Log Out</a></li>
                        <?php
                    } elseif ($_SESSION['user_type'] === 'admin') {
                        // Admin-specific links
                        ?>
                        <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                        <li><a href="logout.php">Log Out</a></li>
                        <?php
                    } else {
                        // Default user links
                        ?>
                        <li><a href="user_dashboard.php">Dashboard</a></li>
                        <li><a href="submit_ticket.php">Submit a Ticket</a></li>
                        <li><a href="check_status.php">Check Status</a></li>
                        <li><a href="logout.php">Log Out</a></li>
                        <?php
                    }
                } else {
                    // Unsigned visitors
                    ?>
                    <li><a href="login.php">Log In</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                    <?php
                }
                ?>
            </ul>
        </nav>
    </div>
</header>
<main>
