<?php
session_start();
include 'db.php';

// ✅ Only admin can access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// ✅ Handle add course
if(isset($_POST['add_course'])){
    $name = $_POST['course_name'];
    $desc = $_POST['description'];
    $duration = $_POST['duration'];
    $price = $_POST['price'];
    $seats = $_POST['seats'];

    $stmt = $conn->prepare("INSERT INTO courses (course_name, description, duration, price, seats) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdi", $name, $desc, $duration, $price, $seats);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>SkillPilot — Add Course</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --trans:0.3s; --radius:12px; --shadow-soft:0 8px 20px rgba(2,6,23,0.4);
  --bg-dark:linear-gradient(180deg,#0a192f,#112240); --bg-light:linear-gradient(180deg,#e9f8ff,#ffffff);
  --card-dark:rgba(255,255,255,0.05); --card-light:#fff;
  --text-dark:#e6f7ff; --text-light:#07203a;
  --muted-dark:#9ad6ff; --muted-light:#475569;
  --gradient-btn:linear-gradient(90deg,#00c6ff,#7b2ff7);
}
body{margin:0;font-family:'Poppins',sans-serif;background:var(--bg-dark);color:var(--text-dark);transition:background var(--trans),color var(--trans);}
body.light{background:var(--bg-light);color:var(--text-light);}
.navbar{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;background:rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.1);}
body.light .navbar{background:#fff;border-bottom:1px solid #ddd;}
.navbar h1{font-size:20px;font-weight:700;margin:0;}
.user{margin-right:12px;color:var(--muted-dark);}
body.light .user{color:var(--muted-light);}
.container{max-width:700px;margin:50px auto;padding:0 16px;}
.card{background:var(--card-dark);padding:20px;border-radius:var(--radius);box-shadow:var(--shadow-soft);}
body.light .card{background:var(--card-light);}
label{display:block;margin:10px 0 5px;}
input, textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:10px;font-family:'Poppins',sans-serif;}
textarea{resize:vertical;}
.btn{padding:8px 16px;border-radius:8px;font-size:14px;background:var(--gradient-btn);color:#fff;font-weight:600;border:none;cursor:pointer;text-decoration:none;margin-right:5px;}
.btn-info{background:#0dcaf0;}
.btn-success{background:#198754;}
.btn-danger{background:#dc3545;}
/* Toggle icons inside ball */
.toggle-ball span{position:absolute;font-size:12px;}
.icon-sun{left:4px;top:50%;transform:translateY(-50%);}
.icon-moon{right:4px;top:50%;transform:translateY(-50%);}
</style>
</head>
<body>

<div class="navbar">
  <h1>SkillPilot — Add Course</h1>
  <div style="display:flex;align-items:center;gap:10px;">
    <span class="user">👤 <?= htmlspecialchars($username) ?> (Admin)</span>
    <a href="admin_dashboard.php" class="btn btn-info">⬅ Dashboard</a>
    <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
    <!-- ✅ Theme Toggle -->
    <div class="theme-toggle" id="themeToggle" style="cursor:pointer;">
      <div class="toggle-shell" style="width:60px;height:28px;border-radius:999px;padding:4px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);position:relative;">
        <div class="toggle-ball" id="toggleBall" style="width:24px;height:24px;border-radius:50%;background:#ffd54a;position:absolute;top:2px;left:2px;display:flex;align-items:center;justify-content:center;font-size:14px;transition: transform 0.3s ease, background 0.3s ease;">
          <span class="icon-sun">☀️</span>
          <span class="icon-moon">🌙</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <div class="card">
    <h2>➕ Add New Course</h2>
    <form method="POST">
      <label>Course Name</label>
      <input type="text" name="course_name" required>

      <label>Description</label>
      <textarea name="description" rows="4" required></textarea>

      <label>Duration (e.g., 4 weeks)</label>
      <input type="text" name="duration" required>

      <label>Price (₹)</label>
      <input type="number" name="price" step="0.01" required>

      <label>Seats</label>
      <input type="number" name="seats" required>

      <button type="submit" name="add_course" class="btn btn-success">Add Course</button>
    </form>
  </div>
</div>

<script>
const body=document.body;
const toggle=document.getElementById('themeToggle');
const ball=document.getElementById('toggleBall');
const sun=ball.querySelector('.icon-sun');
const moon=ball.querySelector('.icon-moon');

function setTheme(isLight){
  if(isLight){
    body.classList.add('light');
    ball.style.transform='translateX(32px)';
    sun.style.display='inline';
    moon.style.display='none';
    localStorage.setItem('skillpilot_theme','light');
  } else {
    body.classList.remove('light');
    ball.style.transform='translateX(0)';
    sun.style.display='none';
    moon.style.display='inline';
    localStorage.setItem('skillpilot_theme','dark');
  }
}

setTheme(localStorage.getItem('skillpilot_theme')==='light');
toggle.addEventListener('click',()=>{ setTheme(!body.classList.contains('light')); });
</script>

</body>
</html>