<?php
if (!defined('INCLUDED')) {
    require_once __DIR__ . '/config.php';
}
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME . ' - Learn Without Limits' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #0f0f23;
  --accent: #ff6b35;
  --accent2: #7c3aed;
  --gold: #f59e0b;
  --success: #10b981;
  --text: #e2e8f0;
  --text-muted: #94a3b8;
  --card-bg: #1a1a35;
  --border: rgba(255,255,255,0.08);
  --nav-height: 72px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DM Sans', sans-serif; background: var(--primary); color: var(--text); line-height: 1.6; min-height: 100vh; }
h1,h2,h3,h4,h5 { font-family: 'Syne', sans-serif; }
a { color: inherit; text-decoration: none; }
img { max-width: 100%; }

/* NAVBAR */
.navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
  height: var(--nav-height);
  background: rgba(15,15,35,0.92);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center;
}
.nav-inner {
  max-width: 1280px; margin: 0 auto; padding: 0 2rem;
  display: flex; align-items: center; justify-content: space-between; width: 100%;
}
.nav-logo {
  font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800;
  background: linear-gradient(135deg, #ff6b35, #f59e0b);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  letter-spacing: -0.5px;
}
.nav-logo span { color: white; -webkit-text-fill-color: white; font-weight: 300; }
.nav-links { display: flex; align-items: center; gap: 1.5rem; }
.nav-link {
  color: var(--text-muted); font-size: 0.9rem; font-weight: 500;
  transition: color 0.2s; padding: 0.3rem 0; position: relative;
}
.nav-link:hover, .nav-link.active { color: var(--text); }
.nav-link.active::after {
  content: ''; position: absolute; bottom: 0; left: 0; right: 0;
  height: 2px; background: var(--accent); border-radius: 2px;
}
.nav-btn {
  padding: 0.5rem 1.2rem; border-radius: 8px; font-size: 0.875rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; border: none;
  font-family: 'DM Sans', sans-serif;
}
.btn-outline { background: transparent; border: 1.5px solid var(--border); color: var(--text); }
.btn-outline:hover { border-color: var(--accent); color: var(--accent); }
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: #e55a28; transform: translateY(-1px); }
.nav-user {
  display: flex; align-items: center; gap: 0.8rem;
}
.nav-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.85rem;
}
.dropdown { position: relative; }
.dropdown-menu {
  position: absolute; top: calc(100% + 0.5rem); right: 0;
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 12px; min-width: 200px; padding: 0.5rem;
  display: none; z-index: 999;
  box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}
.dropdown:hover .dropdown-menu { display: block; }
.dropdown-item {
  display: block; padding: 0.6rem 1rem; border-radius: 8px;
  color: var(--text-muted); font-size: 0.875rem; transition: all 0.15s;
}
.dropdown-item:hover { background: rgba(255,255,255,0.05); color: var(--text); }
.dropdown-divider { height: 1px; background: var(--border); margin: 0.4rem 0; }

