<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch all enrollments
$result = $conn->query("
    SELECT u.username, u.email, c.course_name, c.duration, c.price, e.enrollment_date
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrollment_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Enrollments</title>
</head>
<body>
    <h2><?php echo $username; ?> - All Enrollments</h2>
    <a href="admin_dashboard.php">Back to Dashboard</a> | <a href="logout.php">Logout</a>
    <table border="1" cellpadding="5">
        <tr>
            <th>Student Name</th>
            <th>Email</th>
            <th>Course Name</th>
            <th>Duration</th>
            <th>Price</th>
            <th>Enrollment Date</th>
        </tr>
        <?php while($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['username']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td><?php echo $row['course_name']; ?></td>
            <td><?php echo $row['duration']; ?></td>
            <td><?php echo $row['price']; ?></td>
            <td><?php echo $row['enrollment_date']; ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>
