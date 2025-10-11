<?php
session_start();
include 'db.php';

// ✅ Only admin access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// ✅ Support both ?id= and ?course_id=
$course_id = $_GET['id'] ?? $_GET['course_id'] ?? 0;
$course_id = (int)$course_id;

// ✅ Fetch course safely
$course = null;
if($course_id > 0){
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id=?");
    $stmt->bind_param("i",$course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = $result->fetch_assoc();
    $stmt->close();
}

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $name = $_POST['course_name'];
    $desc = $_POST['description'];
    $duration = $_POST['duration'];
    $price = $_POST['price'];
    $seats = $_POST['seats'];

    $stmt2 = $conn->prepare("UPDATE courses SET course_name=?, description=?, duration=?, price=?, seats=? WHERE id=?");
    $stmt2->bind_param("sssdis", $name, $desc, $duration, $price, $seats, $course_id);

    if($stmt2->execute()){
        $success = "✅ Course updated successfully!";
        // 🔁 Fetch the updated record again
        $stmt3 = $conn->prepare("SELECT * FROM courses WHERE id=?");
        $stmt3->bind_param("i", $course_id);
        $stmt3->execute();
        $result2 = $stmt3->get_result();
        $course = $result2->fetch_assoc();
        $stmt3->close();
    } else {
        $error = "❌ Error: " . $stmt2->error;
    }

    $stmt2->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Edit Course</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:linear-gradient(180deg,#0a192f,#112240);color:#e6f7ff;">
<div class="container mt-5 p-4" style="background:rgba(255,255,255,0.05);border-radius:12px;box-shadow:0 8px 20px rgba(2,6,23,0.4);">
    <h2 class="mb-4 text-center">✏️ Edit Course</h2>

    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <?php if($course): ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Course Name</label>
            <input type="text" name="course_name" class="form-control" 
                   value="<?= htmlspecialchars($course['course_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($course['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Duration</label>
            <input type="text" name="duration" class="form-control" 
                   value="<?= htmlspecialchars($course['duration']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Price (₹)</label>
            <input type="number" step="0.01" name="price" class="form-control" 
                   value="<?= htmlspecialchars($course['price']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Seats</label>
            <input type="number" name="seats" class="form-control" 
                   value="<?= htmlspecialchars($course['seats']) ?>" required>
        </div>

        <button class="btn btn-primary">💾 Update Course</button>
        <a href="admin_dashboard.php" class="btn btn-secondary">⬅ Back</a>
    </form>
    <?php else: ?>
        <div class="alert alert-warning">⚠️ No course found with ID <?= $course_id ?>.</div>
    <?php endif; ?>
</div>
</body>
</html>
