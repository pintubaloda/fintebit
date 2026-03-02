<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

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

    if ($is_free) $price = 0;

    if (!$title || !$instructor) {
        $error = 'Title and instructor are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO courses (category_id, title, description, instructor, duration, level, price, is_free, total_lessons, rating, enrolled) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isssssdiddi", $category_id, $title, $description, $instructor, $duration, $level, $price, $is_free, $total_lessons, $rating, $enrolled);
        if ($stmt->execute()) {
            $success = 'Course added successfully!';
        } else {
            $error = 'Failed to add course.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Course — Admin — Fintebit</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <div class="nav-container">
    <a href="../index.php" class="logo"><span class="logo-icon">⚡</span><span>Fin<span class="logo-accent">tebit</span></span></a>
    <div class="nav-links">
      <a href="../logout.php" class="btn-outline">Logout</a>
    </div>
  </div>
</nav>

<div class="admin-layout">
  <div class="sidebar">
    <div style="padding:16px 20px;margin-bottom:8px">
      <div class="logo" style="font-size:1.3rem">⚡ Fin<span class="logo-accent">tebit</span></div>
    </div>
    <a href="dashboard.php" class="sidebar-link"><span class="icon">📊</span> Dashboard</a>
    <a href="courses.php" class="sidebar-link"><span class="icon">📚</span> Courses</a>
    <a href="add-course.php" class="sidebar-link active"><span class="icon">➕</span> Add Course</a>
    <a href="lessons.php" class="sidebar-link"><span class="icon">🎬</span> Lessons</a>
    <a href="users.php" class="sidebar-link"><span class="icon">👥</span> Users</a>
    <a href="enrollments.php" class="sidebar-link"><span class="icon">🎓</span> Enrollments</a>
    <a href="payments.php" class="sidebar-link"><span class="icon">💰</span> Payments</a>
    <a href="categories.php" class="sidebar-link"><span class="icon">🏷️</span> Categories</a>
    <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">
    <a href="../logout.php" class="sidebar-link" style="color:var(--danger)"><span class="icon">🚪</span> Logout</a>
  </div>

  <div class="admin-main">
    <div class="page-header">
      <div><h1>Add New Course</h1><p>Create a new course for Fintebit students</p></div>
      <a href="courses.php" class="btn-outline-lg" style="font-size:0.9rem;padding:10px 20px">← Back to Courses</a>
    </div>

    <?php if($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?> <a href="courses.php" style="color:var(--primary)">View all courses →</a></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="admin-form">
      <form method="POST">
        <div class="form-row">
          <div class="form-group" style="grid-column:1/-1">
            <label>Course Title *</label>
            <input type="text" name="title" placeholder="e.g. Advanced Python Programming" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Instructor Name *</label>
            <input type="text" name="instructor" placeholder="e.g. John Smith" value="<?= htmlspecialchars($_POST['instructor'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="">Select Category</option>
              <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
              <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Course Description</label>
          <textarea name="description" rows="4" placeholder="Describe what students will learn..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Level</label>
            <select name="level">
              <option value="Beginner" <?= (($_POST['level'] ?? '') === 'Beginner') ? 'selected' : '' ?>>Beginner</option>
              <option value="Intermediate" <?= (($_POST['level'] ?? '') === 'Intermediate') ? 'selected' : '' ?>>Intermediate</option>
              <option value="Advanced" <?= (($_POST['level'] ?? '') === 'Advanced') ? 'selected' : '' ?>>Advanced</option>
            </select>
          </div>
          <div class="form-group">
            <label>Duration (e.g. "20 hours")</label>
            <input type="text" name="duration" placeholder="e.g. 20 hours" value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Price (USD) — Leave 0 for free</label>
            <input type="number" name="price" step="0.01" min="0" placeholder="e.g. 49.99" value="<?= htmlspecialchars($_POST['price'] ?? '0') ?>">
          </div>
          <div class="form-group">
            <label>Total Lessons</label>
            <input type="number" name="total_lessons" min="0" placeholder="e.g. 30" value="<?= htmlspecialchars($_POST['total_lessons'] ?? '0') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Rating (1-5)</label>
            <input type="number" name="rating" step="0.1" min="1" max="5" placeholder="4.5" value="<?= htmlspecialchars($_POST['rating'] ?? '4.5') ?>">
          </div>
          <div class="form-group">
            <label>Enrolled Count (display)</label>
            <input type="number" name="enrolled" min="0" placeholder="0" value="<?= htmlspecialchars($_POST['enrolled'] ?? '0') ?>">
          </div>
        </div>

        <div class="form-group">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <input type="checkbox" name="is_free" value="1" <?= isset($_POST['is_free']) ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--primary)">
            <span>This is a FREE course (overrides price)</span>
          </label>
        </div>

        <button type="submit" class="btn-submit">Add Course →</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
