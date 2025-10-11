<?php
session_start();
include 'db.php';

// ✅ Only admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch all courses
$result = $conn->query("SELECT * FROM courses");
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SkillPilot — Admin Dashboard</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
  /* === THEME SYSTEM (same as login/register) === */
  :root {
    --trans:0.3s;
    --radius:12px;
    --shadow-soft:0 8px 20px rgba(2,6,23,0.4);

    --bg-dark:linear-gradient(180deg,#0a192f,#112240);
    --bg-light:linear-gradient(180deg,#e9f8ff,#ffffff);

    --card-dark:rgba(255,255,255,0.05);
    --card-light:#fff;

    --text-dark:#e6f7ff;
    --text-light:#07203a;

    --muted-dark:#9ad6ff;
    --muted-light:#475569;

    --gradient-btn:linear-gradient(90deg,#00c6ff,#7b2ff7);
    --outline-btn:#00e5ff;
  }

  body {
    margin:0;
    font-family:'Poppins',sans-serif;
    background:var(--bg-dark);
    color:var(--text-dark);
    transition:background var(--trans),color var(--trans);
    opacity:0; 
    animation:fadeInBody 0.6s ease forwards;
  }
  body.light {
    background:var(--bg-light);
    color:var(--text-light);
  }

  *{box-sizing:border-box;}
  a { text-decoration: none; }

  /* === NAVBAR === */
  .navbar {
    display:flex;justify-content:space-between;align-items:center;
    padding:16px 24px;
    background:rgba(255,255,255,0.05);
    border-bottom:1px solid rgba(255,255,255,0.1);
    animation:slideDown 0.6s ease forwards;
  }
  body.light .navbar {background:#fff;border-bottom:1px solid #ddd;}
  .navbar h1 {font-size:20px;font-weight:700;margin:0;}
  .navbar .user {margin-right:12px;color:var(--muted-dark);}
  body.light .navbar .user{color:var(--muted-light);}

  /* === THEME TOGGLE === */
  .theme-toggle{margin-left:16px;cursor:pointer;}
  .toggle-shell{width:60px;height:28px;border-radius:999px;padding:4px;
    background:rgba(255,255,255,0.1);
    border:1px solid rgba(255,255,255,0.2);
    display:flex;align-items:center;position:relative;}
  .toggle-ball{width:24px;height:24px;border-radius:50%;
    background:#ffd54a;position:absolute;top:2px;left:2px;
    transition:transform var(--trans),background var(--trans);
    display:flex;align-items:center;justify-content:center;font-size:14px;}
  body.light .toggle-ball{background:#fff;transform:translateX(32px);}
  .icon-sun,.icon-moon{position:absolute;top:50%;transform:translateY(-50%);font-size:14px;}
  .icon-sun{left:6px;display:none;} body.light .icon-sun{display:inline;}
  .icon-moon{right:6px;display:inline;} body.light .icon-moon{display:none;}

  /* === CONTAINER === */
  .container {max-width:1100px;margin:30px auto;padding:0 16px;}

  /* === CARDS === */
  .card{
    background:var(--card-dark);
    padding:20px;
    border-radius:var(--radius);
    box-shadow:var(--shadow-soft);
    margin-bottom:20px;
    opacity:0;
    transform:translateY(30px);
    animation:fadeUp 0.6s ease forwards;
  }
  body.light .card{background:var(--card-light);}
  .card h3 {margin-top:0;}

  /* Hover Animation */
  .card:hover {
    transform:translateY(-6px) scale(1.02);
    box-shadow:0 12px 30px rgba(0,0,0,0.35);
    transition:transform 0.3s ease, box-shadow 0.3s ease;
  }

  /* === BUTTONS === */
  .btn {
    display:inline-block;padding:8px 14px;border-radius:8px;
    background:var(--gradient-btn);color:#fff;
    font-weight:600;border:none;cursor:pointer;
    transition:transform var(--trans),box-shadow var(--trans);
  }
  .btn:hover{
    transform:translateY(-3px) scale(1.05);
    box-shadow:0 6px 15px rgba(123,47,247,0.4);
  }
  .btn-sm{padding:6px 10px;font-size:13px;}
  .btn-danger{background:#dc3545;}
  .btn-info{background:#0dcaf0;}
  .btn-success{background:#198754;}

  /* === GRID === */
  .course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }
  .course-grid .card {
    animation-delay: calc(var(--i, 0) * 0.15s);
  }

  /* === KEYFRAMES === */
  @keyframes fadeInBody {
    from{opacity:0;}
    to{opacity:1;}
  }
  @keyframes slideDown {
    from{transform:translateY(-50px);opacity:0;}
    to{transform:translateY(0);opacity:1;}
  }
  @keyframes fadeUp {
    from{opacity:0;transform:translateY(30px);}
    to{opacity:1;transform:translateY(0);}
  }
  </style>
</head>
<body>

  <!-- ✅ NAVBAR -->
  <div class="navbar">
    <h1>SkillPilot — Admin</h1>
    <div style="display:flex;align-items:center;gap:10px;">
      <span class="user">👤 <?= htmlspecialchars($username) ?> (Admin)</span>
      <a href="add_course.php" class="btn btn-success btn-sm">➕ Add Course</a>
      <a href="manage_enrollments.php" class="btn btn-success btn-sm">👥 Manage Students</a>
      <a href="payment_history.php" class="btn btn-info btn-sm">💳 Payment History</a> <!-- ✅ NEW LINK -->
      <a href="logout.php" class="btn btn-danger btn-sm">🚪 Logout</a>
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

  <!-- ✅ MAIN CONTENT -->
  <div class="container">
    <div class="card" style="animation-delay:0.1s">
      <h2>Welcome, <?= htmlspecialchars($username) ?> 🎓</h2>
      <p>Manage courses, students, and track enrollments here.</p>
    </div>

    <h3>📚 All Courses</h3>
    <div class="course-grid">
      <?php 
      $i = 1;
      while($course = $result->fetch_assoc()): ?>
        <div class="card" style="--i:<?= $i ?>;">
          <h3><?= htmlspecialchars($course['course_name']) ?></h3>
          <p><?= htmlspecialchars($course['description']) ?></p>
          <p><strong>Duration:</strong> <?= htmlspecialchars($course['duration']) ?></p>
          <p><strong>Price:</strong> ₹<?= htmlspecialchars($course['price']) ?></p>
          <p><strong>Seats:</strong> <?= $course['seats'] ?></p>
          <div style="margin-top:10px;">
            <a href="edit_course.php?course_id=<?= $course['id'] ?>" class="btn btn-info btn-sm">✏️ Edit</a>
            <a href="delete_course.php?course_id=<?= $course['id'] ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Are you sure you want to delete this course?')">🗑 Delete</a>
          </div>
        </div>
      <?php $i++; endwhile; ?>
    </div>
  </div>

<script>
/* === THEME TOGGLE SCRIPT === */
(function(){
  const body=document.body;
  const toggle=document.getElementById('themeToggle');
  if(localStorage.getItem('skillpilot_theme')==='light'){ body.classList.add('light'); }
  toggle.addEventListener('click',()=>{ 
    body.classList.toggle('light');
    localStorage.setItem('skillpilot_theme',body.classList.contains('light')?'light':'dark');
  });
})();
</script>
</body>
</html>
