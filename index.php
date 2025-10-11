<?php
// index.php - SkillPilot homepage (single file)
// - Requires db.php in same folder that creates $conn (mysqli)
include 'db.php';
session_start();

// Fetch courses from DB
$sql = "SELECT * FROM courses ORDER BY id ASC";
$result = $conn->query($sql);

// Determine user login status
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$role = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SkillPilot — Learn & Grow</title>

  <!-- Google font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
  /* ===========================
     Base / Theme variables
     =========================== */
  :root{
    --trans-fast: 0.18s;
    --trans: 0.36s;
    --radius: 12px;
    --maxw: 1100px;
    --shadow-soft: 0 8px 20px rgba(2,6,23,0.12);
  }

  body{
    margin:0;
    font-family:'Poppins',system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;
    background: linear-gradient(180deg,#071428,#102a43);
    color: #e6f7ff;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
    transition: background var(--trans), color var(--trans);
    --bg: linear-gradient(180deg,#071428,#102a43);
    --surface: rgba(255,255,255,0.03);
    --muted: #9ad6ff;
    --text: #e6f7ff;
    --card-bg: rgba(255,255,255,0.02);
    --accent: linear-gradient(90deg,#00c6ff,#7b2ff7);
    --stat-color: #00e5ff;
    --card-border: rgba(255,255,255,0.04);
    --cta-shadow: 0 10px 30px rgba(0,0,0,0.6);
  }

  body.light{
    --bg: linear-gradient(180deg,#e9f8ff,#ffffff);
    --surface: #ffffff;
    --muted: #475569;
    --text: #07203a;
    --card-bg: #ffffff;
    --accent: linear-gradient(90deg,#42a5f5,#1e88e5);
    --stat-color: #0d4b8f;
    --card-border: rgba(6,21,37,0.04);
    --cta-shadow: 0 8px 24px rgba(6,21,37,0.06);
    color: var(--text);
    background: var(--bg);
  }

  *{box-sizing:border-box}
  a{color:inherit;text-decoration:none}
  header{ padding:20px 16px; text-align:center; background:transparent; position:sticky; top:0; z-index:40; }
  .wrap{ max-width:var(--maxw); margin:0 auto; padding:0 16px; }

  .brand { font-weight:800; font-size:20px; letter-spacing:.6px; color:var(--text); }
  nav { display:flex; align-items:center; gap:12px; padding:10px 0; width:100%; }
  .nav-links{ display:flex; gap:10px; margin-left:16px; align-items:center; }
  .nav-links a{ padding:8px 10px; border-radius:8px; color:var(--muted); font-weight:600; font-size:14px; }
  .nav-links a:hover{ color:var(--text); transform:translateY(-2px) }

  .theme-toggle { margin-left:auto; display:flex; align-items:center; gap:8px; cursor:pointer; user-select:none; position:relative; }
  .toggle-shell {
    width:60px; height:28px; border-radius:999px; padding:4px; display:flex; align-items:center;
    background:var(--surface); border:1px solid var(--card-border); box-shadow:var(--shadow-soft); transition:all var(--trans);
    position:relative;
  }
  .toggle-ball {
    width:24px; height:24px; border-radius:50%; background:#ffd54a; transition: transform var(--trans), background var(--trans);
    position:absolute; top:2px; left:2px; display:flex; align-items:center; justify-content:center; font-size:14px;
    box-shadow:0 6px 14px rgba(0,0,0,0.25);
  }
  body.light .toggle-ball { background:#fff; transform:translateX(32px); box-shadow:0 6px 14px rgba(6,21,37,0.08); }
  .icon-sun, .icon-moon { position:absolute; top:50%; transform:translateY(-50%); font-size:14px; }
  .icon-sun { left:6px; display:none; }
  body.light .icon-sun { display:inline; }
  .icon-moon { right:6px; display:inline; }
  body.light .icon-moon { display:none; }

  .hero{ margin:22px auto; padding:36px 18px; text-align:center; border-radius:14px; background: linear-gradient(180deg, transparent, rgba(255,255,255,0.01)); }
  body.light .hero{ background: linear-gradient(180deg,#f1fbff,#ffffff); box-shadow:var(--shadow-soft) }
  .hero h1{ font-size:36px; margin-bottom:10px; color:var(--text) }
  .hero p{ color:var(--muted); margin-bottom:16px; max-width:880px; margin-left:auto; margin-right:auto; }

  .cta {
    display:inline-block; padding:10px 20px; border-radius:999px; color:#fff; font-weight:700;
    background: var(--accent); box-shadow: var(--cta-shadow);
    transition: transform var(--trans);
  }
  .cta:hover{ transform:translateY(-4px) }

  .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin:18px auto; padding:10px; }
  .stat { background:var(--surface); border-radius:10px; padding:14px; text-align:center; border:1px solid var(--card-border); box-shadow: var(--shadow-soft); }
  .stat h3{ margin-bottom:6px; color:var(--stat-color); font-size:22px; }
  .stat p{ color:var(--muted); font-size:13px; }

  .testimonials { margin:22px auto; padding:8px; }
  .testimonials .title{ text-align:center; margin-bottom:12px; color:var(--text); font-size:20px; }
  .test-wrap{ position:relative; overflow:hidden; border-radius:12px; }
  .test-track{ display:flex; gap:12px; transition: transform 0.7s cubic-bezier(.2,.9,.2,1); }
  .testimonial{ flex:0 0 100%; padding:18px; background:var(--surface); border-radius:10px; box-shadow:var(--shadow-soft) }
  .testimonial p{ color:var(--muted); margin-bottom:10px }
  .testimonial strong{ color:var(--stat-color) }

  .courses { margin:26px auto; display:grid; gap:16px; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); }
  .card { background:var(--card-bg); border-radius:12px; padding:16px; border:1px solid var(--card-border); box-shadow:var(--shadow-soft); transform:translateY(18px); opacity:0; transition: transform 0.6s ease, opacity 0.6s ease; }
  body.light .card{ background:#fff }
  .card.visible { transform:translateY(0); opacity:1; }
  .card h4{ margin-bottom:6px; color:var(--stat-color) }
  .card p { color:var(--muted); font-size:14px; margin-bottom:8px }
  .card .meta{ font-size:13px; color:var(--muted); margin-bottom:10px }
    .enroll {
    display:inline-block;
    padding:8px 12px;
    border-radius:8px;
    color:#fff;
    background:var(--accent);
    font-weight:700;
    box-shadow: var(--cta-shadow);
    transition: transform 0.25s ease;
    position:relative;
    animation: enroll-pulse 2s infinite ease-in-out;
  }

  @keyframes enroll-pulse {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.03); }
    100% { transform: scale(1); }
  }

  .enroll:hover {
    animation: enroll-bounce 0.4s ease;
    transform: scale(1.05);
  }

  @keyframes enroll-bounce {
    0%   { transform: scale(1); }
    30%  { transform: scale(1.1); }
    50%  { transform: scale(0.95); }
    70%  { transform: scale(1.05); }
    100% { transform: scale(1); }
  }

  footer { margin:36px auto; text-align:center; color:var(--muted); padding:8px; }

  @media (max-width:980px){ .stats{ grid-template-columns:repeat(2,1fr) } .hero h1{ font-size:28px } }
  @media (max-width:600px){ .stats{ grid-template-columns: 1fr } .nav-links{ display:none } }

  </style>
</head>
<body>
  <!-- HEADER + NAV -->
  <header>
    <div class="wrap" style="display:flex; align-items:center; gap:12px; justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="brand" aria-hidden="true">SkillPilot</div>
        <nav aria-label="Main navigation">
          <div class="nav-links">
            <?php if($isLoggedIn): ?>
              <a href="dashboard.php">Dashboard</a>
              <a href="logout.php">Logout</a>
            <?php else: ?>
              <a href="register.php">Register</a>
              <a href="login.php">Login</a>
            <?php endif; ?>
          </div>
        </nav>
      </div>

      <!-- Theme toggle -->
      <div class="theme-toggle" id="themeToggle" role="button" tabindex="0" aria-label="Toggle theme">
        <div class="toggle-shell">
          <div class="toggle-ball">
            <span class="icon-sun">☀️</span>
            <span class="icon-moon">🌙</span>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- HERO -->
  <main>
    <section class="hero wrap" aria-label="Hero">
      <h1>Learn, Build, Succeed — Launch Your Career Today</h1>
      <p>Hands-on, project-driven courses designed to turn your skills into real-world results. Create, showcase, and grow with guidance from industry mentors.</p>
      <a class="cta" href="<?= $isLoggedIn ? 'dashboard.php' : 'register.php' ?>">Start Learning </a>
    </section>

    <!-- STATS -->
    <section class="stats wrap" aria-label="Statistics">
      <div class="stat"><h3 data-target="50000">0+</h3><p>Students enrolled</p></div>
      <div class="stat"><h3 data-target="120">0+</h3><p>Expert instructors</p></div>
      <div class="stat"><h3 data-target="300">0+</h3><p>Courses</p></div>
      <div class="stat"><h3 data-target="95">0%</h3><p>Success rate</p></div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="testimonials wrap" aria-label="Testimonials">
      <div class="title">What learners say</div>
      <div class="test-wrap" id="testWrap">
        <div class="test-track" id="testTrack">
          <div class="testimonial">
            <p>"SkillPilot's courses helped me build real projects and land interviews faster."</p>
            <strong>- Priya, Data Analyst</strong>
          </div>
          <div class="testimonial">
            <p>"The mentors were helpful; the course content was practical and up-to-date."</p>
            <strong>- Rahul, Full Stack Dev</strong>
          </div>
          <div class="testimonial">
            <p>"Great for students — concise courses and real-world tasks."</p>
            <strong>- Sneha, MBA Student</strong>
          </div>
        </div>
      </div>
    </section>

    <!-- COURSES -->
    <section class="courses wrap" aria-label="Available courses">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php $delay = 0.00; while($row = $result->fetch_assoc()): ?>
          <?php
            $id = (int)$row['id'];
            $name = htmlspecialchars($row['course_name']);
            $desc = htmlspecialchars($row['description']);
            $dur = htmlspecialchars($row['duration']);
            $price = number_format((float)$row['price'],2);
            $seats = (int)$row['seats'];
          ?>
          <article class="card" style="animation-delay:<?= htmlspecialchars($delay) ?>s">
            <h4><?= $name ?></h4>
            <p><?= strlen($desc) > 180 ? substr($desc,0,177).'...' : $desc ?></p>
            <div class="meta"><strong>Duration:</strong> <?= $dur ?> &nbsp; • &nbsp; <strong>Seats:</strong> <?= $seats ?></div>
            <div><strong>Price:</strong> ₹<?= $price ?></div>
            <div style="margin-top:12px">
              <a class="enroll" href="<?= $isLoggedIn ? 'enroll.php' : 'login.php' ?>">Enroll now</a>
            </div>
          </article>
        <?php $delay += 0.12; endwhile; ?>
      <?php else: ?>
        <div style="grid-column:1/-1; text-align:center; color:var(--muted); padding:14px">No courses yet — add some in admin panel.</div>
      <?php endif; ?>
    </section>
  </main>

  <footer class="wrap">
    &copy; <?= date("Y") ?> SkillPilot — All rights reserved.
  </footer>

  <script>
  (function(){
    const body = document.body;
    const toggle = document.getElementById('themeToggle');

    if(localStorage.getItem('skillpilot_theme') === 'light'){
      body.classList.add('light');
    }

    toggle.addEventListener('click', () => {
      body.classList.toggle('light');
      localStorage.setItem('skillpilot_theme', body.classList.contains('light') ? 'light' : 'dark');
    });

    toggle.addEventListener('keydown', (e) => {
      if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle.click(); }
    });
  })();

  (function(){
    const nodes = document.querySelectorAll('.stat h3');
    if(!nodes.length) return;

    const obs = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if(!entry.isIntersecting) return;
        const el = entry.target;
        const raw = el.getAttribute('data-target') || el.innerText.replace(/\D/g,'');
        const target = Number(raw) || 0;
        const isPercent = el.innerText.includes('%');
        const suffix = isPercent ? '%' : '+';
        const duration = 1200;
        const frame = 20;
        const steps = Math.max(6, Math.round(duration / frame));
        const increment = Math.max(1, Math.round(target / steps));
        let value = 0;

        const t = setInterval(() => {
          value += increment;
          if(value >= target) {
            el.innerText = (isPercent ? target + '%' : target + '+');
            clearInterval(t);
          } else {
            el.innerText = (isPercent ? value + '%' : value + '+');
          }
        }, frame);
        observer.unobserve(el);
      });
    }, { threshold:0.5 });

    nodes.forEach(n => obs.observe(n));
  })();

  (function(){
    const track = document.getElementById('testTrack');
    if(!track) return;
    const slides = Array.from(track.children);
    let idx = 0;
    let timer = null;
    const interval = 4200;

    function show(i) { track.style.transform = `translateX(-${i * 100}%)`; }
    function start() { if(timer) clearInterval(timer); timer = setInterval(()=>{ idx=(idx+1)%slides.length; show(idx); }, interval); }
    function stop() { if(timer){ clearInterval(timer); timer=null; } }
    if(slides.length>1) start();
    track.addEventListener('mouseenter', stop);
    track.addEventListener('mouseleave', start);
    const wrap = document.getElementById('testWrap');
    if(wrap){ wrap.addEventListener('mouseenter', stop); wrap.addEventListener('mouseleave', start); }
  })();

  (function(){
    const cards = document.querySelectorAll('.card');
    if(!cards.length) return;
    const obs = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if(entry.isIntersecting){ entry.target.classList.add('visible'); observer.unobserve(entry.target); }
      });
    }, { threshold: 0.12 });
    cards.forEach(c => obs.observe(c));
  })();
  </script>
</body>
</html>
