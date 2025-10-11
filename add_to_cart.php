<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$course_id = (int)($_GET['course_id'] ?? 0);

if(!$user_id || !$course_id){
    header("Location: student_dashboard.php");
    exit();
}

// Check if course already in cart
$stmt = $conn->prepare("SELECT * FROM cart WHERE user_id=? AND course_id=?");
$stmt->bind_param("ii",$user_id,$course_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    $stmt_insert = $conn->prepare("INSERT INTO cart (user_id, course_id) VALUES (?,?)");
    $stmt_insert->bind_param("ii",$user_id,$course_id);
    $stmt_insert->execute();
    $stmt_insert->close();
}

$stmt->close();
header("Location: student_dashboard.php");
exit();
