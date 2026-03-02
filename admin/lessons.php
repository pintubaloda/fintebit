<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

$success = $error = '';
$courses = $conn->query("SELECT id, title FROM courses ORDER BY title");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = intval($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $order_num = intval($_POST['order_num'] ?? 1);
    $is_preview = isset($_POST['is_preview']) ? 1 : 0;

    if (!$course_id || !$title) { $error = 'Course and title are required.'; }
    else {
        $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content, duration, order_num, is_preview) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssii", $course_id, $title, $content, $duration, $order_num, $is_preview);
        if ($stmt->execute()) {
            $conn->query("UPDATE courses SET total_lessons = (SELECT COUNT(*) FROM lessons WHERE course_id = $course_id) WHERE id = $course_id");
            $success = 'Lesson added!';
        } else $error = 'Failed to add lesson.';
    }
}

if (isset($_GET['delete'])) {
    $lid = intval($_GET['delete']);
    $lesson = $conn->query("SELECT course_id FROM lessons WHERE id=$lid")->fetch_assoc();
    $conn->query("DELETE FROM lessons WHERE id=$lid");
    if ($lesson) $conn->query("UPDATE courses SET total_lessons = (SELECT COUNT(*) FROM lessons WHERE course_id = {$lesson['course_id']}) WHERE id = {$lesson['course_id']}");
    header('Location: lessons.php');
    exit;
}

$filter_course = intval($_GET['course'] ?? 0);
if ($filter_course) {
    $lessons = $conn->prepare("SELECT l.*, c.title as course_title FROM lessons l JOIN courses c ON l.course_id=c.id WHERE l.course_id=? ORDER BY l.order_num");
    $lessons->bind_param("i", $filter_course);
} else {
    $lessons = $conn->prepare("SELECT l.*, c.title as course_title FROM lessons l JOIN courses c ON l.course_id=c.id ORDER BY c.title, l.order_num");
}
$lessons->execute();
$lessonsResult = $lessons->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lessons — Admin — Fintebit</title>
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
    <a href="courses.php" class="sidebar-link"><span class="icon">📚</span> Courses</a>
    <a href="add-course.php" class="sidebar-link"><span class="icon">➕</span> Add Course</a>
    <a href="lessons.php" class="sidebar-link active"><span class="icon">🎬</span> Lessons</a>
    <a href="users.php" class="sidebar-link"><span class="icon">👥</span> Users</a>
    <a href="enrollments.php" class="sidebar-link"><span class="icon">🎓</span> Enrollments</a>
    <a href="payments.php" class="sidebar-link"><span class="icon">💰</span> Payments</a>
    <a href="categories.php" class="sidebar-link"><span class="icon">🏷️</span> Categories</a>
    <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">
    <a href="../logout.php" class="sidebar-link" style="color:var(--danger)"><span class="icon">🚪</span> Logout</a>
  </div>

  <div class="admin-main">
    <div class="page-header">
      <div><h1>Lessons</h1><p>Manage course lessons and content</p></div>
    </div>

    <?php if($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:350px 1fr;gap:24px;align-items:start">
      <div class="admin-form">
        <h3 style="color:var(--white);margin-bottom:20px">Add New Lesson</h3>
        <form method="POST">
          <div class="form-group">
            <label>Course *</label>
            <select name="course_id" required>
              <option value="">Select Course</option>
              <?php $courses->data_seek(0); while($c = $courses->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Lesson Title *</label>
            <input type="text" name="title" placeholder="e.g. Introduction to Variables" required>
          </div>
          <div class="form-group">
            <label>Content / Description</label>
            <textarea name="content" rows="3" placeholder="Lesson description..."></textarea>
          </div>
          <div class="form-group">
            <label>Duration</label>
            <input type="text" name="duration" placeholder="e.g. 15 min">
          </div>
          <div class="form-group">
            <label>Order Number</label>
            <input type="number" name="order_num" min="1" value="1">
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="is_preview" style="width:16px;height:16px;accent-color:var(--primary)">
              Free Preview (visible without enrollment)
            </label>
          </div>
          <button type="submit" class="btn-submit">Add Lesson</button>
        </form>
      </div>

      <div class="table-card">
        <div class="table-header">
          <h3>All Lessons (<?= $lessonsResult->num_rows ?>)</h3>
          <form method="GET" style="display:flex;gap:8px">
            <select name="course" onchange="this.form.submit()" style="padding:8px;background:var(--dark3);border:1px solid var(--border);border-radius:8px;color:var(--white);font-family:inherit">
              <option value="">All Courses</option>
              <?php $courses->data_seek(0); while($c = $courses->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= $filter_course==$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars(substr($c['title'],0,35)) ?></option>
              <?php endwhile; ?>
            </select>
          </form>
        </div>
        <table>
          <thead>
            <tr><th>#</th><th>Title</th><th>Course</th><th>Duration</th><th>Preview</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php while($l = $lessonsResult->fetch_assoc()): ?>
            <tr>
              <td style="color:var(--text-muted)"><?= $l['order_num'] ?></td>
              <td style="color:var(--white);font-weight:500;font-size:0.9rem"><?= htmlspecialchars($l['title']) ?></td>
              <td style="color:var(--text-muted);font-size:0.8rem;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($l['course_title']) ?></td>
              <td style="color:var(--text-muted)"><?= htmlspecialchars($l['duration'] ?? '-') ?></td>
              <td><?= $l['is_preview'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-danger">No</span>' ?></td>
              <td>
                <a href="lessons.php?delete=<?= $l['id'] ?>" class="btn-sm btn-sm-danger" onclick="return confirm('Delete lesson?')">Delete</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
