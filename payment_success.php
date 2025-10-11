<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if(!$user_id){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Success — SkillPilot</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:linear-gradient(180deg,#071428,#102a43);color:#e6f7ff;margin:0;padding:0;}
.container{max-width:500px;margin:80px auto;padding:30px;background:rgba(255,255,255,0.05);border-radius:12px;text-align:center;}
h2{margin-bottom:20px;color:#4caf50;}
.btn{display:inline-block;margin-top:20px;padding:10px 20px;border:none;border-radius:8px;background:#1e88e5;color:#fff;font-weight:600;text-decoration:none;}
.btn:hover{background:#1565c0;}
</style>
</head>
<body>
<div class="container">
<h2>✅ Payment Successful!</h2>
<p>Thank you for your payment. Your courses are now enrolled.</p>
<a href="student_dashboard.php" class="btn">Go to Dashboard</a>
</div>
</body>
</html>
