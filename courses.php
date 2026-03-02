<?php
define('INCLUDED', true);
require_once 'includes/config.php';
$pageTitle = 'All Courses';

$where = "WHERE status='active'";
$params = [];
$types = '';

if(!empty($_GET['category'])) {
    $cat = sanitize($_GET['category']);
    $where .= " AND category=?";
    $params[] = $cat; $types .= 's';
}
if(!empty($_GET['level'])) {
    $lvl = sanitize($_GET['level']);
    $where .= " AND level=?";
    $params[] = $lvl; $types .= 's';
}
if(!empty($_GET['type'])) {
    if($_GET['type']==='free') { $where .= " AND is_free=1"; }
    elseif($_GET['type']==='paid') { $where .= " AND is_free=0"; }
}
if(!empty($_GET['q'])) {
    $q = '%'.sanitize($_GET['q']).'%';
    $where .= " AND (title LIKE ? OR description LIKE ? OR category LIKE ?)";
    $params[] = $q; $params[] = $q; $params[] = $q; $types .= 'sss';
}

$sort = " ORDER BY students_count DESC";
if(!empty($_GET['sort'])) {
    if($_GET['sort']==='newest') $sort=" ORDER BY created_at DESC";
    elseif($_GET['sort']==='price_low') $sort=" ORDER BY price ASC";
    elseif($_GET['sort']==='price_high') $sort=" ORDER BY price DESC";
    elseif($_GET['sort']==='rating') $sort=" ORDER BY rating DESC";
}

$sql = "SELECT * FROM courses $where $sort";
if($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $courses = $stmt->get_result();
} else {
    $courses = $conn->query($sql);
}
$total = $courses->num_rows;

$allCats = $conn->query("SELECT DISTINCT category FROM courses ORDER BY category");
?>
<?php include 'includes/header.php'; ?>

<div style="padding:3rem 0 2rem;background:rgba(0,0,0,0.3);">
  <div class="container">
    <h1 style="font-size:2.2rem;font-weight:800;margin-bottom:0.5rem">All Courses</h1>
    <p style="color:var(--text-muted)"><?= $total ?> courses available</p>
    <!-- Search Bar -->
    <form method="GET" style="margin-top:1.5rem;display:flex;gap:0.8rem;flex-wrap:wrap;">
      <input type="text" name="q" value="<?= isset($_GET['q'])?htmlspecialchars($_GET['q']):'' ?>" class="form-control" placeholder="Search courses..." style="flex:1;min-width:200px;max-width:400px">
      <select name="category" class="form-control" style="width:auto">
        <option value="">All Categories</option>
        <?php while($c=$allCats->fetch_assoc()): ?>
        <option value="<?=$c['category']?>" <?=isset($_GET['category'])&&$_GET['category']==$c['category']?'selected':''?>><?=$c['category']?></option>
        <?php endwhile; ?>
      </select>
      <select name="level" class="form-control" style="width:auto">
        <option value="">All Levels</option>
        <option value="Beginner" <?=isset($_GET['level'])&&$_GET['level']=='Beginner'?'selected':''?>>Beginner</option>
        <option value="Intermediate" <?=isset($_GET['level'])&&$_GET['level']=='Intermediate'?'selected':''?>>Intermediate</option>
        <option value="Advanced" <?=isset($_GET['level'])&&$_GET['level']=='Advanced'?'selected':''?>>Advanced</option>
      </select>
      <select name="type" class="form-control" style="width:auto">
        <option value="">All Types</option>
        <option value="free" <?=isset($_GET['type'])&&$_GET['type']=='free'?'selected':''?>>Free</option>
        <option value="paid" <?=isset($_GET['type'])&&$_GET['type']=='paid'?'selected':''?>>Paid</option>
      </select>
      <select name="sort" class="form-control" style="width:auto">
        <option value="">Most Popular</option>
        <option value="newest" <?=isset($_GET['sort'])&&$_GET['sort']=='newest'?'selected':''?>>Newest</option>
        <option value="rating" <?=isset($_GET['sort'])&&$_GET['sort']=='rating'?'selected':''?>>Top Rated</option>
        <option value="price_low" <?=isset($_GET['sort'])&&$_GET['sort']=='price_low'?'selected':''?>>Price: Low</option>
        <option value="price_high" <?=isset($_GET['sort'])&&$_GET['sort']=='price_high'?'selected':''?>>Price: High</option>
      </select>
      <button type="submit" class="btn btn-accent"><i class="fas fa-search"></i> Filter</button>
      <a href="courses.php" class="btn btn-ghost">Reset</a>
    </form>
  </div>
