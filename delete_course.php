<?php
session_start();
include 'db.php';

// Check admin login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// Get course_id from URL
$course_id = $_GET['course_id'] ?? 0;

if($course_id){
    // Delete course
    $stmt = $conn->prepare("DELETE FROM courses WHERE id=?");
    $stmt->bind_param("i", $course_id);

    if($stmt->execute()){
        $stmt->close();
        $conn->close();
        header("Location: admin_dashboard.php"); // Redirect back to dashboard
        exit();
    } else {
        echo "<p style='color:red;'>❌ Error deleting course: ".$stmt->error."</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Invalid course ID.</p>";
}
?>
