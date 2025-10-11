
<?php
// Database configuration
$host = "127.0.0.1";   // localhost
$port = 3307;          // MySQL port shown in XAMPP
$user = "root";        // your MySQL username
$password = "Rit@2026"; // your MySQL password
$dbname = "course_registration"; // your database name

// Create connection
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
