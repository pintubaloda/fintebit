<?php
define('INCLUDED', true);
require_once 'includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');
$stmt = $conn->prepare("SELECT * FROM courses WHERE slug=? AND status='active'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if(!$course) { header("Location: courses.php"); exit; }

$pageTitle = $course['title'];
$lessons = $conn->query("SELECT * FROM lessons WHERE course_id={$course['id']} ORDER BY order_num");
$enrolled = isLoggedIn() ? isEnrolled($conn, $_SESSION['user_id'], $course['id']) : false;

// Handle enrollment
$message = ''; $msgType = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['enroll'])) {
    if(!isLoggedIn()) { redirect('login.php?redirect=course.php?slug='.$slug); }
    if(!$enrolled) {
        if($course['is_free']) {
            $stmt2 = $conn->prepare("INSERT IGNORE INTO enrollments (user_id,course_id) VALUES(?,?)");
            $stmt2->bind_param("ii", $_SESSION['user_id'], $course['id']);
            if($stmt2->execute()) { $enrolled=true; $message='Successfully enrolled!'; $msgType='success'; 
                $conn->query("UPDATE courses SET students_count=students_count+1 WHERE id={$course['id']}");}
        } else {
            // Simulate payment for demo
            $stmt2 = $conn->prepare("INSERT IGNORE INTO enrollments (user_id,course_id) VALUES(?,?)");
            $stmt2->bind_param("ii", $_SESSION['user_id'], $course['id']);
            if($stmt2->execute()) {
                $conn->prepare("INSERT INTO orders(user_id,course_id,amount) VALUES(?,?,?)")->execute() || true;
                $o=$conn->prepare("INSERT INTO orders(user_id,course_id,amount) VALUES(?,?,?)");
                $o->bind_param("iid",$_SESSION['user_id'],$course['id'],$course['price']);
                $o->execute();
                $conn->query("UPDATE courses SET students_count=students_count+1 WHERE id={$course['id']}");
                $enrolled=true; $message='Payment successful! You are now enrolled.'; $msgType='success';
            }
        }
    }
}

$color = getCategoryColor($course['category']);
$icon = getCategoryIcon($course['category']);
?>
<?php include 'includes/header.php'; ?>

