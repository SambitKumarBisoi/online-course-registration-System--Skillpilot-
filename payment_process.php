<?php
session_start();
include 'db.php';

// ✅ Only logged-in students can pay
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$from = $_POST['from'] ?? ''; // cart or enrollments
$payment_method = $_POST['payment_method'] ?? '';
$upi_id = $_POST['upi_id'] ?? null;
$card_number = $_POST['card_number'] ?? null;
$netbank_user = $_POST['net_username'] ?? null;

// Extract last 4 digits of card (if any)
$card_last4 = $card_number ? substr(preg_replace('/\D/', '', $card_number), -4) : null;

// Collect courses for payment
$course_ids = [];
$course_names = [];
$total_amount = 0.00;

if($from === 'cart'){
    $stmt = $conn->prepare("SELECT c.id, c.course_name, c.price FROM cart ct JOIN courses c ON ct.course_id=c.id WHERE ct.user_id=?");
} else {
    $stmt = $conn->prepare("SELECT c.id, c.course_name, c.price FROM enrollments e JOIN courses c ON e.course_id=c.id WHERE e.user_id=? AND e.payment_status='pending'");
}

if(!$stmt){
    die("Database error (select courses): ".$conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $course_ids[] = $row['id'];
    $course_names[] = $row['course_name'];
    $total_amount += (float)$row['price'];
}
$stmt->close();

// Convert course arrays to string for payments table
$course_ids_str = implode(',', $course_ids);
$course_names_str = implode(', ', $course_names);

// ✅ Insert payment record
$status = 'paid';
$stmt2 = $conn->prepare("INSERT INTO payments (user_id, course_ids, amount, payment_method, upi_id, card_last4, netbank_user, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if(!$stmt2){
    die("Database error (insert payment): ".$conn->error);
}
$stmt2->bind_param("isdssss", $user_id, $course_ids_str, $total_amount, $payment_method, $upi_id, $card_last4, $netbank_user, $status);

if($stmt2->execute()){
    $stmt2->close();

    // ✅ Move cart courses to enrollments or update pending enrollments
    if($from === 'cart'){
        foreach($course_ids as $cid){
            $cid = (int)$cid;
            $enroll = $conn->prepare("
                INSERT INTO enrollments (user_id, course_id, payment_status)
                VALUES (?, ?, 'paid')
                ON DUPLICATE KEY UPDATE payment_status='paid'
            ");
            if($enroll){
                $enroll->bind_param("ii", $user_id, $cid);
                $enroll->execute();
                $enroll->close();
            }
        }

        // Clear cart
        $conn->query("DELETE FROM cart WHERE user_id=$user_id");
    } else {
        $conn->query("UPDATE enrollments SET payment_status='paid' WHERE user_id=$user_id AND payment_status='pending'");
    }

    // ✅ Deduct available seats for each course
    foreach($course_ids as $cid){
        $cid = (int)$cid;
        $conn->query("UPDATE courses SET seats = GREATEST(seats - 1, 0) WHERE id = $cid");
    }

    // Redirect after success
    header("Location: student_dashboard.php?payment=success");
    exit();

} else {
    echo "❌ Payment failed: " . $stmt2->error;
}
?>
