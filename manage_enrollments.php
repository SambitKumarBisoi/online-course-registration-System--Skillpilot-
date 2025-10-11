<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();
include 'db.php';

// Admin only
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// ---------- Add Enrollment ----------
if(isset($_POST['add_enrollment'])){
    $user_id=intval($_POST['user_id']);
    $course_id=intval($_POST['course_id']);
    if($user_id && $course_id){
        $stmt=$conn->prepare("INSERT INTO enrollments(user_id,course_id) VALUES(?,?)");
        $stmt->bind_param("ii",$user_id,$course_id);
        $stmt->execute();
        $stmt->close();

        // Reduce seat
        $conn->query("UPDATE courses SET seats = seats - 1 WHERE id=$course_id AND seats>0");
    }
    header("Location: manage_enrollments.php");
    exit();
}

// ---------- Edit Enrollment ----------
if(isset($_POST['edit_enrollment'])){
    $user_id=intval($_POST['user_id']);
    $new_courses=$_POST['course_ids']??[];
    $new_courses=array_map('intval',$new_courses);

    // Old courses
    $old_courses=[];
    $res=$conn->query("SELECT course_id FROM enrollments WHERE user_id=$user_id");
    while($r=$res->fetch_assoc()) $old_courses[] = intval($r['course_id']);

    // Delete old enrollments
    $conn->query("DELETE FROM enrollments WHERE user_id=$user_id");

    // Seats management
    $added = array_diff($new_courses,$old_courses);
    $removed = array_diff($old_courses,$new_courses);

    foreach($removed as $cid){
        $conn->query("UPDATE courses SET seats = seats + 1 WHERE id=$cid");
    }

    if(!empty($new_courses)){
        $stmt = $conn->prepare("INSERT INTO enrollments(user_id,course_id) VALUES(?,?)");
        foreach($new_courses as $cid){
            $stmt->bind_param("ii",$user_id,$cid);
            $stmt->execute();
            if(in_array($cid,$added)){
                $conn->query("UPDATE courses SET seats = seats - 1 WHERE id=$cid AND seats>0");
            }
        }
        $stmt->close();
    }

    header("Location: manage_enrollments.php");
    exit();
}

// ---------- Bulk Delete ----------
if(isset($_POST['bulk_delete'])){
    $selected=$_POST['selected_users']??[];
    $selected=array_filter(array_map('intval',$selected),fn($v)=>$v>0);
    if(!empty($selected)){
        $in=implode(',',$selected);
        // Increase seats for removed enrollments
        $res=$conn->query("SELECT course_id FROM enrollments WHERE user_id IN ($in)");
        while($r=$res->fetch_assoc()){
            $cid=intval($r['course_id']);
            $conn->query("UPDATE courses SET seats = seats + 1 WHERE id=$cid");
        }
        $conn->query("DELETE FROM enrollments WHERE user_id IN ($in)");
    }
    header("Location: manage_enrollments.php");
    exit();
}

