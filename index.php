<?php include 'templates/header.php'; ?>

        <div class="container">
        <section class="hero">
            <h2>Welcome to the ICT Help Desk</h2>
            <p>Your one-stop solution for all IT-related issues. Our team is here to help you.</p>
            <div class="cta-buttons">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'submit_ticket.php' : 'login.php'; ?>" class="btn btn-primary">Submit a Ticket</a>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'check_status.php' : 'login.php'; ?>" class="btn btn-secondary">Check Status</a>
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


<?php include 'templates/footer.php'; ?>
