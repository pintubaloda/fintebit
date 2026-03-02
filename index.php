<?php
define('INCLUDED', true);
require_once 'includes/config.php';
$pageTitle = 'Home';

// If database isn't initialized yet, send users to setup flow.
$coursesTable = $conn->query("SHOW TABLES LIKE 'courses'");
if (!$coursesTable || $coursesTable->num_rows === 0) {
    redirect('setup.php');
}

// Some deployments may still be on legacy schema briefly.
$hasStatus = false;
$columns = $conn->query("SHOW COLUMNS FROM courses");
if ($columns) {
    while ($col = $columns->fetch_assoc()) {
        if ($col['Field'] === 'status') {
            $hasStatus = true;
            break;
        }
    }
}
$activeFilter = $hasStatus ? " WHERE status='active'" : "";

// Get stats
$totalCoursesRes = $conn->query("SELECT COUNT(*) as c FROM courses" . $activeFilter);
$totalCourses = $totalCoursesRes ? (int)$totalCoursesRes->fetch_assoc()['c'] : 0;
$totalStudentsRes = $conn->query("SELECT SUM(students_count) as c FROM courses");
$totalStudents = $totalStudentsRes ? (int)($totalStudentsRes->fetch_assoc()['c'] ?? 0) : 0;
$totalInstructorsRes = $conn->query("SELECT COUNT(DISTINCT instructor) as c FROM courses");
$totalInstructors = $totalInstructorsRes ? (int)$totalInstructorsRes->fetch_assoc()['c'] : 0;

// Featured courses
$featured = $conn->query("SELECT * FROM courses" . $activeFilter . " ORDER BY students_count DESC LIMIT 6");
if (!$featured) {
    $featured = $conn->query("SELECT * FROM courses ORDER BY id DESC LIMIT 6");
}

// Categories with count
$categories = $conn->query("SELECT category, COUNT(*) as cnt FROM courses GROUP BY category ORDER BY cnt DESC LIMIT 8");
if (!$categories) {
    $categories = $conn->query("SELECT 'General' as category, 0 as cnt");
}
?>
<?php include 'includes/header.php'; ?>

