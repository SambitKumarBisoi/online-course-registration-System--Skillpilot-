<?php
session_start();
include 'db.php';

// Check student login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

// Search functionality
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM courses";
if($search){
    $query .= " WHERE course_name LIKE ?";
    $stmt = $conn->prepare($query);
    $searchTerm = "%$search%";
    $stmt->bind_param("s", $searchTerm);
} else {
    $stmt = $conn->prepare($query);
}
$stmt->execute();
$result = $stmt->get_result();

// Load student enrollments: course_id => payment_status
$enrollments_map = [];
$stmt_en = $conn->prepare("SELECT course_id, payment_status FROM enrollments WHERE user_id = ?");
if($stmt_en){
    $stmt_en->bind_param("i", $user_id);
    $stmt_en->execute();
    $res_en = $stmt_en->get_result();
    while($r = $res_en->fetch_assoc()){
        $enrollments_map[(int)$r['course_id']] = $r['payment_status']; // 'pending' or 'paid'
    }
    $stmt_en->close();
}

// Calculate total pending fee
$total_pending = 0.00;
$stmt_fee = $conn->prepare("
    SELECT COALESCE(SUM(c.price),0) AS total_pending
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.payment_status = 'pending'
");
if($stmt_fee){
    $stmt_fee->bind_param("i", $user_id);
    $stmt_fee->execute();
    $row_fee = $stmt_fee->get_result()->fetch_assoc();
    $total_pending = (float) ($row_fee['total_pending'] ?? 0);
    $stmt_fee->close();
}

// Load cart courses
$cart_courses = [];
$stmt_cart = $conn->prepare("SELECT c.id, c.course_name, c.price FROM cart ct JOIN courses c ON ct.course_id=c.id WHERE ct.user_id=?");
if($stmt_cart){
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $res_cart = $stmt_cart->get_result();
    while($r = $res_cart->fetch_assoc()){
        $cart_courses[] = $r;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SkillPilot — Student Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
  :root{ --trans:0.36s; --radius:12px; --shadow-soft:0 8px 20px rgba(2,6,23,0.12); }
  body{margin:0;font-family:'Poppins',system-ui;background:linear-gradient(180deg,#071428,#102a43);color:#e6f7ff;--bg:linear-gradient(180deg,#071428,#102a43);--surface:rgba(255,255,255,0.03);--muted:#9ad6ff;--text:#e6f7ff;--card-bg:rgba(255,255,255,0.05);--accent:linear-gradient(90deg,#00c6ff,#7b2ff7);--card-border:rgba(255,255,255,0.08);transition:background var(--trans),color var(--trans);}
  body.light{--bg:linear-gradient(180deg,#e9f8ff,#ffffff);--surface:#fff;--muted:#475569;--text:#07203a;--card-bg:#fff;--accent:linear-gradient(90deg,#42a5f5,#1e88e5);--card-border:rgba(6,21,37,0.08);background:var(--bg);color:var(--text);}
  *{box-sizing:border-box;text-decoration:none;}
  header{padding:20px 16px;position:sticky;top:0;background:var(--bg);z-index:100}
  .wrap{max-width:1200px;margin:0 auto;padding:0 16px}
  .brand{font-weight:800;font-size:20px;color:var(--text)}
  .nav-links{display:flex;gap:12px;margin-left:16px}
  .nav-links a{padding:8px 12px;border-radius:8px;color:var(--muted);font-weight:600;font-size:14px;transition:color var(--trans),transform var(--trans)}
  .nav-links a:hover{color:var(--text);transform:translateY(-2px)}
  .theme-toggle{cursor:pointer}
  .toggle-shell{width:60px;height:28px;border-radius:999px;padding:4px;background:var(--surface);border:1px solid var(--card-border);display:flex;align-items:center;position:relative;box-shadow:var(--shadow-soft)}
  .toggle-ball{width:24px;height:24px;border-radius:50%;background:#ffd54a;position:absolute;top:2px;left:2px;transition:transform var(--trans),background var(--trans);display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 6px 14px rgba(0,0,0,0.25)}
  body.light .toggle-ball{background:#fff;transform:translateX(32px);box-shadow:0 6px 14px rgba(6,21,37,0.08)}
  .icon-sun,.icon-moon{position:absolute;top:50%;transform:translateY(-50%);font-size:14px}
  .icon-sun{left:6px;display:none} body.light .icon-sun{display:inline}
  .icon-moon{right:6px;display:inline} body.light .icon-moon{display:none}
  .container{max-width:1200px;margin:40px auto;padding:0 16px}
  .search-box{margin-bottom:20px}
  .card{background:var(--card-bg);padding:20px;border-radius:var(--radius);box-shadow:var(--shadow-soft);border:1px solid var(--card-border);transition:transform var(--trans),box-shadow var(--trans);}
  .card:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 12px 24px rgba(0,0,0,0.3)}
  .card-title{font-size:18px;margin-bottom:10px;color:var(--text);font-weight:600}
  .card-text{color:var(--muted);font-size:14px;line-height:1.4}
  .btn{display:inline-block;padding:10px 14px;border-radius:8px;background:var(--accent);color:#fff;font-weight:700;border:none;cursor:pointer;transition:transform var(--trans),box-shadow var(--trans);}
  .btn:hover{transform:translateY(-3px) scale(1.05);box-shadow:0 8px 18px rgba(0,0,0,0.25)}
  .btn-secondary{padding:10px 14px;border-radius:8px;background:transparent;border:2px solid var(--muted);color:var(--muted);font-weight:700;transition:all var(--trans)}
  .btn-secondary:hover{background:var(--muted);color:#fff;transform:translateY(-2px)}
  .info-box{margin:20px 0;padding:16px;border-radius:8px;background:var(--surface);border:1px solid var(--card-border)}
  .success{color:#4caf50;font-weight:600}
  .pending{color:#ffb74d;font-weight:600}
  </style>
</head>
<body>
<header>
  <div class="wrap" style="display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="brand">SkillPilot</div>
      <nav>
        <div class="nav-links">
          <a href="index.php">Home</a>
          <a href="my_courses.php">My Courses</a>
          <a href="logout.php">Logout</a>
        </div>
      </nav>
    </div>
    <div class="theme-toggle" id="themeToggle">
      <div class="toggle-shell">
        <div class="toggle-ball"><span class="icon-sun">☀️</span><span class="icon-moon">🌙</span></div>
      </div>
    </div>
  </div>
</header>

<main class="container">
  <h2 style="margin-bottom:20px;">Welcome, <?= htmlspecialchars($username) ?></h2>

  <!-- Payment success message -->
  <?php if(isset($_GET['payment']) && $_GET['payment']==='success'): ?>
    <div class="info-box success">✅ Payment successful — thank you!</div>
  <?php endif; ?>

  <!-- Total pending + Go to Checkout -->
  <?php if($total_pending > 0 || count($cart_courses) > 0): ?>
    <div class="info-box">
      <?php if($total_pending > 0): ?>
        <p><strong>Total Pending:</strong> ₹<?= number_format($total_pending,2) ?></p>
        <p class="pending">You have pending payments.</p>
      <?php endif; ?>
      <?php if(count($cart_courses) > 0): ?>
        <p><strong>Cart Courses:</strong> <?= count($cart_courses) ?> items</p>
      <?php endif; ?>
      <a href="checkout.php" class="btn">💳 Go to Checkout</a>
    </div>
  <?php else: ?>
    <div class="info-box success">✅ No pending payments.</div>
  <?php endif; ?>

  <!-- Cart section -->
  <?php if(count($cart_courses) > 0): ?>
    <div class="info-box">
      <h4>🛒 Cart</h4>
      <ul>
        <?php foreach($cart_courses as $c): ?>
          <li><?= htmlspecialchars($c['course_name']) ?> — ₹<?= $c['price'] ?> 
              <a href="remove_from_cart.php?course_id=<?= $c['id'] ?>" style="color:#ff6961;">Remove</a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="GET" class="search-box">
    <input type="text" name="search" placeholder="🔍 Search courses..." 
      value="<?= htmlspecialchars($search) ?>" 
      style="width:100%;padding:12px;border-radius:8px;border:1px solid var(--card-border);background:var(--surface);color:var(--text);transition:box-shadow var(--trans)">
  </form>

  <div class="row" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">
    <?php while($course = $result->fetch_assoc()): 
        $cid = (int)$course['id'];
        $status = $enrollments_map[$cid] ?? null; 
    ?>
      <div class="card">
        <h5 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h5>
        <p class="card-text"><?= htmlspecialchars($course['description']) ?></p>
        <p><strong>Duration:</strong> <?= htmlspecialchars($course['duration']) ?></p>
        <p><strong>Price:</strong> ₹<?= htmlspecialchars($course['price']) ?></p>
        <p><strong>Seats:</strong> <?= $course['seats'] ?></p>

        <!-- Action area -->
        <?php if($status === null): ?>
            <a href="enroll.php?course_id=<?= $cid ?>" class="btn">Enroll</a>
            <a href="add_to_cart.php?course_id=<?= $cid ?>" class="btn-secondary">Add to Cart</a>
        <?php elseif($status === 'pending'): ?>
            <a href="checkout.php" class="btn">💳 Pay Now</a>
        <?php elseif($status === 'paid'): ?>
            <span class="btn-secondary success">✅ Paid</span>
        <?php else: ?>
            <a href="enroll.php?course_id=<?= $cid ?>" class="btn">Enroll</a>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  </div>
</main>

<footer class="wrap" style="text-align:center;margin:36px auto;color:var(--muted)">
  &copy; <?= date("Y") ?> SkillPilot — All rights reserved.
</footer>

<script>
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