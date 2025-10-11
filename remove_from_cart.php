<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$course_id = (int)($_GET['course_id'] ?? 0);

if(!$user_id || !$course_id){
    header("Location: student_dashboard.php");
    exit();
}

$stmt = $conn->prepare("DELETE FROM cart WHERE user_id=? AND course_id=?");
$stmt->bind_param("ii",$user_id,$course_id);
$stmt->execute();
$stmt->close();

header("Location: student_dashboard.php");
exit();
