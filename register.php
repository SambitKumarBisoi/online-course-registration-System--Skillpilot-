<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {
        $success = "✅ Registration successful! <a href='login.php'>Login here</a>";
    } else {
        $error = "❌ Error: " . $stmt->error;
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
  <title>SkillPilot — Register</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
  /* === THEME SYSTEM === */
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
  }
  body.light {
    background:var(--bg-light);
    color:var(--text-light);
  }

  *{box-sizing:border-box;}
  a { text-decoration: none; }

  /* === THEME TOGGLE === */
  .theme-toggle{cursor:pointer;}
  .toggle-shell{width:60px;height:28px;border-radius:999px;padding:4px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);display:flex;align-items:center;position:relative;}
  .toggle-ball{width:24px;height:24px;border-radius:50%;background:#ffd54a;position:absolute;top:2px;left:2px;transition:transform var(--trans),background var(--trans);display:flex;align-items:center;justify-content:center;font-size:14px;}
  body.light .toggle-ball{background:#fff;transform:translateX(32px);}
  .icon-sun,.icon-moon{position:absolute;top:50%;transform:translateY(-50%);font-size:14px;}
  .icon-sun{left:6px;display:none;} body.light .icon-sun{display:inline;}
  .icon-moon{right:6px;display:inline;} body.light .icon-moon{display:none;}

  /* === CARD === */
  .card{
    background:var(--card-dark);
    padding:30px;
    border-radius:var(--radius);
    box-shadow:var(--shadow-soft);
    max-width:400px;
    margin:100px auto;
    text-align:center;
  }
  body.light .card{background:var(--card-light);}

  .card h2{margin-bottom:8px;}
  .card p{margin-bottom:20px;color:var(--muted-dark);}
  body.light .card p{color:var(--muted-light);}

  .form-group{text-align:left;margin-bottom:14px;}
  .form-group label{font-size:14px;font-weight:600;}
  .form-group input{
    width:100%;padding:10px;border-radius:8px;
    border:1px solid rgba(255,255,255,0.2);
    background:rgba(255,255,255,0.05);
    color:inherit;
  }
  body.light .form-group input{
    border:1px solid #ddd;background:#fff;color:#000;
  }

  /* === BUTTONS === */
  .btn{
    display:inline-block;width:100%;padding:10px;
    border-radius:8px;background:var(--gradient-btn);
    color:#fff;font-weight:700;border:none;cursor:pointer;
    transition:transform var(--trans);
  }
  .btn:hover{transform:translateY(-3px);}
  .btn-secondary{
    display:inline-block;width:100%;padding:10px;margin-top:10px;
    border-radius:8px;background:transparent;
    border:2px solid var(--outline-btn);color:var(--outline-btn);
    font-weight:700;cursor:pointer;transition:all var(--trans);
  }
  .btn-secondary:hover{background:var(--outline-btn);color:#fff;}

  .alert{padding:10px;border-radius:8px;margin-bottom:12px;}
  .alert-success{background:#4caf50;color:#fff;}
  .alert-error{background:#ff4d4f;color:#fff;}

  /* === NAVBAR === */
  header{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;position:sticky;top:0;z-index:10;}
  .brand{font-weight:800;font-size:20px;}
  .nav-left{display:flex;align-items:center;gap:14px;}
  .nav-left a, .nav-right a{padding:6px 10px;border-radius:8px;font-weight:600;font-size:14px;color:var(--muted-dark);}
  .nav-left a:hover, .nav-right a:hover{color:#fff;}
  .nav-right{display:flex;align-items:center;gap:14px;}
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <header>
    <div class="nav-left">
      <div class="brand">SkillPilot</div>
      <a href="index.php">Home</a>
    </div>
    <div class="nav-right">
      <div class="theme-toggle" id="themeToggle">
        <div class="toggle-shell">
          <div class="toggle-ball">
            <span class="icon-sun">☀️</span><span class="icon-moon">🌙</span>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- CARD -->
  <div class="card">
    <h2>Create Account</h2>
    <p>Join SkillPilot and start your learning journey 🚀</p>

    <?php if(isset($success)): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif(isset($error)): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Register</button>
    </form>
    <a href="login.php" class="btn-secondary">Already have an account? Login</a>
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