<!-- HERO -->
<section style="min-height:90vh;display:flex;align-items:center;position:relative;overflow:hidden;padding:4rem 0;">
  <!-- Animated BG -->
  <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;">
    <div style="position:absolute;top:-20%;right:-10%;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(255,107,53,0.12) 0%,transparent 70%);animation:pulse 4s ease-in-out infinite;"></div>
    <div style="position:absolute;bottom:-20%;left:-10%;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(124,58,237,0.1) 0%,transparent 70%);animation:pulse 4s ease-in-out infinite 2s;"></div>
    <div style="position:absolute;top:50%;left:50%;width:1px;height:1px;">
      <?php for($i=0;$i<20;$i++): 
        $x = rand(-600,600); $y = rand(-300,300); $s = rand(1,3); $d = rand(2,8);
      ?>
      <div style="position:absolute;width:<?=$s?>px;height:<?=$s?>px;background:rgba(255,255,255,<?=rand(1,4)/10?>);border-radius:50%;left:<?=$x?>px;top:<?=$y?>px;animation:float <?=$d?>s ease-in-out infinite;animation-delay:<?=rand(0,400)/100?>s;"></div>
      <?php endfor; ?>
    </div>
  </div>
  <div class="container" style="position:relative;z-index:1;">
    <div style="max-width:780px;">
      <div style="display:inline-flex;align-items:center;gap:0.5rem;background:rgba(255,107,53,0.1);border:1px solid rgba(255,107,53,0.2);border-radius:50px;padding:0.4rem 1rem;margin-bottom:2rem;">
        <span style="width:8px;height:8px;background:var(--accent);border-radius:50%;animation:pulse 1.5s ease-in-out infinite;"></span>
        <span style="font-size:0.8rem;color:var(--accent);font-weight:600">🎓 World-class Tech Education</span>
      </div>
      <h1 style="font-size:clamp(2.8rem,6vw,5rem);font-weight:800;line-height:1.1;margin-bottom:1.5rem;">
        Learn. Build.<br>
        <span style="background:linear-gradient(135deg,#ff6b35,#f59e0b);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Launch Your Career.</span>
      </h1>
      <p style="font-size:1.15rem;color:var(--text-muted);max-width:560px;margin-bottom:2.5rem;line-height:1.8;">
        Master in-demand tech skills with <strong style="color:var(--text)">20+ expert-led courses</strong> in web dev, AI, data science, and more. Free &amp; paid options available.
      </p>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;">
        <a href="courses.php" class="btn btn-accent btn-lg"><i class="fas fa-rocket"></i> Explore Courses</a>
        <?php if(!isLoggedIn()): ?>
        <a href="register.php" class="btn btn-ghost btn-lg"><i class="fas fa-user-plus"></i> Join Free</a>
        <?php endif; ?>
      </div>
      <!-- Stats -->
      <div style="display:flex;gap:2.5rem;margin-top:3.5rem;flex-wrap:wrap;">
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--accent)"><?= number_format($totalCourses) ?>+</div>
          <div style="font-size:0.8rem;color:var(--text-muted)">Courses</div>
        </div>
        <div style="width:1px;background:var(--border);"></div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--gold)"><?= number_format($totalStudents / 1000, 0) ?>K+</div>
          <div style="font-size:0.8rem;color:var(--text-muted)">Students</div>
        </div>
        <div style="width:1px;background:var(--border);"></div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--success)"><?= $totalInstructors ?>+</div>
          <div style="font-size:0.8rem;color:var(--text-muted)">Instructors</div>
        </div>
        <div style="width:1px;background:var(--border);"></div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:#a78bfa">4.8★</div>
          <div style="font-size:0.8rem;color:var(--text-muted)">Avg Rating</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section style="padding:4rem 0;background:rgba(0,0,0,0.2);">
  <div class="container">
    <div style="text-align:center;margin-bottom:2.5rem;">
      <h2 style="font-size:2rem;font-weight:800;margin-bottom:0.5rem">Browse by Category</h2>
      <p style="color:var(--text-muted)">Find courses that match your learning goals</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;">
      <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): 
        $icon = getCategoryIcon($cat['category']);
        $color = getCategoryColor($cat['category']);
      ?>
      <a href="courses.php?category=<?= urlencode($cat['category']) ?>" 
         style="display:flex;flex-direction:column;align-items:center;gap:0.7rem;padding:1.5rem 1rem;background:var(--card-bg);border:1px solid var(--border);border-radius:14px;transition:all 0.3s;text-align:center;"
         onmouseover="this.style.borderColor='<?=$color?>';this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 30px rgba(0,0,0,0.3)'" 
         onmouseout="this.style.borderColor='rgba(255,255,255,0.08)';this.style.transform='none';this.style.boxShadow='none'">
        <div style="font-size:2rem"><?= $icon ?></div>
        <div style="font-size:0.8rem;font-weight:600;color:var(--text)"><?= $cat['category'] ?></div>
        <div style="font-size:0.72rem;color:var(--text-muted)"><?= $cat['cnt'] ?> courses</div>
      </a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- FEATURED COURSES -->
