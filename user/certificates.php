<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$uid = $_SESSION['user_id'];
$result = $conn->query("SELECT c.*, e.progress FROM enrollments e JOIN courses c ON e.course_id=c.id WHERE e.user_id=$uid AND e.progress>=100");
$completed = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificates – Fintebit</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#6366f1;--dark:#0f172a;--dark2:#1e293b;--border:#334155;--text:#e2e8f0;--text-muted:#94a3b8;--success:#10b981;--accent:#f59e0b;}
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--text);display:flex;min-height:100vh}
.sidebar{width:240px;background:var(--dark2);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0}
.sidebar-logo{padding:1.5rem;border-bottom:1px solid var(--border)}.logo{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,#6366f1,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}.logo span{-webkit-text-fill-color:#f59e0b}
.nav-menu{padding:1rem}.nav-item{display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1rem;border-radius:10px;text-decoration:none;color:var(--text-muted);font-size:0.9rem;font-weight:500;margin-bottom:0.2rem;transition:all 0.2s}.nav-item:hover,.nav-item.active{background:rgba(99,102,241,0.15);color:var(--primary)}.nav-item i{width:18px;text-align:center}
.main{margin-left:240px;flex:1;padding:2rem}.page-title{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:2rem}
.cert-card{background:linear-gradient(135deg,#1e293b,#0f172a);border:2px solid var(--accent);border-radius:16px;padding:3rem;text-align:center;margin-bottom:1.5rem}
.cert-icon{font-size:4rem;margin-bottom:1rem}.cert-title{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--accent);margin-bottom:0.5rem}
.btn{display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.5rem;border-radius:8px;font-weight:600;text-decoration:none;border:none;cursor:pointer;font-size:0.9rem;font-family:'Inter',sans-serif;background:linear-gradient(135deg,var(--accent),#d97706);color:#000;margin-top:1rem}
.empty{text-align:center;padding:4rem;color:var(--text-muted)}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo"><a href="../index.php" class="logo">Fin<span>tebit</span></a></div>
    <div class="nav-menu">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
        <a href="my-courses.php" class="nav-item"><i class="fas fa-book-open"></i> My Courses</a>
        <a href="certificates.php" class="nav-item active"><i class="fas fa-certificate"></i> Certificates</a>
        <a href="../logout.php" class="nav-item" style="color:#ef4444;margin-top:2rem"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>
<main class="main">
    <h1 class="page-title">🏆 My Certificates</h1>
    <?php if (empty($completed)): ?>
    <div class="empty">
        <i class="fas fa-certificate" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3"></i>
        <p>Complete a course to earn your certificate!</p>
        <a href="../courses.php" style="color:var(--primary)">Browse Courses →</a>
    </div>
    <?php else: ?>
    <?php foreach ($completed as $c): ?>
    <div class="cert-card">
        <div class="cert-icon">🏆</div>
        <div class="cert-title">Certificate of Completion</div>
        <div style="font-size:1.2rem;margin-bottom:0.5rem">This certifies that</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($_SESSION['name']) ?></div>
        <div style="font-size:1.1rem;color:var(--text-muted);margin:0.5rem 0">has successfully completed</div>
        <div style="font-size:1.5rem;font-weight:700;color:var(--primary)"><?= htmlspecialchars($c['title']) ?></div>
        <button class="btn"><i class="fas fa-download"></i> Download Certificate</button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
