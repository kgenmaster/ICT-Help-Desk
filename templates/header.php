<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Help Desk</title>
    <?php if (strpos($_SERVER['REQUEST_URI'], 'admin') !== false): ?>
        <link rel="stylesheet" href="public/css/admin.css">
    <?php else: ?>
        <link rel="stylesheet" href="public/css/style.css">
    <?php endif; ?>
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