/* MAIN CONTENT */
.page-content { margin-top: var(--nav-height); }

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600;
  font-size: 0.9rem; cursor: pointer; border: none; transition: all 0.2s;
  font-family: 'DM Sans', sans-serif;
}
.btn-lg { padding: 1rem 2rem; font-size: 1rem; }
.btn-sm { padding: 0.4rem 0.9rem; font-size: 0.8rem; }
.btn-accent { background: var(--accent); color: white; }
.btn-accent:hover { background: #e55a28; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,107,53,0.3); }
.btn-violet { background: var(--accent2); color: white; }
.btn-violet:hover { background: #6d28d9; transform: translateY(-2px); }
.btn-success { background: var(--success); color: white; }
.btn-ghost { background: rgba(255,255,255,0.05); color: var(--text); border: 1px solid var(--border); }
.btn-ghost:hover { background: rgba(255,255,255,0.1); }
.btn-danger { background: #ef4444; color: white; }

/* CARDS */
.card {
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 16px; overflow: hidden; transition: all 0.3s;
}
.card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.12); }

/* BADGES */
.badge {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.3rem 0.7rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600;
}
.badge-free { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
.badge-paid { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
.badge-level { background: rgba(124,58,237,0.15); color: #a78bfa; border: 1px solid rgba(124,58,237,0.3); }
.badge-cat { background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid var(--border); }

/* FORMS */
.form-group { margin-bottom: 1.2rem; }
.form-label { display: block; margin-bottom: 0.4rem; font-size: 0.875rem; font-weight: 500; color: var(--text-muted); }
.form-control {
  width: 100%; padding: 0.75rem 1rem; background: rgba(255,255,255,0.04);
  border: 1.5px solid var(--border); border-radius: 10px; color: var(--text);
  font-size: 0.9rem; font-family: 'DM Sans', sans-serif; transition: border-color 0.2s;
}
.form-control:focus { outline: none; border-color: var(--accent); background: rgba(255,107,53,0.04); }
.form-control::placeholder { color: var(--text-muted); }
select.form-control option { background: #1a1a35; }

/* ALERTS */
.alert { padding: 0.9rem 1.2rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1rem; }
.alert-success { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; }
.alert-danger { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
.alert-info { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.3); color: #60a5fa; }

/* TABLES */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th { padding: 0.8rem 1rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); border-bottom: 1px solid var(--border); }
tbody td { padding: 0.9rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.875rem; }
tbody tr:hover { background: rgba(255,255,255,0.02); }

/* STARS */
.stars { color: #f59e0b; font-size: 0.8rem; }

/* UTILS */
.container { max-width: 1280px; margin: 0 auto; padding: 0 1.5rem; }
.text-accent { color: var(--accent); }
.text-muted { color: var(--text-muted); }
.text-success { color: var(--success); }
.text-center { text-align: center; }
.mt-1 { margin-top: 0.5rem; }
.mt-2 { margin-top: 1rem; }
.mt-3 { margin-top: 1.5rem; }
.mb-1 { margin-bottom: 0.5rem; }
.mb-2 { margin-bottom: 1rem; }
.mb-3 { margin-bottom: 1.5rem; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-1 { gap: 0.5rem; }
.gap-2 { gap: 1rem; }
.w-full { width: 100%; }

/* HAMBURGER */
.hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 5px; }
.hamburger span { width: 25px; height: 2px; background: var(--text); border-radius: 2px; transition: all 0.3s; }

@media (max-width: 768px) {
  .hamburger { display: flex; }
  .nav-links { 
    display: none; flex-direction: column; 
    position: fixed; top: var(--nav-height); left: 0; right: 0;
    background: var(--card-bg); border-bottom: 1px solid var(--border);
    padding: 1.5rem; gap: 1rem; z-index: 999;
  }
  .nav-links.open { display: flex; }
}
</style>
</head>
<body>
<nav class="navbar">
  <div class="nav-inner">
    <a href="<?= SITE_URL ?>/index.php" class="nav-logo">Fin<span>tebit</span></a>
    <div class="hamburger" onclick="document.querySelector('.nav-links').classList.toggle('open')">
      <span></span><span></span><span></span>
    </div>
    <div class="nav-links">
      <a href="<?= SITE_URL ?>/index.php" class="nav-link <?= $currentPage==='index'?'active':'' ?>">Home</a>
      <a href="<?= SITE_URL ?>/courses.php" class="nav-link <?= $currentPage==='courses'?'active':'' ?>">Courses</a>
      <?php if(isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/user/dashboard.php" class="nav-link <?= $currentPage==='dashboard'?'active':'' ?>">My Learning</a>
      <?php endif; ?>
      <?php if(isAdmin()): ?>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-link">Admin Panel</a>
      <?php endif; ?>
      <?php if(isLoggedIn()): ?>
        <div class="dropdown nav-user">
          <div class="nav-avatar" style="cursor:pointer"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
          <div class="dropdown-menu">
            <span style="padding:0.6rem 1rem;font-size:0.8rem;color:var(--text-muted);display:block"><?= htmlspecialchars($_SESSION['name']) ?></span>
            <div class="dropdown-divider"></div>
            <a href="<?= SITE_URL ?>/user/dashboard.php" class="dropdown-item"><i class="fas fa-graduation-cap"></i> My Courses</a>
            <a href="<?= SITE_URL ?>/user/profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
            <?php if(isAdmin()): ?>
              <a href="<?= SITE_URL ?>/admin/dashboard.php" class="dropdown-item"><i class="fas fa-cog"></i> Admin Panel</a>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <a href="<?= SITE_URL ?>/logout.php" class="dropdown-item" style="color:#f87171"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="nav-btn btn-outline">Login</a>
        <a href="<?= SITE_URL ?>/register.php" class="nav-btn btn-primary">Get Started</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="page-content">
