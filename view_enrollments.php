<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// Fetch all enrollments
$result = $conn->query("
    SELECT e.id, u.username, u.email, c.course_name, e.enrollment_date
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">

<h2>All Enrollments</h2>
<a href="admin_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

<table class="table table-striped table-bordered">
<thead class="table-dark">
<tr>
<th>Student Name</th>
<th>Email</th>
<th>Course Name</th>
<th>Enrollment Date</th>
</tr>
</thead>
<tbody>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['username']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['course_name']) ?></td>
<td><?= $row['enrollment_date'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body>
</html>

<?php
session_start();
include 'db.php';

// ✅ Only admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch all enrollments
$sql = "SELECT e.id, u.username, u.email, c.course_name, e.enrollment_date 
        FROM enrollments e
        JOIN users u ON e.user_id = u.id
        JOIN courses c ON e.course_id = c.id
        ORDER BY e.enrollment_date DESC";
$result = $conn->query($sql);

// Debugging if query fails
if(!$result){
    die("Query failed: " . $conn->error);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>SkillPilot — Enrollments</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="theme-admin.css"> <!-- ✅ SkillPilot theme -->
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
  <h1>📚 SkillPilot — Admin</h1>
  <div style="display:flex;align-items:center;gap:10px;">
    <span class="user">👤 <?= htmlspecialchars($username) ?> (Admin)</span>
    <a href="admin_dashboard.php" class="btn btn-gradient">⬅ Dashboard</a>
    <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
      <div class="toggle-shell">
        <div class="toggle-ball">
          <span class="icon-sun">☀️</span>
          <span class="icon-moon">🌙</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="container">
  <div class="card fade-in" style="animation-delay:0.1s">
    <h2 class="card-title">📊 Student Enrollments</h2>
    <p class="subtitle">Below is the complete list of students and their enrolled courses.</p>

    <div class="table-container">
      <table class="styled-table hover">
        <thead>
          <tr>
            <th>#</th>
            <th>👨‍🎓 Student</th>
            <th>📧 Email</th>
            <th>📘 Course</th>
            <th>📅 Enrolled At</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($row['course_name']) ?></span></td>
              <td><?= htmlspecialchars($row['enrollment_date']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="theme.js"></script>
</body>
</html>