// ---------- Fetch Data ----------
$enrollments=$conn->query("
    SELECT u.id AS user_id,u.username,u.email,GROUP_CONCAT(c.course_name ORDER BY c.course_name SEPARATOR ', ') AS courses
    FROM enrollments e
    JOIN users u ON e.user_id=u.id
    JOIN courses c ON e.course_id=c.id
    GROUP BY u.id
    ORDER BY u.username ASC
");

$userCourses=[];
$resUC=$conn->query("SELECT user_id,GROUP_CONCAT(course_id) AS course_ids FROM enrollments GROUP BY user_id");
if($resUC){
    while($r=$resUC->fetch_assoc()){
        $userCourses[intval($r['user_id'])]=$r['course_ids']?array_map('intval',explode(',',$r['course_ids'])):[];
    }
    $resUC->free();
}

$students=$conn->query("SELECT id,username FROM users WHERE role='student' ORDER BY username");
$courses=$conn->query("SELECT id,course_name,seats FROM courses ORDER BY course_name");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SkillPilot — Manage Enrollments</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --radius:12px;
  --shadow-soft:0 8px 20px rgba(2,6,23,0.4);
  --bg-dark:linear-gradient(180deg,#0a192f,#112240);
  --card-dark:rgba(255,255,255,0.05);
  --text-dark:#e6f7ff;
  --muted-dark:#9ad6ff;
  --gradient-btn:linear-gradient(90deg,#00c6ff,#7b2ff7);
}
body{margin:0;font-family:'Poppins',sans-serif;background:var(--bg-dark);color:var(--text-dark);}
.navbar{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.05);}
.navbar h1{font-size:20px;font-weight:700;margin:0;}
.user{margin-right:12px;color:var(--muted-dark);}
.container{max-width:1100px;margin:30px auto;padding:0 16px;}
.card{background:var(--card-dark);padding:20px;border-radius:var(--radius);box-shadow:var(--shadow-soft);margin-bottom:20px;}
.btn{padding:8px 12px;border-radius:8px;font-size:14px;background:var(--gradient-btn);color:#fff;font-weight:600;border:none;cursor:pointer;text-decoration:none;margin-right:8px;}
.btn-danger{background:#dc3545;}
.btn-info{background:#0dcaf0;}
.btn-success{background:#198754;}
table{width:100%;border-collapse:collapse;margin-top:12px;}
th,td{padding:12px 14px;text-align:left;vertical-align:middle;}
th{background:rgba(255,255,255,0.03);font-weight:600;}
tr:nth-child(even){background:rgba(255,255,255,0.02);}
table tbody tr:hover{background: rgba(0,198,255,0.1); transition: background 0.2s ease;}

/* Modal */
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;z-index:9999;}
.modal-content{background: linear-gradient(145deg,#112240,#0a192f); color:var(--text-dark);padding:20px;border-radius:12px;width:380px;max-width:95%;max-height:80%;overflow-y:auto;box-shadow:0 10px 30px rgba(0,0,0,0.6);border:1px solid rgba(0,198,255,0.4);animation:fadeSlideUp 0.3s ease;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.modal-header h5{margin:0;font-size:16px;}
.close{cursor:pointer;font-weight:700;padding:6px 8px;border-radius:6px;background:transparent;border:none;color:var(--text-dark);}
.modal-content label{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;font-weight:600;}
.modal-content input[type="checkbox"]{transform:scale(1.1);accent-color:#00c6ff;margin-right:6px;}
.modal-content .btn-save{display:block;width:100%;margin-top:12px;}
.col-check{width:40px;text-align:center;}
.action-col{width:110px;text-align:center;}
@media(max-width:520px){.modal-content{width:320px;}.btn{padding:8px 10px;font-size:13px;}}
@keyframes fadeSlideUp{from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;}}
</style>
</head>
<body>

<div class="navbar">
  <h1>SkillPilot — Manage Enrollments</h1>
  <div style="display:flex;align-items:center;gap:10px;">
    <span class="user">👤 <?= htmlspecialchars($username) ?> (Admin)</span>
    <a href="admin_dashboard.php" class="btn btn-info">⬅ Dashboard</a>
    <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
  </div>
</div>

<div class="container">
  <div class="card">
    <h2 style="margin:0 0 12px 0">📊 Student Enrollments</h2>

    <!-- Add Enrollment -->
    <form method="POST" style="margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;">
      <select name="user_id" required>
        <option value="">-- Select Student --</option>
        <?php $students->data_seek(0); while($s=$students->fetch_assoc()): ?>
          <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['username']) ?></option>
        <?php endwhile; ?>
      </select>
      <select name="course_id" required>
        <option value="">-- Select Course --</option>
        <?php $courses->data_seek(0); while($c=$courses->fetch_assoc()): ?>
          <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?> (<?= $c['seats'] ?> seats)</option>
        <?php endwhile; ?>
      </select>
      <button type="submit" name="add_enrollment" class="btn btn-success">Add Enrollment</button>
    </form>

    <!-- Bulk delete -->
    <form method="POST" id="bulkForm">
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
        <button type="submit" name="bulk_delete" class="btn btn-danger" onclick="return confirm('Delete ALL courses for selected students?')">🗑 Delete Selected</button>
        <div style="margin-left:auto;color:rgba(255,255,255,0.6);font-size:14px;">Select students to delete all enrollments</div>
      </div>

      <table>
        <thead>
          <tr>
            <th class="col-check"><input type="checkbox" id="selectAll"></th>
            <th>#</th>
            <th>Student</th>
            <th>Email</th>
            <th>Courses</th>
            <th class="action-col">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $i=1;
        if($enrollments){
            $enrollments->data_seek(0);
            while($row=$enrollments->fetch_assoc()):
                $uid=(int)$row['user_id'];
        ?>
          <tr>
            <td class="col-check"><input type="checkbox" name="selected_users[]" value="<?= $uid ?>"></td>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['courses']) ?></td>
            <td class="action-col">
              <button type="button" class="btn btn-info" onclick="openModal(<?= $uid ?>)">✏️ Edit</button>
            </td>
          </tr>
        <?php endwhile; } else {
          echo '<tr><td colspan="6" style="text-align:center;padding:18px;color:rgba(255,255,255,0.6)">No enrollments found.</td></tr>';
        } ?>
        </tbody>
      </table>
    </form>
  </div>
</div>

<!-- Modals -->
<?php
$enrollments->data_seek(0);
while($row=$enrollments->fetch_assoc()):
  $uid=(int)$row['user_id'];
?>
<div class="modal" id="modal<?= $uid ?>">
  <div class="modal-content">
    <div class="modal-header">
      <h5>Edit Courses for <?= htmlspecialchars($row['username']) ?></h5>
      <button class="close" onclick="closeModal(<?= $uid ?>)">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="user_id" value="<?= $uid ?>">
      <?php
      $courses->data_seek(0);
      $selected_ids = $userCourses[$uid]??[];
      while($c=$courses->fetch_assoc()):
          $cid=(int)$c['id'];
          $checked = in_array($cid,$selected_ids)?'checked':'';
          $disabled = ($c['seats']<=0 && !in_array($cid,$selected_ids))?'disabled':'';
      ?>
      <label>
        <span>
          <input type="checkbox" name="course_ids[]" value="<?= $cid ?>" <?= $checked ?> <?= $disabled ?>>
          <?= htmlspecialchars($c['course_name']) ?>
        </span>
        <span style="font-size:12px;color:#9ad6ff;"><?= $c['seats'] ?> seats</span>
      </label>
      <?php endwhile; ?>
      <button type="submit" name="edit_enrollment" class="btn btn-success btn-save">Save Changes</button>
    </form>
  </div>
</div>
<?php endwhile; ?>

<script>
// Modals
function openModal(id){document.getElementById('modal'+id).style.display='flex';}
function closeModal(id){document.getElementById('modal'+id).style.display='none';}

// Select all
document.getElementById('selectAll').addEventListener('change',function(){
  document.querySelectorAll('input[name="selected_users[]"]').forEach(cb=>cb.checked=this.checked);
});

// click outside modal
document.addEventListener('click',function(e){
  document.querySelectorAll('.modal').forEach(modal=>{
    if(modal.style.display==='flex'){
      const content=modal.querySelector('.modal-content');
      if(content&&!content.contains(e.target)&&!e.target.matches('.btn-info')){
        modal.style.display='none';
      }
    }
  });
});
</script>
</body>
</html>
