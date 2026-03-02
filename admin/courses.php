<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin(); requireAdmin();

$msg = '';
// Handle delete
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM courses WHERE id=$did");
    $msg = 'Course deleted.';
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitize($conn, $_POST['title'] ?? '');
    $desc = sanitize($conn, $_POST['description'] ?? '');
    $category = sanitize($conn, $_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    $level = sanitize($conn, $_POST['level'] ?? 'Beginner');
    $duration = sanitize($conn, $_POST['duration'] ?? '');
    $lessons = (int)($_POST['lessons'] ?? 0);
    $instructor = sanitize($conn, $_POST['instructor'] ?? '');
    $icon = sanitize($conn, $_POST['icon'] ?? 'fas fa-book');
    $color = sanitize($conn, $_POST['image_color'] ?? '#6366f1');
    
    if ($id) {
        $conn->query("UPDATE courses SET title='$title',description='$desc',category='$category',price=$price,is_free=$is_free,level='$level',duration='$duration',lessons=$lessons,instructor='$instructor',icon='$icon',image_color='$color' WHERE id=$id");
        $msg = 'Course updated!';
    } else {
        $conn->query("INSERT INTO courses (title,description,category,price,is_free,level,duration,lessons,instructor,icon,image_color) VALUES ('$title','$desc','$category',$price,$is_free,'$level','$duration',$lessons,'$instructor','$icon','$color')");
        $msg = 'Course added!';
    }
}

$editCourse = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editCourse = $conn->query("SELECT * FROM courses WHERE id=$eid")->fetch_assoc();
}

