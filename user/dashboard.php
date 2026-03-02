<?php
define('INCLUDED', true);
require_once '../includes/config.php';
if(!isLoggedIn()) redirect('../login.php');
$pageTitle = 'My Dashboard';

$userId = $_SESSION['user_id'];
// Enrolled courses
$enrolled = $conn->query("SELECT c.*,e.enrolled_at,e.progress FROM enrollments e JOIN courses c ON e.course_id=c.id WHERE e.user_id=$userId ORDER BY e.enrolled_at DESC");
$enrollCount = $enrolled->num_rows;

// Total spent
$spent = $conn->query("SELECT SUM(amount) as total FROM orders WHERE user_id=$userId AND payment_status='completed'")->fetch_assoc()['total'] ?? 0;
?>
<?php include '../includes/header.php'; ?>
<div style="padding:2.5rem 0;">
  <div class="container">
    <!-- Welcome -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2.5rem;flex-wrap:wrap;gap:1rem;">
      <div style="display:flex;align-items:center;gap:1rem;">
        <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800"><?=strtoupper(substr($_SESSION['name'],0,1))?></div>
        <div>
          <h1 style="font-size:1.6rem;font-weight:800">Welcome, <?=htmlspecialchars(explode(' ',$_SESSION['name'])[0])?>! 👋</h1>
          <p style="color:var(--text-muted);font-size:0.875rem">Continue your learning journey</p>
        </div>
      </div>
      <a href="../courses.php" class="btn btn-accent"><i class="fas fa-plus"></i> Browse Courses</a>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2.5rem;">
      <?php $stats = [
        ['Enrolled Courses',$enrollCount,'fas fa-graduation-cap','#ff6b35'],
        ['Completed',0,'fas fa-trophy','#f59e0b'],
        ['Total Spent','₹'.number_format($spent),'fas fa-receipt','#10b981'],
        ['Hours Learned','0h','fas fa-clock','#7c3aed'],
      ]; foreach($stats as $s): ?>
      <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:1.3rem;display:flex;align-items:center;gap:1rem;">
        <div style="width:44px;height:44px;border-radius:10px;background:<?=$s[3]?>18;display:flex;align-items:center;justify-content:center;color:<?=$s[3]?>;font-size:1.1rem;flex-shrink:0">
          <i class="<?=$s[2]?>"></i>
        </div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800"><?=$s[1]?></div>
          <div style="font-size:0.75rem;color:var(--text-muted)"><?=$s[0]?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- My Courses -->
    <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:1.2rem">My Courses (<?=$enrollCount?>)</h2>
    <?php if($enrollCount === 0): ?>
    <div style="text-align:center;padding:4rem;background:var(--card-bg);border:1px solid var(--border);border-radius:16px;">
      <div style="font-size:3.5rem;margin-bottom:1rem">📚</div>
      <h3 style="margin-bottom:0.5rem">No courses yet</h3>
      <p style="color:var(--text-muted);margin-bottom:1.5rem">Start your learning journey today</p>
      <a href="../courses.php" class="btn btn-accent">Browse Courses</a>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;">
      <?php $enrolled->data_seek(0); while($c=$enrolled->fetch_assoc()):
        $color = getCategoryColor($c['category']);
        $icon = getCategoryIcon($c['category']);
      ?>
      <div class="card">
        <div style="height:120px;background:linear-gradient(135deg,<?=$color?>25,<?=$color?>45);display:flex;align-items:center;justify-content:center;font-size:3rem;position:relative;">
          <?=$icon?>
          <?php if($c['is_free']): ?>
          <div style="position:absolute;top:0.7rem;right:0.7rem"><span class="badge badge-free">FREE</span></div>
          <?php endif; ?>
        </div>
        <div style="padding:1.2rem;">
          <span style="font-size:0.72rem;color:<?=$color?>;font-weight:600"><?=$c['category']?></span>
          <h3 style="font-size:0.95rem;font-weight:700;margin:0.3rem 0 0.5rem;line-height:1.3"><?=htmlspecialchars($c['title'])?></h3>
          <div style="margin-bottom:0.8rem;">
            <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--text-muted);margin-bottom:0.3rem">
              <span>Progress</span><span><?=$c['progress']?>%</span>
            </div>
            <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden">
              <div style="height:100%;width:<?=$c['progress']?>%;background:linear-gradient(90deg,var(--accent),var(--gold));border-radius:3px;transition:width 0.5s"></div>
            </div>
          </div>
          <div style="display:flex;gap:0.6rem;">
            <a href="learn.php?course=<?=$c['id']?>" class="btn btn-accent btn-sm" style="flex:1;justify-content:center"><i class="fas fa-play"></i> Continue</a>
            <a href="../course.php?slug=<?=$c['slug']?>" class="btn btn-ghost btn-sm"><i class="fas fa-info"></i></a>
          </div>
          <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.6rem">Enrolled: <?=date('M d, Y',strtotime($c['enrolled_at']))?></div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
