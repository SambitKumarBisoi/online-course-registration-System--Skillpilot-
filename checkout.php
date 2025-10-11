<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if(!$user_id){
    header("Location: login.php");
    exit();
}

// Fetch cart items
$cart_courses = [];
$stmt_cart = $conn->prepare("SELECT c.id, c.course_name, c.price FROM cart ct JOIN courses c ON ct.course_id=c.id WHERE ct.user_id=?");
$stmt_cart->bind_param("i",$user_id);
$stmt_cart->execute();
$res_cart = $stmt_cart->get_result();
while($r = $res_cart->fetch_assoc()){
    $cart_courses[] = $r;
}

// Fetch pending enrollments
$pending_courses = [];
$stmt_pending = $conn->prepare("
    SELECT e.course_id, c.course_name, c.price 
    FROM enrollments e 
    JOIN courses c ON e.course_id=c.id 
    WHERE e.user_id=? AND e.payment_status='pending'
");
$stmt_pending->bind_param("i",$user_id);
$stmt_pending->execute();
$res_pending = $stmt_pending->get_result();
while($r = $res_pending->fetch_assoc()){
    $pending_courses[] = $r;
}

// Calculate total & collect course IDs
$total_amount = 0;
$course_ids = [];

foreach($cart_courses as $c){
    $total_amount += $c['price'];
    $course_ids[] = $c['id'];
}
foreach($pending_courses as $p){
    $total_amount += $p['price'];
    $course_ids[] = $p['course_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — SkillPilot</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {font-family:'Poppins',sans-serif;background:#f7f9fc;margin:0;padding:0;}
.container {max-width:480px;margin:60px auto;background:#fff;border-radius:16px;padding:30px;box-shadow:0 12px 28px rgba(0,0,0,0.12);border:1px solid #e0e6ed;}
h2 {margin-bottom:24px;color:#333;font-weight:700;text-align:center;}
ul {list-style:none;padding:0;margin-bottom:20px;}
li {padding:12px;border-bottom:1px solid #f0f2f5;display:flex;justify-content:space-between;font-weight:500;}
.total {font-weight:700;font-size:18px;margin:20px 0;text-align:right;}
label {display:block;margin-bottom:8px;font-weight:600;}
select, input {width:100%;padding:12px;border-radius:10px;border:1px solid #d0d4db;margin-bottom:15px;font-size:15px;}
.btn {display:block;width:100%;padding:14px;border-radius:10px;border:none;background:linear-gradient(90deg,#635bff,#00c6ff);color:#fff;font-weight:700;font-size:16px;cursor:pointer;transition:transform 0.3s, box-shadow 0.3s;}
.btn:hover {transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.2);}
.back-btn {background:#fff;color:#333;border:1px solid #d0d4db;margin-top:10px;}
.back-btn:hover {background:#f0f2f5;color:#000;}
.payment-fields {display:none;margin-bottom:20px;}
</style>
<script>
function showFields() {
    let method = document.getElementById("payment_method").value;
    document.getElementById("upi-fields").style.display = (method === "upi") ? "block" : "none";
    document.getElementById("card-fields").style.display = (method === "card") ? "block" : "none";
    document.getElementById("netbanking-fields").style.display = (method === "netbanking") ? "block" : "none";
}
</script>
</head>
<body>
<div class="container">
<h2>💳 Checkout</h2>

<?php if(count($cart_courses) > 0): ?>
<h4>Cart Items</h4>
<ul>
<?php foreach($cart_courses as $c): ?>
<li><?= htmlspecialchars($c['course_name']) ?> <span>₹<?= $c['price'] ?></span></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if(count($pending_courses) > 0): ?>
<h4>Pending Enrollments</h4>
<ul>
<?php foreach($pending_courses as $p): ?>
<li><?= htmlspecialchars($p['course_name']) ?> <span>₹<?= $p['price'] ?></span></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<p class="total">Total: ₹<?= $total_amount ?></p>

<form method="POST" action="payment_process.php">
    <input type="hidden" name="amount" value="<?= $total_amount ?>">
    <input type="hidden" name="course_ids" value="<?= implode(',', $course_ids) ?>">
    <input type="hidden" name="from" value="<?= count($cart_courses) > 0 ? 'cart' : 'pending' ?>">

    <label>Select Payment Method:</label>
    <select name="payment_method" id="payment_method" onchange="showFields()" required>
        <option value="">-- Choose --</option>
        <option value="card">Card</option>
        <option value="upi">UPI</option>
        <option value="netbanking">NetBanking</option>
    </select>

    <!-- UPI Fields -->
    <div id="upi-fields" class="payment-fields">
        <label>Enter UPI ID:</label>
        <input type="text" name="upi_id" placeholder="example@upi">
    </div>

    <!-- Card Fields -->
    <div id="card-fields" class="payment-fields">
        <label>Card Number:</label>
        <input type="text" name="card_number" maxlength="16" placeholder="1234 5678 9012 3456">
        <label>Expiry Date:</label>
        <input type="text" name="expiry" placeholder="MM/YY">
        <label>CVV:</label>
        <input type="password" name="cvv" maxlength="3" placeholder="123">
        <label>Cardholder Name:</label>
        <input type="text" name="card_name" placeholder="John Doe">
    </div>

    <!-- NetBanking Fields -->
    <div id="netbanking-fields" class="payment-fields">
        <label>Bank Name:</label>
        <input type="text" name="bank_name" placeholder="HDFC, SBI, ICICI...">
        <label>Username:</label>
        <input type="text" name="net_username" placeholder="Enter NetBanking Username">
        <label>Password:</label>
        <input type="password" name="net_password" placeholder="Enter NetBanking Password">
    </div>

    <button type="submit" class="btn">Pay ₹<?= $total_amount ?></button>
</form>

<a href="student_dashboard.php" class="btn back-btn">← Back to Dashboard</a>
</div>
</body>
</html>
