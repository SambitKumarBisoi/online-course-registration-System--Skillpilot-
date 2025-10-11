<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

// ✅ Only students can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ✅ Fetch only courses that are PAID
$stmt = $conn->prepare("
    SELECT c.id, c.course_name, c.description, c.duration, c.price, e.enrollment_date
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.payment_status='paid'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Courses - SkillPilot</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* ===== Styles unchanged from your previous my_courses.php ===== */
    :root {
      --bg-light: #f5f7fb;
      --bg-dark: linear-gradient(180deg, #0a0f1c, #1c2333);
      --text-light: #222;
      --text-dark: #f1f5ff;
      --card-light: #fff;
      --card-dark: #1e293b;
      --muted-light: #555;
      --muted-dark: #9db2ce;
      --accent: linear-gradient(90deg, #7b2ff7, #00c6ff);
    }

    body { background: var(--bg-light); color: var(--text-light); font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; transition: all 0.3s ease-in-out; }
    body.dark { background: var(--bg-dark); color: var(--text-dark); }

    .navbar { background: var(--accent); }
    .navbar-brand { font-weight: bold; color: white !important; }

    h2 { font-weight: 600; }

    .card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
      transition: transform 0.25s ease, background 0.3s;
      background: var(--card-light);
      color: var(--text-light);
    }
    body.dark .card { background: var(--card-dark); color: var(--text-dark); box-shadow: 0 6px 18px rgba(0,0,0,0.4); }
    .card:hover { transform: translateY(-6px); }

    .course-title { font-size: 1.2rem; font-weight: 600; }
    .course-info p { margin: 4px 0; font-size: 0.95rem; color: var(--muted-light); }
    body.dark .course-info p { color: var(--muted-dark); }

    .theme-toggle { cursor: pointer; background: var(--card-light); border-radius: 50%; padding: 8px; font-size: 18px; transition: all 0.3s; }
    body.dark .theme-toggle { background: var(--card-dark); color: #ffeb3b; }
  </style>
</head>
<body>
  
<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <a class="navbar-brand" href="student_dashboard.php">SkillPilot</a>
    <div id="themeToggle" class="theme-toggle">🌙</div>
  </div>
</nav>

<div class="container mt-4">

  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>📚 <?= htmlspecialchars($username) ?>'s Courses</h2>
      <a href="student_dashboard.php" class="btn btn-outline-primary">⬅ Back to Dashboard</a>
  </div>

  <div class="row">
  <?php if($result->num_rows > 0): ?>
    <?php while($course = $result->fetch_assoc()): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="course-title"><?= htmlspecialchars($course['course_name']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($course['description']) ?></p>
                    <div class="course-info">
                        <p><strong>⏳ Duration:</strong> <?= htmlspecialchars($course['duration']) ?></p>
                        <p><strong>💰 Price:</strong> ₹<?= htmlspecialchars($course['price']) ?></p>
                        <p><strong>📅 Enrolled On:</strong> <?= $course['enrollment_date'] ?></p>
                        <p><strong>✅ Status:</strong> Active</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="col-12 text-center text-muted">
        You haven't paid for any courses yet. Complete payment to access your courses.
    </div>
  <?php endif; ?>
  </div>

</div>

<script>
  const toggle = document.getElementById('themeToggle');
  const body = document.body;

  if(localStorage.getItem('skillpilot_theme') === 'dark') {
    body.classList.add('dark');
    toggle.textContent = "☀️";
  }

  toggle.addEventListener('click', () => {
    body.classList.toggle('dark');
    if(body.classList.contains('dark')){
      localStorage.setItem('skillpilot_theme', 'dark');
      toggle.textContent = "☀️";
    } else {
      localStorage.setItem('skillpilot_theme', 'light');
      toggle.textContent = "🌙";
    }
  });
</script>

</body>
</html>
