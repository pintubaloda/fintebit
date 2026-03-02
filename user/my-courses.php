<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$uid = $_SESSION['user_id'];
$result = $conn->query("SELECT c.*, e.enrolled_at, e.progress FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.user_id = $uid ORDER BY e.enrolled_at DESC");
$courses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Courses – Fintebit</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root { --primary:#6366f1; --dark:#0f172a; --dark2:#1e293b; --dark3:#334155; --border:#334155; --text:#e2e8f0; --text-muted:#94a3b8; --success:#10b981; --accent:#f59e0b; --accent2:#06b6d4; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Inter',sans-serif; background:var(--dark); color:var(--text); display:flex; min-height:100vh; }
.sidebar { width:240px; background:var(--dark2); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; }
.sidebar-logo { padding:1.5rem; border-bottom:1px solid var(--border); }
.logo { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; background:linear-gradient(135deg,#6366f1,#06b6d4); -webkit-background-clip:text; -webkit-text-fill-color:transparent; text-decoration:none; }
.logo span { -webkit-text-fill-color:#f59e0b; }
.nav-menu { padding:1rem; flex:1; }
.nav-item { display:flex; align-items:center; gap:0.8rem; padding:0.8rem 1rem; border-radius:10px; text-decoration:none; color:var(--text-muted); font-size:0.9rem; font-weight:500; margin-bottom:0.2rem; transition:all 0.2s; }
.nav-item:hover, .nav-item.active { background:rgba(99,102,241,0.15); color:var(--primary); }
.nav-item i { width:18px; text-align:center; }
.sidebar-bottom { padding:1rem; border-top:1px solid var(--border); }
.main { margin-left:240px; flex:1; padding:2rem; }
.page-title { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:2rem; }
.courses-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.5rem; }
.course-card { background:var(--dark2); border:1px solid var(--border); border-radius:16px; overflow:hidden; transition:all 0.3s; }
.course-card:hover { transform:translateY(-4px); border-color:var(--primary); }
.course-thumb { height:140px; display:flex; align-items:center; justify-content:center; font-size:3rem; }
.course-body { padding:1.2rem; }
.course-title { font-weight:700; margin-bottom:0.5rem; }
.progress-wrap { margin:1rem 0; }
.progress-bar { height:8px; background:var(--dark3); border-radius:4px; overflow:hidden; }
.progress-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,var(--primary),var(--accent2)); }
.btn { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.4rem; border-radius:8px; font-weight:600; text-decoration:none; cursor:pointer; border:none; font-size:0.9rem; transition:all 0.3s; font-family:'Inter',sans-serif; }
.btn-primary { background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; width:100%; justify-content:center; }
.empty { text-align:center; padding:4rem; color:var(--text-muted); grid-column:1/-1; }
.user-avatar { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--accent2)); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; color:#fff; }
</style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo"><a href="../index.php" class="logo">Fin<span>tebit</span></a></div>
    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
        <a href="my-courses.php" class="nav-item active"><i class="fas fa-book-open"></i> My Courses</a>
        <a href="../courses.php" class="nav-item"><i class="fas fa-search"></i> Browse</a>
        <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> Profile</a>
        <a href="certificates.php" class="nav-item"><i class="fas fa-certificate"></i> Certificates</a>
    </nav>
    <div class="sidebar-bottom">
        <a href="../logout.php" class="nav-item" style="color:#ef4444"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>
<main class="main">
    <h1 class="page-title">📚 My Courses (<?= count($courses) ?>)</h1>
    <div class="courses-grid">
        <?php if (empty($courses)): ?>
        <div class="empty"><i class="fas fa-book" style="font-size:3rem;display:block;margin-bottom:1rem"></i><p>No enrolled courses yet.</p><a href="../courses.php" class="btn btn-primary" style="display:inline-flex;margin-top:1rem">Browse Courses</a></div>
        <?php else: ?>
        <?php foreach ($courses as $c): ?>
        <div class="course-card">
            <div class="course-thumb" style="background:linear-gradient(135deg,<?= $c['image_color'] ?>22,<?= $c['image_color'] ?>44)">
                <i class="<?= $c['icon'] ?>" style="color:<?= $c['image_color'] ?>"></i>
            </div>
            <div class="course-body">
                <div style="font-size:0.75rem;color:var(--accent2);margin-bottom:0.3rem;text-transform:uppercase;font-weight:600"><?= $c['category'] ?></div>
                <div class="course-title"><?= htmlspecialchars($c['title']) ?></div>
                <div style="font-size:0.8rem;color:var(--text-muted)"><?= $c['duration'] ?> • <?= $c['lessons'] ?> lessons</div>
                <div class="progress-wrap">
                    <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:0.4rem">
                        <span style="color:var(--text-muted)">Progress</span>
                        <span style="color:var(--primary);font-weight:600"><?= $c['progress'] ?>%</span>
                    </div>
                    <div class="progress-bar"><div class="progress-fill" style="width:<?= $c['progress'] ?>%"></div></div>
                </div>
                <a href="learn.php?course=<?= $c['id'] ?>" class="btn btn-primary"><i class="fas fa-play"></i> Continue Learning</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