<div style="background:linear-gradient(135deg,<?=$color?>15,rgba(0,0,0,0.5));border-bottom:1px solid var(--border);padding:3rem 0;">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 340px;gap:3rem;align-items:start;">
      <div>
        <div style="display:flex;gap:0.6rem;margin-bottom:1rem;flex-wrap:wrap;">
          <a href="courses.php?category=<?=urlencode($course['category'])?>" style="font-size:0.75rem;color:<?=$color?>;font-weight:600;background:<?=$color?>18;padding:0.3rem 0.8rem;border-radius:50px;border:1px solid <?=$color?>33"><?=$course['category']?></a>
          <span class="badge badge-level"><?=$course['level']?></span>
          <?php if($course['is_free']): ?><span class="badge badge-free">FREE</span><?php endif; ?>
        </div>
        <h1 style="font-size:2.2rem;font-weight:800;margin-bottom:1rem;line-height:1.2"><?=htmlspecialchars($course['title'])?></h1>
        <p style="color:var(--text-muted);font-size:1rem;line-height:1.8;margin-bottom:1.5rem"><?=htmlspecialchars($course['description'])?></p>
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;font-size:0.875rem;color:var(--text-muted)">
          <div style="display:flex;align-items:center;gap:0.5rem">
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,<?=$color?>,<?=$color?>88);display:flex;align-items:center;justify-content:center;font-size:0.9rem;font-weight:700"><?=strtoupper(substr($course['instructor'],0,1))?></div>
            <span><?=htmlspecialchars($course['instructor'])?></span>
          </div>
          <div class="stars"><?php for($i=1;$i<=5;$i++) echo round($course['rating'])>=$i?'★':'☆'?> <?=$course['rating']?> <span style="color:var(--text-muted)">(<?=number_format($course['students_count'])?> students)</span></div>
        </div>
        <div style="display:flex;gap:2rem;margin-top:1.5rem;flex-wrap:wrap;">
          <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700"><?=$course['duration']?></div><div style="font-size:0.75rem;color:var(--text-muted)">Duration</div></div>
          <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700"><?=$course['total_lessons']?></div><div style="font-size:0.75rem;color:var(--text-muted)">Lessons</div></div>
          <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700"><?=number_format($course['students_count'])?></div><div style="font-size:0.75rem;color:var(--text-muted)">Students</div></div>
          <div style="text-align:center"><div style="font-size:1.3rem;font-weight:700"><?=$course['level']?></div><div style="font-size:0.75rem;color:var(--text-muted)">Level</div></div>
        </div>
      </div>
      <!-- Enrollment Card -->
      <div class="card" style="position:sticky;top:calc(var(--nav-height)+1rem);">
        <div style="height:140px;background:linear-gradient(135deg,<?=$color?>30,<?=$color?>50);display:flex;align-items:center;justify-content:center;font-size:4rem"><?=$icon?></div>
        <div style="padding:1.5rem;">
          <?php if($message): ?>
          <div class="alert alert-<?=$msgType?>"><?=$message?></div>
          <?php endif; ?>
          <div style="font-size:2rem;font-weight:800;margin-bottom:0.5rem">
            <?php if($course['is_free']): ?>
              <span style="color:var(--success)">FREE</span>
            <?php else: ?>
              ₹<?=number_format($course['price'])?>
            <?php endif; ?>
          </div>
          <?php if($enrolled): ?>
            <a href="user/learn.php?course=<?=$course['id']?>" class="btn btn-success w-full" style="justify-content:center;margin-bottom:0.8rem"><i class="fas fa-play-circle"></i> Continue Learning</a>
            <div style="text-align:center;font-size:0.8rem;color:var(--success)"><i class="fas fa-check-circle"></i> You're enrolled!</div>
          <?php elseif(isLoggedIn()): ?>
            <form method="POST">
              <button type="submit" name="enroll" class="btn btn-accent w-full btn-lg" style="justify-content:center">
                <?=$course['is_free']?'<i class="fas fa-graduation-cap"></i> Enroll for Free':'<i class="fas fa-credit-card"></i> Buy Now — ₹'.number_format($course['price'])?>
              </button>
            </form>
            <?php if(!$course['is_free']): ?><p style="text-align:center;font-size:0.75rem;color:var(--text-muted);margin-top:0.5rem">30-day money back guarantee</p><?php endif; ?>
          <?php else: ?>
            <a href="login.php?redirect=course.php?slug=<?=$slug?>" class="btn btn-accent w-full btn-lg" style="justify-content:center"><i class="fas fa-sign-in-alt"></i> Login to Enroll</a>
            <a href="register.php" class="btn btn-ghost w-full mt-1" style="justify-content:center;margin-top:0.5rem">Create Free Account</a>
          <?php endif; ?>
          <div style="margin-top:1.2rem;display:flex;flex-direction:column;gap:0.5rem;font-size:0.82rem;color:var(--text-muted)">
            <div><i class="fas fa-infinity" style="color:var(--accent);width:20px"></i> Full lifetime access</div>
            <div><i class="fas fa-mobile-alt" style="color:var(--accent);width:20px"></i> Access on all devices</div>
            <div><i class="fas fa-certificate" style="color:var(--accent);width:20px"></i> Certificate of completion</div>
            <div><i class="fas fa-language" style="color:var(--accent);width:20px"></i> English language</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Course Content -->
<div class="container" style="padding:2.5rem 1.5rem;">
  <div style="max-width:860px;">
    <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:1.5rem">Course Content</h2>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;">
      <div style="padding:1rem 1.2rem;background:rgba(255,255,255,0.03);border-bottom:1px solid var(--border);font-size:0.85rem;color:var(--text-muted)">
        <?=$course['total_lessons']?> lessons • <?=$course['duration']?> total
      </div>
      <?php $lessonNum=1; while($lesson=$lessons->fetch_assoc()): ?>
      <div style="display:flex;align-items:center;gap:1rem;padding:0.9rem 1.2rem;border-bottom:1px solid rgba(255,255,255,0.04);transition:background 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
        <div style="width:32px;height:32px;border-radius:50%;background:<?=$enrolled?'linear-gradient(135deg,var(--success),#059669)':'rgba(255,255,255,0.06)'?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.8rem">
          <?=$enrolled?'<i class="fas fa-play" style="color:white;font-size:0.7rem"></i>':$lessonNum?>
        </div>
        <div style="flex:1">
          <div style="font-size:0.875rem;font-weight:500"><?=htmlspecialchars($lesson['title'])?></div>
          <?php if($lesson['content']): ?>
          <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.2rem"><?=substr(htmlspecialchars($lesson['content']),0,80)?>...</div>
          <?php endif; ?>
        </div>
        <div style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap"><?=$lesson['duration']?></div>
      </div>
      <?php $lessonNum++; endwhile; ?>
      <?php if($course['total_lessons'] > $lessonNum-1): ?>
      <div style="padding:1rem 1.2rem;text-align:center;font-size:0.85rem;color:var(--text-muted)">
        + <?=($course['total_lessons']-($lessonNum-1))?> more lessons after enrollment
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
