<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

$payments = $conn->query("
    SELECT p.*, u.name as user_name, u.email as user_email, c.title as course_title
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    ORDER BY p.paid_at DESC
");

$totalRev = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE status='completed'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments — Admin — Fintebit</title>
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
    <a href="payments.php" class="sidebar-link active"><span class="icon">💰</span> Payments</a>
    <a href="categories.php" class="sidebar-link"><span class="icon">🏷️</span> Categories</a>
    <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">
    <a href="../logout.php" class="sidebar-link" style="color:var(--danger)"><span class="icon">🚪</span> Logout</a>
  </div>

  <div class="admin-main">
    <div class="page-header">
      <div><h1>Payments</h1><p>Revenue tracking and transaction history</p></div>
    </div>

    <div class="stats-row">
      <div class="stat-card teal">
        <div class="stat-card-icon">💰</div>
        <div class="stat-card-num">$<?= number_format($totalRev, 2) ?></div>
        <div class="stat-card-label">Total Revenue (USD)</div>
      </div>
      <div class="stat-card yellow">
        <div class="stat-card-icon">₹</div>
        <div class="stat-card-num">₹<?= number_format($totalRev * 83, 0) ?></div>
        <div class="stat-card-label">Total Revenue (INR)</div>
      </div>
      <div class="stat-card purple">
        <div class="stat-card-icon">🧾</div>
        <div class="stat-card-num"><?= $payments->num_rows ?></div>
        <div class="stat-card-label">Total Transactions</div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-header"><h3>Transaction History</h3></div>
      <?php if($payments->num_rows === 0): ?>
      <div style="padding:40px;text-align:center;color:var(--text-muted)">
        <div style="font-size:3rem;margin-bottom:12px">💳</div>
        <p>No payments yet. Enroll in paid courses to see transactions.</p>
      </div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Course</th>
            <th>Amount (USD)</th>
            <th>Amount (INR)</th>
            <th>Transaction ID</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php $payments->data_seek(0); while($p = $payments->fetch_assoc()): ?>
          <tr>
            <td style="color:var(--text-muted)"><?= $p['id'] ?></td>
            <td>
              <div>
                <div style="font-weight:600;color:var(--white);font-size:0.9rem"><?= htmlspecialchars($p['user_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= htmlspecialchars($p['user_email']) ?></div>
              </div>
            </td>
            <td style="color:var(--text-light);font-size:0.85rem;max-width:180px"><?= htmlspecialchars($p['course_title']) ?></td>
            <td style="font-weight:700;color:var(--white)">$<?= number_format($p['amount'], 2) ?></td>
            <td style="font-weight:700;color:var(--success)">₹<?= number_format($p['amount']*83, 0) ?></td>
            <td style="font-size:0.8rem;color:var(--text-muted);font-family:monospace"><?= htmlspecialchars($p['transaction_id']) ?></td>
            <td style="color:var(--text-muted);font-size:0.85rem"><?= date('d M Y H:i', strtotime($p['paid_at'])) ?></td>
            <td><span class="badge badge-success"><?= $p['status'] ?></span></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
