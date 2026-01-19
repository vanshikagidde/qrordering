<?php
// Database configuration
$servername = "127.0.0.1:3307";   // or localhost
$username = "root";
$password = "";               // XAMPP default password is empty
$dbname = "qr_ordering";           // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
