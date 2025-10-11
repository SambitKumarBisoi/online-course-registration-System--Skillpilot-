<?php
$servername = "localhost";
$username   = "root";        // XAMPP default
$password   = "Rit@2026";    // Your MySQL password
$database   = "course_registration";
$port       = 3307;          // Your MySQL port

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
// echo "✅ Connected successfully!";
?>
