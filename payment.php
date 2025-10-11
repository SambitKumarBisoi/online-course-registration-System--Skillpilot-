<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Determine source: single course or cart or "pay all pending"
$from = $_GET['from'] ?? null;
$all = $_GET['all'] ?? null;
$course_id = $_GET['course_id'] ?? null;

// Initialize courses array to pay
$pay_courses = [];
$total_amount = 0;

// ----- (A) Single course payment -----
if($course_id){
    $stmt = $conn->prepare("SELECT id, course_name, price FROM courses WHERE id=?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
        $pay_courses[] = $res->fetch_assoc();
        $total_amount = $pay_courses[0]['price'];
    } else {
        die("Course not found!");
    }
}
// ----- (B) Pay all pending enrollments -----
elseif($all){
    $stmt = $conn->prepare("
        SELECT c.id, c.course_name, c.price 
        FROM enrollments e 
        JOIN courses c ON e.course_id=c.id 
        WHERE e.user_id=? AND e.payment_status='pending'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($r = $res->fetch_assoc()){
        $pay_courses[] = $r;
        $total_amount += $r['price'];
    }
}
// ----- (C) Cart payment -----
elseif($from==='cart'){
    $stmt = $conn->prepare("
        SELECT c.id, c.course_name, c.price 
        FROM cart ct 
        JOIN courses c ON ct.course_id=c.id 
        WHERE ct.user_id=?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($r = $res->fetch_assoc()){
        $pay_courses[] = $r;
        $total_amount += $r['price'];
    }
}

// If nothing to pay
if(count($pay_courses)==0){
    die("No courses selected for payment!");
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>SkillPilot — Payment</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{margin:0;font-family:'Poppins',system-ui;background:linear-gradient(180deg,#071428,#102a43);color:#e6f7ff;}
.container{max-width:700px;margin:50px auto;padding:20px;background:rgba(255,255,255,0.05);border-radius:12px;}
h2{margin-bottom:20px;}
.course-item{margin-bottom:10px;padding:10px;border-bottom:1px solid rgba(255,255,255,0.2);}
.total{font-weight:600;margin-top:15px;}
.btn{padding:10px 16px;margin-top:20px;border:none;border-radius:8px;background:linear-gradient(90deg,#00c6ff,#7b2ff7);color:#fff;font-weight:700;cursor:pointer;}
</style>
</head>
<body>
<div class="container">
<h2>💳 Payment Summary</h2>

<?php foreach($pay_courses as $c): ?>
<div class="course-item">
<?= htmlspecialchars($c['course_name']) ?> — ₹<?= $c['price'] ?>
</div>
<?php endforeach; ?>

<div class="total">Total Amount: ₹<?= number_format($total_amount,2) ?></div>

<!-- Payment options -->
<form method="POST" action="payment_process.php">
<input type="hidden" name="all" value="<?= isset($_GET['all']) ? '1' : '0' ?>">
<input type="hidden" name="total_amount" value="<?= $total_amount ?>">
<input type="hidden" name="from" value="<?= htmlspecialchars($from ?? '') ?>">
<?php if($course_id): ?>
<input type="hidden" name="course_id" value="<?= $course_id ?>">
<?php endif; ?>

<h4>Select Payment Method:</h4>
<select name="payment_method" required style="padding:10px;border-radius:8px;margin-top:10px;width:100%;">
<option value="">-- Choose --</option>
<option value="razorpay">Razorpay</option>
<option value="card">Card</option>
<option value="upi">UPI</option>
</select>

<button type="submit" class="btn">Pay Now</button>
</form>
</div>
</body>
</html>