</div>

<div class="container" style="padding:2.5rem 1.5rem;">
  <?php if($total === 0): ?>
    <div style="text-align:center;padding:4rem;color:var(--text-muted)">
      <div style="font-size:4rem;margin-bottom:1rem">🔍</div>
      <h3 style="margin-bottom:0.5rem">No courses found</h3>
      <p>Try different search terms or filters</p>
      <a href="courses.php" class="btn btn-accent mt-2">View All Courses</a>
    </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:1.5rem;">
    <?php while($course = $courses->fetch_assoc()): 
      $color = getCategoryColor($course['category']);
      $icon = getCategoryIcon($course['category']);
      $enrolled = isLoggedIn() ? isEnrolled($conn, $_SESSION['user_id'], $course['id']) : false;
    ?>
    <div class="card" style="display:flex;flex-direction:column;">
      <div style="height:150px;background:linear-gradient(135deg,<?=$color?>25,<?=$color?>45);display:flex;align-items:center;justify-content:center;position:relative;">
        <div style="font-size:3.5rem"><?=$icon?></div>
        <div style="position:absolute;top:0.8rem;right:0.8rem">
          <?php if($course['is_free']): ?>
            <span class="badge badge-free"><i class="fas fa-gift"></i> FREE</span>
          <?php else: ?>
            <span class="badge badge-paid">₹<?= number_format($course['price']) ?></span>
          <?php endif; ?>
        </div>
        <?php if($enrolled): ?>
        <div style="position:absolute;top:0.8rem;left:0.8rem">
          <span class="badge" style="background:rgba(16,185,129,0.9);color:white;border:none"><i class="fas fa-check"></i> Enrolled</span>
        </div>
        <?php endif; ?>
      </div>
      <div style="padding:1.2rem;flex:1;display:flex;flex-direction:column;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.6rem;">
          <span style="font-size:0.72rem;color:<?=$color?>;font-weight:600;background:<?=$color?>18;padding:0.2rem 0.5rem;border-radius:50px"><?=$course['category']?></span>
          <span class="badge badge-level" style="font-size:0.68rem"><?=$course['level']?></span>
        </div>
        <h3 style="font-size:0.95rem;font-weight:700;margin-bottom:0.4rem;line-height:1.4"><?=htmlspecialchars($course['title'])?></h3>
        <p style="font-size:0.78rem;color:var(--text-muted);line-height:1.6;flex:1;margin-bottom:0.8rem"><?=substr(htmlspecialchars($course['description']),0,90)?>...</p>
        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.8rem">By <strong style="color:var(--text)"><?=htmlspecialchars($course['instructor'])?></strong></div>
        <div style="display:flex;gap:1rem;font-size:0.72rem;color:var(--text-muted);margin-bottom:0.8rem">
          <span><i class="fas fa-clock"></i> <?=$course['duration']?></span>
          <span><i class="fas fa-book"></i> <?=$course['total_lessons']?> lessons</span>
          <span><i class="fas fa-users"></i> <?=number_format($course['students_count'])?></span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <div class="stars">
            <?php for($i=1;$i<=5;$i++) echo round($course['rating'])>=$i?'★':'☆'; ?>
            <span style="color:var(--text-muted);font-size:0.72rem"> <?=$course['rating']?></span>
          </div>
          <a href="course.php?slug=<?=$course['slug']?>" class="btn btn-ghost btn-sm">View <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