$courses = $conn->query("SELECT c.*, COUNT(e.id) as enrollment_count FROM courses c LEFT JOIN enrollments e ON c.id=e.course_id GROUP BY c.id ORDER BY c.id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Courses – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#6366f1;--dark:#0f172a;--dark2:#1e293b;--border:#334155;--text:#e2e8f0;--text-muted:#94a3b8;--success:#10b981;--accent:#f59e0b;--red:#ef4444;}
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--text);display:flex;min-height:100vh}
.sidebar{width:240px;background:var(--dark2);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0;padding:1.5rem 1rem}
.logo{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,#6366f1,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none;display:block;margin-bottom:0.3rem}
.logo span{-webkit-text-fill-color:#f59e0b}
.admin-badge{display:inline-block;background:rgba(239,68,68,0.15);color:var(--red);font-size:0.7rem;font-weight:700;padding:0.2rem 0.6rem;border-radius:4px;margin-bottom:1.5rem}
.nav-item{display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1rem;border-radius:10px;text-decoration:none;color:var(--text-muted);font-size:0.9rem;font-weight:500;margin-bottom:0.2rem;transition:all 0.2s}
.nav-item:hover,.nav-item.active{background:rgba(99,102,241,0.15);color:var(--primary)}
.nav-item i{width:18px;text-align:center}
.main{margin-left:240px;flex:1;padding:2rem}
.page-title{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:2rem}
.card{background:var(--dark2);border:1px solid var(--border);border-radius:16px;padding:1.5rem;margin-bottom:1.5rem}
.card h2{font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1.5rem}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.form-group{margin-bottom:1rem}
label{display:block;font-size:0.85rem;font-weight:600;margin-bottom:0.5rem;color:var(--text-muted)}
input,select,textarea{width:100%;padding:0.8rem 1rem;background:rgba(15,23,42,0.8);border:1.5px solid var(--border);border-radius:10px;color:var(--text);font-size:0.9rem;font-family:'Inter',sans-serif}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--primary)}
textarea{resize:vertical;min-height:80px}
.btn{display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.4rem;border-radius:8px;font-weight:600;text-decoration:none;cursor:pointer;border:none;font-size:0.9rem;transition:all 0.3s;font-family:'Inter',sans-serif}
.btn-primary{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff}
.btn-danger{background:rgba(239,68,68,0.15);color:var(--red);border:1px solid rgba(239,68,68,0.3)}
.btn-edit{background:rgba(99,102,241,0.15);color:var(--primary);border:1px solid rgba(99,102,241,0.3)}
.table{width:100%;border-collapse:collapse}
.table th{text-align:left;padding:0.6rem;font-size:0.8rem;color:var(--text-muted);font-weight:600;border-bottom:1px solid var(--border)}
.table td{padding:0.8rem 0.6rem;font-size:0.85rem;border-bottom:1px solid rgba(51,65,85,0.5)}
.badge{padding:0.2rem 0.6rem;border-radius:50px;font-size:0.75rem;font-weight:600}
.badge-free{background:rgba(16,185,129,0.15);color:var(--success)}
.badge-paid{background:rgba(245,158,11,0.15);color:var(--accent)}
.success-msg{background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);color:var(--success);padding:1rem;border-radius:10px;margin-bottom:1.5rem}
.checkbox-wrap{display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem}
.checkbox-wrap input{width:auto}
</style>
</head>
<body>
<aside class="sidebar">
    <a href="../index.php" class="logo">Fin<span>tebit</span></a>
    <div class="admin-badge">ADMIN</div>
    <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="courses.php" class="nav-item active"><i class="fas fa-book"></i> Courses</a>
    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
    <a href="enrollments.php" class="nav-item"><i class="fas fa-clipboard-list"></i> Enrollments</a>
    <a href="../logout.php" class="nav-item" style="color:var(--red);margin-top:2rem"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <h1 class="page-title">📚 Manage Courses</h1>
    <?php if ($msg): ?><div class="success-msg"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
    
    <div class="card">
        <h2><?= $editCourse ? '✏️ Edit Course' : '➕ Add New Course' ?></h2>
        <form method="POST">
            <?php if ($editCourse): ?><input type="hidden" name="id" value="<?= $editCourse['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Course Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($editCourse['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Instructor</label>
                    <input type="text" name="instructor" value="<?= htmlspecialchars($editCourse['instructor'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach (['Web Dev','Programming','AI/ML','Data Science','Database','Cloud','Design','Security','Marketing','Spreadsheet'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($editCourse['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Level</label>
                    <select name="level">
                        <?php foreach (['Beginner','Intermediate','Advanced'] as $l): ?>
                        <option value="<?= $l ?>" <?= ($editCourse['level'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price (USD)</label>
                    <input type="number" name="price" step="0.01" value="<?= $editCourse['price'] ?? '0.00' ?>">
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 10 hours" value="<?= htmlspecialchars($editCourse['duration'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Number of Lessons</label>
                    <input type="number" name="lessons" value="<?= $editCourse['lessons'] ?? '0' ?>">
                </div>
                <div class="form-group">
                    <label>Icon Class (Font Awesome)</label>
                    <input type="text" name="icon" value="<?= htmlspecialchars($editCourse['icon'] ?? 'fas fa-book') ?>">
                </div>
                <div class="form-group">
                    <label>Accent Color</label>
                    <input type="color" name="image_color" value="<?= $editCourse['image_color'] ?? '#6366f1' ?>" style="height:42px">
                </div>
                <div class="form-group" style="display:flex;align-items:end">
                    <div class="checkbox-wrap">
                        <input type="checkbox" name="is_free" id="is_free" <?= ($editCourse['is_free'] ?? 0) ? 'checked' : '' ?>>
                        <label for="is_free" style="color:var(--success);margin:0">Free Course</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($editCourse['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $editCourse ? '<i class="fas fa-save"></i> Update Course' : '<i class="fas fa-plus"></i> Add Course' ?>
            </button>
            <?php if ($editCourse): ?>
            <a href="courses.php" class="btn" style="background:transparent;border:1px solid var(--border);margin-left:0.5rem">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="card">
        <h2>📋 All Courses (<?= count($courses) ?>)</h2>
        <div style="overflow-x:auto">
        <table class="table">
            <tr><th>Course</th><th>Category</th><th>Level</th><th>Type</th><th>Price</th><th>Enrollments</th><th>Actions</th></tr>
            <?php foreach ($courses as $c):
            $is_free = $c['is_free'] || $c['price'] == 0;
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:0.5rem">
                        <div style="width:32px;height:32px;border-radius:8px;background:<?= $c['image_color'] ?>22;color:<?= $c['image_color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1rem"><i class="<?= $c['icon'] ?>"></i></div>
                        <span style="font-weight:600"><?= htmlspecialchars(substr($c['title'],0,30)) ?>...</span>
                    </div>
                </td>
                <td style="color:var(--text-muted)"><?= $c['category'] ?></td>
                <td style="color:var(--text-muted)"><?= $c['level'] ?></td>
                <td><span class="badge <?= $is_free ? 'badge-free' : 'badge-paid' ?>"><?= $is_free ? 'FREE' : 'PAID' ?></span></td>
                <td style="font-weight:600;color:<?= $is_free ? 'var(--success)' : 'var(--accent)' ?>"><?= $is_free ? 'Free' : '$'.number_format($c['price'],2) ?></td>
                <td style="font-weight:700;color:var(--primary)"><?= $c['enrollment_count'] ?></td>
                <td>
                    <div style="display:flex;gap:0.5rem">
                        <a href="?edit=<?= $c['id'] ?>" class="btn btn-edit" style="padding:0.4rem 0.8rem;font-size:0.8rem"><i class="fas fa-edit"></i></a>
                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger" style="padding:0.4rem 0.8rem;font-size:0.8rem" onclick="return confirm('Delete this course?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
</main>
</body>
</html>
