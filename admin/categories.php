<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '📚');
        $color = trim($_POST['color'] ?? '#6C3AFF');
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO categories (name, icon, color) VALUES (?,?,?)");
            $stmt->bind_param("sss", $name, $icon, $color);
            $stmt->execute();
            $success = 'Category added!';
        } else $error = 'Name is required.';
    }
}

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM categories WHERE id = " . intval($_GET['delete']));
    header('Location: categories.php?msg=deleted');
    exit;
}

$categories = $conn->query("SELECT cat.*, COUNT(c.id) as course_count FROM categories cat LEFT JOIN courses c ON cat.id=c.category_id GROUP BY cat.id ORDER BY cat.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categories — Admin — Fintebit</title>
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
    <a href="users.php" class="sidebar-link"><span class="icon">👥</span> Users</a>
    <a href="enrollments.php" class="sidebar-link"><span class="icon">🎓</span> Enrollments</a>
    <a href="payments.php" class="sidebar-link"><span class="icon">💰</span> Payments</a>
    <a href="categories.php" class="sidebar-link active"><span class="icon">🏷️</span> Categories</a>
    <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">
    <a href="../logout.php" class="sidebar-link" style="color:var(--danger)"><span class="icon">🚪</span> Logout</a>
  </div>

  <div class="admin-main">
    <div class="page-header">
      <div><h1>Categories</h1><p>Organize courses by category</p></div>
    </div>

    <?php if($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
    <?php if(isset($_GET['msg'])): ?><div class="alert alert-success">✅ Category <?= $_GET['msg'] ?>.</div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
      <!-- Add Category -->
      <div class="admin-form">
        <h3 style="color:var(--white);margin-bottom:24px">Add New Category</h3>
        <form method="POST">
          <div class="form-group">
            <label>Category Name *</label>
            <input type="text" name="name" placeholder="e.g. Data Science" required>
          </div>
          <div class="form-group">
            <label>Icon (Emoji)</label>
            <input type="text" name="icon" placeholder="📊" maxlength="5">
          </div>
          <div class="form-group">
            <label>Color (HEX)</label>
            <input type="color" name="color" value="#6C3AFF">
          </div>
          <button type="submit" name="add" class="btn-submit">Add Category</button>
        </form>
      </div>

      <!-- Categories List -->
      <div class="table-card">
        <div class="table-header"><h3>All Categories (<?= $categories->num_rows ?>)</h3></div>
        <table>
          <thead>
            <tr><th>Icon</th><th>Name</th><th>Courses</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
            <tr>
              <td style="font-size:1.5rem"><?= $cat['icon'] ?></td>
              <td>
                <div style="font-weight:600;color:var(--white)"><?= htmlspecialchars($cat['name']) ?></div>
                <div style="font-size:0.75rem;font-family:monospace;color:var(--text-muted)"><?= $cat['color'] ?></div>
              </td>
              <td style="color:var(--white);font-weight:600"><?= $cat['course_count'] ?></td>
              <td>
                <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn-sm btn-sm-danger" onclick="return confirm('Delete category?')">Delete</a>
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
