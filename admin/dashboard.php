<?php
define('INCLUDED', true);
require_once '../includes/config.php';
if(!isLoggedIn()||!isAdmin()) redirect('../login.php');
$pageTitle = 'Admin Dashboard';

$totalCourses = $conn->query("SELECT COUNT(*) as c FROM courses")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$totalEnrollments = $conn->query("SELECT COUNT(*) as c FROM enrollments")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT SUM(amount) as t FROM orders WHERE payment_status='completed'")->fetch_assoc()['t'] ?? 0;
$recentUsers = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5");
$topCourses = $conn->query("SELECT * FROM courses ORDER BY students_count DESC LIMIT 5");
?>
<?php include '../includes/header.php'; ?>
<?php include 'includes/admin_nav.php'; ?>

<div style="padding:2rem 0;"><div class="container">
  <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:1.5rem">Admin Dashboard</h1>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2.5rem;">
    <?php $astats=[
      ['Total Courses',$totalCourses,'fas fa-graduation-cap','#ff6b35','admin/courses.php'],
      ['Students',$totalUsers,'fas fa-users','#7c3aed','admin/users.php'],
      ['Enrollments',$totalEnrollments,'fas fa-book-open','#10b981','#'],
      ['Revenue','₹'.number_format($totalRevenue),'fas fa-rupee-sign','#f59e0b','admin/orders.php'],
    ]; foreach($astats as $s): ?>
    <a href="<?=$s[4]?>" style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:1.5rem;display:flex;align-items:center;gap:1rem;transition:all 0.2s;text-decoration:none;" onmouseover="this.style.borderColor='<?=$s[3]?>'" onmouseout="this.style.borderColor='var(--border)'">
      <div style="width:48px;height:48px;border-radius:12px;background:<?=$s[3]?>18;display:flex;align-items:center;justify-content:center;color:<?=$s[3]?>;font-size:1.2rem;flex-shrink:0"><i class="<?=$s[2]?>"></i></div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800"><?=$s[1]?></div>
        <div style="font-size:0.78rem;color:var(--text-muted)"><?=$s[0]?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;flex-wrap:wrap;">
    <!-- Top Courses -->
    <div class="card">
      <div style="padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-size:1rem;font-weight:700">Top Courses</h3>
        <a href="courses.php" class="btn btn-ghost btn-sm">View All</a>
      </div>
      <div style="padding:0.5rem 0;">
        <?php while($c=$topCourses->fetch_assoc()):
          $color=getCategoryColor($c['category']);
        ?>
        <div style="display:flex;align-items:center;gap:1rem;padding:0.8rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04)">
          <div style="width:36px;height:36px;border-radius:8px;background:<?=$color?>18;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><?=getCategoryIcon($c['category'])?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($c['title'])?></div>
            <div style="font-size:0.72rem;color:var(--text-muted)"><?=number_format($c['students_count'])?> students</div>
          </div>
          <?php if($c['is_free']): ?>
          <span class="badge badge-free" style="font-size:0.65rem">FREE</span>
          <?php else: ?>
          <span style="font-size:0.82rem;font-weight:700;color:var(--gold)">₹<?=number_format($c['price'])?></span>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="card">
      <div style="padding:1.2rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="font-size:1rem;font-weight:700">Recent Users</h3>
        <a href="users.php" class="btn btn-ghost btn-sm">View All</a>
      </div>
      <div style="padding:0.5rem 0;">
        <?php while($u=$recentUsers->fetch_assoc()): ?>
        <div style="display:flex;align-items:center;gap:1rem;padding:0.8rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04)">
          <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:0.9rem;font-weight:700;flex-shrink:0"><?=strtoupper(substr($u['name'],0,1))?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:0.85rem;font-weight:600"><?=htmlspecialchars($u['name'])?></div>
            <div style="font-size:0.72rem;color:var(--text-muted)"><?=htmlspecialchars($u['email'])?></div>
          </div>
          <div style="font-size:0.72rem;color:var(--text-muted)"><?=date('M d',strtotime($u['created_at']))?></div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div style="margin-top:1.5rem;display:flex;gap:0.8rem;flex-wrap:wrap;">
    <a href="add_course.php" class="btn btn-accent"><i class="fas fa-plus"></i> Add Course</a>
    <a href="courses.php" class="btn btn-ghost"><i class="fas fa-graduation-cap"></i> Manage Courses</a>
    <a href="users.php" class="btn btn-ghost"><i class="fas fa-users"></i> Manage Users</a>
    <a href="contact-messages.php" class="btn btn-ghost"><i class="fas fa-envelope"></i> Contact Messages</a>
    <a href="orders.php" class="btn btn-ghost"><i class="fas fa-receipt"></i> View Orders</a>
  </div>
</div></div>
<?php include '../includes/footer.php'; ?>
