<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT c.id, c.course_name, c.price 
        FROM cart ct 
        JOIN courses c ON ct.course_id = c.id 
        WHERE ct.user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$courses = [];
while($row = $result->fetch_assoc()){
    $courses[] = $row;
    $total += $row['price'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Cart</title>
  <style>
    body{font-family:Poppins;background:#f7faff;padding:40px;}
    .box{background:#fff;padding:20px;border-radius:12px;max-width:700px;margin:auto;box-shadow:0 6px 18px rgba(0,0,0,0.1);}
    h2{text-align:center}
    table{width:100%;border-collapse:collapse;margin-top:20px;}
    td,th{border-bottom:1px solid #ccc;padding:10px;text-align:left}
    .btn{padding:10px 14px;border-radius:6px;background:#007bff;color:#fff;text-decoration:none;margin:5px;display:inline-block}
    .btn-danger{background:#e74c3c}
  </style>
</head>
<body>
  <div class="box">
    <h2>🛒 My Cart</h2>
    <?php if(count($courses) > 0): ?>
      <table>
        <tr><th>Course</th><th>Price</th><th>Action</th></tr>
        <?php foreach($courses as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['course_name']) ?></td>
            <td>₹<?= $c['price'] ?></td>
            <td><a href="remove_from_cart.php?course_id=<?= $c['id'] ?>" class="btn btn-danger">Remove</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <h3 style="text-align:right;margin-top:10px">Total: ₹<?= $total ?></h3>
      <a href="payment.php?from=cart" class="btn">Proceed to Payment</a>
    <?php else: ?>
      <p>No courses in cart.</p>
      <a href="student_dashboard.php" class="btn">Browse Courses</a>
    <?php endif; ?>
  </div>
</body>
</html>
