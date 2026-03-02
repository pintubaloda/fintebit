<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin(); requireAdmin();

$msg = '';
if (isset($_GET['delete']) && $_GET['delete'] != $_SESSION['user_id']) {
    $conn->query("DELETE FROM users WHERE id=".(int)$_GET['delete']." AND role='user'");
    $msg = 'User deleted.';
}

$users = $conn->query("SELECT u.*, COUNT(e.id) as enrollments FROM users u LEFT JOIN enrollments e ON u.id=e.user_id GROUP BY u.id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users – Admin</title>
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
.card h2{font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1.5rem}
.table{width:100%;border-collapse:collapse}.table th{text-align:left;padding:0.7rem;font-size:0.8rem;color:var(--text-muted);font-weight:600;border-bottom:1px solid var(--border)}.table td{padding:0.9rem 0.7rem;font-size:0.85rem;border-bottom:1px solid rgba(51,65,85,0.5)}
.badge{padding:0.2rem 0.6rem;border-radius:50px;font-size:0.75rem;font-weight:600}.badge-admin{background:rgba(239,68,68,0.15);color:var(--red)}.badge-user{background:rgba(99,102,241,0.15);color:var(--primary)}
.avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#06b6d4);display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:0.9rem}
.btn{display:inline-flex;align-items:center;gap:0.3rem;padding:0.4rem 0.8rem;border-radius:8px;font-weight:600;text-decoration:none;cursor:pointer;border:none;font-size:0.8rem;transition:all 0.3s;font-family:'Inter',sans-serif}
.btn-danger{background:rgba(239,68,68,0.15);color:var(--red);border:1px solid rgba(239,68,68,0.3)}
.success-msg{background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);color:var(--success);padding:1rem;border-radius:10px;margin-bottom:1.5rem}
</style>
</head>
<body>
<aside class="sidebar">
    <a href="../index.php" class="logo">Fin<span>tebit</span></a>
    <div class="admin-badge">ADMIN</div>
    <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
    <a href="users.php" class="nav-item active"><i class="fas fa-users"></i> Users</a>
    <a href="enrollments.php" class="nav-item"><i class="fas fa-clipboard-list"></i> Enrollments</a>
    <a href="../logout.php" class="nav-item" style="color:var(--red);margin-top:2rem"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <h1 class="page-title">👥 All Users (<?= count($users) ?>)</h1>
    <?php if ($msg): ?><div class="success-msg"><?= $msg ?></div><?php endif; ?>
    <div class="card">
        <div style="overflow-x:auto">
        <table class="table">
            <tr><th>User</th><th>Email</th><th>Role</th><th>Courses</th><th>Joined</th><th>Actions</th></tr>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:0.8rem">
                        <div class="avatar"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                        <span style="font-weight:600"><?= htmlspecialchars($u['name']) ?></span>
                    </div>
                </td>
                <td style="color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                <td style="font-weight:700;color:var(--primary)"><?= $u['enrollments'] ?></td>
                <td style="color:var(--text-muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['id'] != $_SESSION['user_id'] && $u['role'] !== 'admin'): ?>
                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i> Delete</a>
                    <?php else: ?>
                    <span style="color:var(--text-muted);font-size:0.8rem">Protected</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
</main>
</body>
</html>