<section style="padding:4rem 0;">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2.5rem;flex-wrap:wrap;gap:1rem;">
      <div>
        <h2 style="font-size:2rem;font-weight:800;margin-bottom:0.3rem">Featured Courses</h2>
        <p style="color:var(--text-muted)">Handpicked courses by our experts</p>
      </div>
      <a href="courses.php" class="btn btn-ghost">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem;">
      <?php while($course = $featured->fetch_assoc()): 
        $color = getCategoryColor($course['category']);
        $icon = getCategoryIcon($course['category']);
      ?>
      <div class="card" style="display:flex;flex-direction:column;">
        <!-- Course Header -->
        <div style="height:160px;background:linear-gradient(135deg,<?=$color?>22,<?=$color?>44);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
          <div style="font-size:4rem;opacity:0.8"><?= $icon ?></div>
          <div style="position:absolute;top:1rem;right:1rem;">
            <?php if($course['is_free']): ?>
              <span class="badge badge-free">FREE</span>
            <?php else: ?>
              <span class="badge badge-paid">₹<?= number_format($course['price']) ?></span>
            <?php endif; ?>
          </div>
          <div style="position:absolute;top:1rem;left:1rem;">
            <span class="badge badge-level"><?= $course['level'] ?></span>
          </div>
        </div>
        <!-- Course Body -->
        <div style="padding:1.3rem;flex:1;display:flex;flex-direction:column;">
          <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.6rem;">
            <span style="font-size:0.72rem;color:<?=$color?>;font-weight:600;background:<?=$color?>18;padding:0.2rem 0.5rem;border-radius:50px;"><?= $course['category'] ?></span>
          </div>
          <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.5rem;line-height:1.4"><?= htmlspecialchars($course['title']) ?></h3>
          <p style="font-size:0.8rem;color:var(--text-muted);line-height:1.6;flex:1;margin-bottom:1rem"><?= substr(htmlspecialchars($course['description']),0,100) ?>...</p>
          <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,<?=$color?>,<?=$color?>88);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700">
              <?= strtoupper(substr($course['instructor'],0,1)) ?>
            </div>
            <span style="font-size:0.8rem;color:var(--text-muted)"><?= htmlspecialchars($course['instructor']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding-top:0.8rem;border-top:1px solid var(--border);">
            <div style="display:flex;gap:1rem;font-size:0.75rem;color:var(--text-muted)">
              <span><i class="fas fa-clock"></i> <?= $course['duration'] ?></span>
              <span><i class="fas fa-book"></i> <?= $course['total_lessons'] ?> lessons</span>
            </div>
            <div class="stars">
              <?php for($i=1;$i<=5;$i++) echo $i<=$course['rating']?'★':'☆'; ?>
              <span style="color:var(--text-muted);font-size:0.72rem">(<?= number_format($course['students_count']) ?>)</span>
            </div>
          </div>
        </div>
        <div style="padding:0 1.3rem 1.3rem;">
          <a href="course.php?slug=<?= $course['slug'] ?>" class="btn btn-ghost w-full" style="justify-content:center">View Course <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <div style="text-align:center;margin-top:2rem;">
      <a href="courses.php" class="btn btn-accent btn-lg">See All 20 Courses <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<!-- WHY FINTEBIT -->
<section style="padding:4rem 0;background:rgba(0,0,0,0.2);">
  <div class="container">
    <div style="text-align:center;margin-bottom:3rem;">
      <h2 style="font-size:2rem;font-weight:800;margin-bottom:0.5rem">Why Choose Fintebit?</h2>
      <p style="color:var(--text-muted)">We're different from the rest</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;">
      <?php $features = [
        ['🎯','Expert Instructors','Learn from industry professionals with years of real-world experience.','#ff6b35'],
        ['📱','Learn Anywhere','Access courses on any device, at any time. Learn at your own pace.','#7c3aed'],
        ['🏆','Certificates','Earn recognized certificates to boost your career prospects.','#f59e0b'],
        ['💬','Community','Join thousands of learners. Ask questions, share projects, grow together.','#10b981'],
      ]; foreach($features as $f): ?>
      <div style="padding:2rem;background:var(--card-bg);border:1px solid var(--border);border-radius:16px;text-align:center;transition:all 0.3s;" onmouseover="this.style.borderColor='<?=$f[3]?>'" onmouseout="this.style.borderColor='var(--border)'">
        <div style="font-size:2.5rem;margin-bottom:1rem"><?= $f[0] ?></div>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.5rem"><?= $f[1] ?></h3>
        <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.7"><?= $f[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<?php if(!isLoggedIn()): ?>
<section style="padding:5rem 0;text-align:center;">
  <div class="container">
    <div style="max-width:600px;margin:0 auto;">
      <h2 style="font-size:2.5rem;font-weight:800;margin-bottom:1rem">Ready to Start Learning?</h2>
      <p style="color:var(--text-muted);font-size:1.1rem;margin-bottom:2rem">Join over 100,000 students already learning on Fintebit. Get started for free today!</p>
      <a href="register.php" class="btn btn-accent btn-lg"><i class="fas fa-rocket"></i> Start Learning Free</a>
    </div>
  </div>
</section>
<?php endif; ?>

<style>
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.7;transform:scale(1.05)} }
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-20px)} }
</style>

<?php include 'includes/footer.php'; ?>
