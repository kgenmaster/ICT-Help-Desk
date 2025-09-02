<?php
// config/db.php

$host = 'localhost';
$port = '5432';
$dbname = 'help_desk_db';
$user = getenv('PDB_USERNAME');
$password = getenv('PDB_PASSWORD');

// Construct the DSN without the credentials
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    // Pass user and password as separate arguments
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>