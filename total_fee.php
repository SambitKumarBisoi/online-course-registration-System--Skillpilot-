<?php
session_start();
include 'db.php';

// ✅ Only student can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Calculate total fee
$sql = "SELECT SUM(c.price) AS total_fee
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$total_fee = $result['total_fee'] ?? 0;
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Total Fee — SkillPilot</title>
<link rel="stylesheet" href="theme-admin.css"> <!-- reuse your theme -->
</head>
<body class="container">

<h2>Hello, <?= htmlspecialchars($username) ?> 👋</h2>
<h3>Your Total Fee: ₹<?= $total_fee ?></h3>

<a href="payment.php" class="btn btn-success">💳 Pay Now</a>

</body>
</html>
