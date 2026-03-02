<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: courses.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) { header('Location: courses.php'); exit; }

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $duration = trim($_POST['duration'] ?? '');
    $level = trim($_POST['level'] ?? 'Beginner');
    $price = floatval($_POST['price'] ?? 0);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    $total_lessons = intval($_POST['total_lessons'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 4.5);
    $enrolled = intval($_POST['enrolled'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    if ($is_free) $price = 0;

    if (!$title || !$instructor) { $error = 'Title and instructor are required.'; }
    else {
        $stmt = $conn->prepare("UPDATE courses SET category_id=?,title=?,description=?,instructor=?,duration=?,level=?,price=?,is_free=?,total_lessons=?,rating=?,enrolled=?,status=? WHERE id=?");
        $stmt->bind_param("isssssdiddisi", $category_id, $title, $description, $instructor, $duration, $level, $price, $is_free, $total_lessons, $rating, $enrolled, $status, $id);
        if ($stmt->execute()) {
            $success = 'Course updated successfully!';
            $stmt2 = $conn->prepare("SELECT * FROM courses WHERE id=?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $course = $stmt2->get_result()->fetch_assoc();
        } else { $error = 'Update failed.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Course — Admin — Fintebit</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <div class="nav-container">
    <a href="../index.php" class="logo"><span class="logo-icon">⚡</span><span>Fin<span class="logo-accent">tebit</span></span></a>
    <div class="nav-links"><a href="../logout.php" class="btn-outline">Logout</a></div>
  </div>
</nav>

<div class="admin-layout">
  <div class="sidebar">
    <div style="padding:16px 20px"><div class="logo" style="font-size:1.3rem">⚡ Fin<span class="logo-accent">tebit</span></div></div>
    <a href="dashboard.php" class="sidebar-link"><span class="icon">📊</span> Dashboard</a>
    <a href="courses.php" class="sidebar-link active"><span class="icon">📚</span> Courses</a>
    <a href="add-course.php" class="sidebar-link"><span class="icon">➕</span> Add Course</a>
    <a href="users.php" class="sidebar-link"><span class="icon">👥</span> Users</a>
    <a href="enrollments.php" class="sidebar-link"><span class="icon">🎓</span> Enrollments</a>
    <a href="payments.php" class="sidebar-link"><span class="icon">💰</span> Payments</a>
    <a href="categories.php" class="sidebar-link"><span class="icon">🏷️</span> Categories</a>
    <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">
    <a href="../logout.php" class="sidebar-link" style="color:var(--danger)"><span class="icon">🚪</span> Logout</a>
  </div>

  <div class="admin-main">
    <div class="page-header">
      <div><h1>Edit Course</h1><p>Update course details and settings</p></div>
      <div style="display:flex;gap:12px">
        <a href="../course.php?id=<?= $id ?>" class="btn-outline-lg" style="font-size:0.9rem;padding:10px 20px" target="_blank">View Live</a>
        <a href="courses.php" class="btn-outline-lg" style="font-size:0.9rem;padding:10px 20px">← Back</a>
      </div>
    </div>

    <?php if($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="admin-form">
      <form method="POST">
        <div class="form-row">
          <div class="form-group" style="grid-column:1/-1">
            <label>Course Title *</label>
            <input type="text" name="title" value="<?= htmlspecialchars($course['title']) ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Instructor *</label>
            <input type="text" name="instructor" value="<?= htmlspecialchars($course['instructor']) ?>" required>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="">Select Category</option>
              <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
              <option value="<?= $cat['id'] ?>" <?= $course['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="4"><?= htmlspecialchars($course['description']) ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Level</label>
            <select name="level">
              <option value="Beginner" <?= $course['level']==='Beginner' ? 'selected' : '' ?>>Beginner</option>
              <option value="Intermediate" <?= $course['level']==='Intermediate' ? 'selected' : '' ?>>Intermediate</option>
              <option value="Advanced" <?= $course['level']==='Advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
          </div>
          <div class="form-group">
            <label>Duration</label>
            <input type="text" name="duration" value="<?= htmlspecialchars($course['duration']) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Price (USD)</label>
            <input type="number" name="price" step="0.01" min="0" value="<?= $course['price'] ?>">
          </div>
          <div class="form-group">
            <label>Total Lessons</label>
            <input type="number" name="total_lessons" min="0" value="<?= $course['total_lessons'] ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Rating (1-5)</label>
            <input type="number" name="rating" step="0.1" min="1" max="5" value="<?= $course['rating'] ?>">
          </div>
          <div class="form-group">
            <label>Enrolled Count</label>
            <input type="number" name="enrolled" min="0" value="<?= $course['enrolled'] ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active" <?= $course['status']==='active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $course['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-top:32px">
              <input type="checkbox" name="is_free" value="1" <?= $course['is_free'] ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--primary)">
              <span>Free Course</span>
            </label>
          </div>
        </div>
        <button type="submit" class="btn-submit">Update Course →</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
