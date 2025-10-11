<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $username, $hashedPassword, $role);

    if ($stmt->fetch()) {
        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            if($role === 'admin'){
                header("Location: admin_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Invalid email or password!";
    }

    $stmt->close();
    $conn->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SkillPilot — Login</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
  /* === THEME SYSTEM === */
  :root{ --trans:0.36s; --radius:12px; --shadow-soft:0 8px 20px rgba(2,6,23,0.12); }

  body{
    margin:0; font-family:'Poppins',system-ui; 
    background:linear-gradient(180deg,#071428,#102a43); color:#e6f7ff;
    --bg:linear-gradient(180deg,#071428,#102a43);
    --surface:rgba(255,255,255,0.03);
    --muted:#9ad6ff; --text:#e6f7ff;
    --card-bg:rgba(255,255,255,0.02);
    --accent:linear-gradient(90deg,#00c6ff,#7b2ff7);
    --stat-color:#00e5ff;
    --card-border:rgba(255,255,255,0.04);
    --cta-shadow:0 10px 30px rgba(0,0,0,0.6);
    transition:background var(--trans),color var(--trans);
  }
  body.light{
    --bg:linear-gradient(180deg,#e9f8ff,#ffffff);
    --surface:#fff; --muted:#475569; --text:#07203a;
    --card-bg:#fff; --accent:linear-gradient(90deg,#42a5f5,#1e88e5);
    --stat-color:#0d4b8f;
    --card-border:rgba(6,21,37,0.04);
    --cta-shadow:0 8px 24px rgba(6,21,37,0.06);
    background:var(--bg); color:var(--text);
  }

  *{box-sizing:border-box} 

  /* === REMOVE UNDERLINES FROM LINKS === */
  a { text-decoration: none; }
  a:hover { text-decoration: none; }

  header{padding:20px 16px;position:sticky;top:0}
  .wrap{max-width:1100px;margin:0 auto;padding:0 16px}
  .brand{font-weight:800;font-size:20px;color:var(--text)}
  .nav-links{display:flex;gap:12px;margin-left:16px}
  .nav-links a{padding:8px 12px;border-radius:8px;color:var(--muted);font-weight:600;font-size:14px}
  .nav-links a:hover{color:var(--text)}

  /* === THEME TOGGLE === */
  .theme-toggle{margin-left:auto;cursor:pointer}
  .toggle-shell{width:60px;height:28px;border-radius:999px;padding:4px;background:var(--surface);border:1px solid var(--card-border);display:flex;align-items:center;position:relative;box-shadow:var(--shadow-soft)}
  .toggle-ball{width:24px;height:24px;border-radius:50%;background:#ffd54a;position:absolute;top:2px;left:2px;transition:transform var(--trans),background var(--trans);display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 6px 14px rgba(0,0,0,0.25)}
  body.light .toggle-ball{background:#fff;transform:translateX(32px);box-shadow:0 6px 14px rgba(6,21,37,0.08)}
  .icon-sun,.icon-moon{position:absolute;top:50%;transform:translateY(-50%);font-size:14px}
  .icon-sun{left:6px;display:none} body.light .icon-sun{display:inline}
  .icon-moon{right:6px;display:inline} body.light .icon-moon{display:none}

  /* === LOGIN CARD === */
  .card{background:var(--card-bg);padding:30px;border-radius:var(--radius);
    box-shadow:var(--shadow-soft);border:1px solid var(--card-border);
    max-width:400px;margin:60px auto;text-align:center}
  .card h2{margin-bottom:16px;color:var(--text)}
  .form-group{text-align:left;margin-bottom:14px}
  .form-group label{font-size:14px;font-weight:600;color:var(--muted)}
  .form-group input{width:100%;padding:10px;border-radius:8px;border:1px solid var(--card-border);background:var(--surface);color:var(--text);margin-top:6px}
  
  /* === BUTTONS === */
  .btn{display:inline-block;width:100%;padding:10px;border-radius:8px;background:var(--accent);color:#fff;font-weight:700;border:none;cursor:pointer;box-shadow:var(--cta-shadow);transition:transform var(--trans)}
  .btn:hover{transform:translateY(-3px)}
  
  /* Secondary button (Register) */
  .btn-secondary{
    display:inline-block;width:100%;padding:10px;margin-top:10px;
    border-radius:8px;background:transparent;
    border:2px solid var(--stat-color);color:var(--stat-color);
    font-weight:700;cursor:pointer;transition:all var(--trans);
  }
  .btn-secondary:hover{background:var(--stat-color);color:#fff}

  .alert{padding:10px;border-radius:8px;margin-bottom:12px;background:#ff4d4f;color:#fff}
  footer{margin:36px auto;text-align:center;color:var(--muted);padding:8px}
  </style>
</head>
<body>
<header>
  <div class="wrap" style="display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="brand">SkillPilot</div>
      <nav><div class="nav-links"><a href="index.php">Home</a></div></nav>
    </div>
    <div class="theme-toggle" id="themeToggle">
      <div class="toggle-shell">
        <div class="toggle-ball">
          <span class="icon-sun">☀️</span><span class="icon-moon">🌙</span>
        </div>
      </div>
    </div>
  </div>
</header>

<main class="wrap">
  <div class="card">
    <h2>Login</h2>
    <?php if(isset($error)): ?>
      <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <!-- Register as a button -->
    <a href="register.php" class="btn-secondary">Create an Account</a>
  </div>
</main>

<footer class="wrap">
  &copy; <?= date("Y") ?> SkillPilot — All rights reserved.
</footer>

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
