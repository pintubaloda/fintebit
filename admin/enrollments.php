<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin(); requireAdmin();

$enrollments = $conn->query("SELECT e.*, u.name as user_name, u.email, c.title as course_title, c.is_free, c.price, c.image_color, c.icon FROM enrollments e JOIN users u ON e.user_id=u.id JOIN courses c ON e.course_id=c.id ORDER BY e.enrolled_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enrollments – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#6366f1;--dark:#0f172a;--dark2:#1e293b;--border:#334155;--text:#e2e8f0;--text-muted:#94a3b8;--success:#10b981;--accent:#f59e0b;--red:#ef4444;}
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--text);display:flex;min-height:100vh}
.sidebar{width:240px;background:var(--dark2);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0;padding:1.5rem 1rem}
.logo{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,#6366f1,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none;display:block;margin-bottom:0.3rem}.logo span{-webkit-text-fill-color:#f59e0b}
.admin-badge{display:inline-block;background:rgba(239,68,68,0.15);color:var(--red);font-size:0.7rem;font-weight:700;padding:0.2rem 0.6rem;border-radius:4px;margin-bottom:1.5rem}
.nav-item{display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1rem;border-radius:10px;text-decoration:none;color:var(--text-muted);font-size:0.9rem;font-weight:500;margin-bottom:0.2rem;transition:all 0.2s}.nav-item:hover,.nav-item.active{background:rgba(99,102,241,0.15);color:var(--primary)}.nav-item i{width:18px;text-align:center}
.main{margin-left:240px;flex:1;padding:2rem}.page-title{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:2rem}
.card{background:var(--dark2);border:1px solid var(--border);border-radius:16px;padding:1.5rem}
.table{width:100%;border-collapse:collapse}.table th{text-align:left;padding:0.7rem;font-size:0.8rem;color:var(--text-muted);font-weight:600;border-bottom:1px solid var(--border)}.table td{padding:0.9rem 0.7rem;font-size:0.85rem;border-bottom:1px solid rgba(51,65,85,0.5)}
.badge{padding:0.2rem 0.6rem;border-radius:50px;font-size:0.75rem;font-weight:600}.badge-free{background:rgba(16,185,129,0.15);color:var(--success)}.badge-paid{background:rgba(245,158,11,0.15);color:var(--accent)}
.progress-bar{height:6px;background:rgba(51,65,85,1);border-radius:3px;overflow:hidden;width:80px}.progress-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--primary),#06b6d4)}
</style>
</head>
<body>
<aside class="sidebar">
    <a href="../index.php" class="logo">Fin<span>tebit</span></a>
    <div class="admin-badge">ADMIN</div>
    <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
    <a href="users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
    <a href="enrollments.php" class="nav-item active"><i class="fas fa-clipboard-list"></i> Enrollments</a>
    <a href="../logout.php" class="nav-item" style="color:var(--red);margin-top:2rem"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <h1 class="page-title">📋 All Enrollments (<?= count($enrollments) ?>)</h1>
    <div class="card" style="overflow-x:auto">
        <table class="table">
            <tr><th>Student</th><th>Course</th><th>Type</th><th>Progress</th><th>Enrolled On</th></tr>
            <?php foreach ($enrollments as $e):
            $is_free = $e['is_free'] || $e['price'] == 0;
            ?>
            <tr>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($e['user_name']) ?></div>
                    <div style="font-size:0.8rem;color:var(--text-muted)"><?= htmlspecialchars($e['email']) ?></div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.5rem">
                        <span style="width:28px;height:28px;border-radius:6px;background:<?= $e['image_color'] ?>22;color:<?= $e['image_color'] ?>;display:inline-flex;align-items:center;justify-content:center;font-size:0.9rem"><i class="<?= $e['icon'] ?>"></i></span>
                        <span style="font-size:0.85rem"><?= htmlspecialchars(substr($e['course_title'],0,30)) ?>...</span>
                    </div>
                </td>
                <td><span class="badge <?= $is_free ? 'badge-free' : 'badge-paid' ?>"><?= $is_free ? 'FREE' : 'PAID' ?></span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.5rem">
                        <div class="progress-bar"><div class="progress-fill" style="width:<?= $e['progress'] ?>%"></div></div>
                        <span style="font-size:0.8rem;color:var(--text-muted)"><?= $e['progress'] ?>%</span>
                    </div>
                </td>
                <td style="color:var(--text-muted);font-size:0.8rem"><?= date('d M Y', strtotime($e['enrolled_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</main>
</body>
</html>
