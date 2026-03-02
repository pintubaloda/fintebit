<?php
define('INCLUDED', true);
require_once '../includes/config.php';
if(!isLoggedIn()) redirect('../login.php');

$courseId = (int)($_GET['course'] ?? 0);
if(!$courseId) redirect('dashboard.php');

// Check enrollment
$stmt = $conn->prepare("SELECT c.*,e.progress FROM courses c JOIN enrollments e ON e.course_id=c.id WHERE c.id=? AND e.user_id=?");
$stmt->bind_param("ii",$courseId,$_SESSION['user_id']);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if(!$course) redirect('dashboard.php');

$pageTitle = 'Learn: '.$course['title'];
$lessons = $conn->query("SELECT * FROM lessons WHERE course_id=$courseId ORDER BY order_num");
$lessonId = (int)($_GET['lesson'] ?? 0);
$currentLesson = null;
$allLessons = [];
if ($lessons instanceof mysqli_result) {
    while($l=$lessons->fetch_assoc()) { $allLessons[] = $l; if($l['id']==$lessonId) $currentLesson=$l; }
}
if(!$currentLesson && count($allLessons)>0) $currentLesson = $allLessons[0];

// Mark complete
if(isset($_GET['complete']) && $currentLesson) {
    $stmt2=$conn->prepare("INSERT IGNORE INTO lesson_progress(user_id,lesson_id,course_id,completed,completed_at) VALUES(?,?,?,1,NOW())");
    if ($stmt2) {
        $stmt2->bind_param("iii",$_SESSION['user_id'],$currentLesson['id'],$courseId);
        $stmt2->execute();
    }
    $doneRes = $conn->query("SELECT COUNT(*) as c FROM lesson_progress WHERE user_id={$_SESSION['user_id']} AND course_id=$courseId AND completed=1");
    $done = $doneRes ? (int)$doneRes->fetch_assoc()['c'] : 0;
    $total=count($allLessons); $prog=$total>0?min(100,round($done/$total*100)):0;
    $conn->query("UPDATE enrollments SET progress=$prog WHERE user_id={$_SESSION['user_id']} AND course_id=$courseId");
    redirect("learn.php?course=$courseId&lesson={$currentLesson['id']}");
}

// Get completed lessons
$completed = [];
$r=$conn->query("SELECT lesson_id FROM lesson_progress WHERE user_id={$_SESSION['user_id']} AND course_id=$courseId AND completed=1");
if ($r) {
    while($row=$r->fetch_assoc()) $completed[]=$row['lesson_id'];
}

$quizByLesson = [];
if (!empty($allLessons)) {
    $lessonIds = array_map(function($l){ return (int)$l['id']; }, $allLessons);
    $idList = implode(',', $lessonIds);
    $quizRes = $conn->query("
        SELECT q.id, q.lesson_id, q.pass_percentage,
               MAX(CASE WHEN qa.user_id={$_SESSION['user_id']} AND qa.passed=1 THEN 1 ELSE 0 END) AS passed
        FROM quizzes q
        LEFT JOIN quiz_attempts qa ON qa.quiz_id=q.id
        WHERE q.lesson_id IN ($idList)
        GROUP BY q.id, q.lesson_id, q.pass_percentage
    ");
    if ($quizRes) {
        while ($q = $quizRes->fetch_assoc()) {
            $quizByLesson[(int)$q['lesson_id']] = [
                'id' => (int)$q['id'],
                'pass_percentage' => (int)$q['pass_percentage'],
                'passed' => (int)$q['passed'] === 1,
            ];
        }
    }
}
$currentQuiz = $currentLesson ? ($quizByLesson[(int)$currentLesson['id']] ?? null) : null;
$currentIndex = 0;
if ($currentLesson) {
    foreach ($allLessons as $idx => $lessonRow) {
        if ((int)$lessonRow['id'] === (int)$currentLesson['id']) {
            $currentIndex = $idx + 1;
            break;
        }
    }
}
$color = getCategoryColor($course['category']);
?>
<?php include '../includes/header.php'; ?>
<style>
.learn-layout{display:grid;grid-template-columns:280px 1fr;gap:0;min-height:calc(100vh - var(--nav-height));}
.sidebar{background:#0d0d24;border-right:1px solid var(--border);overflow-y:auto;height:calc(100vh - var(--nav-height));position:sticky;top:var(--nav-height);}
.lesson-item{display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1rem;cursor:pointer;transition:background 0.2s;text-decoration:none;color:var(--text);}
.lesson-item:hover{background:rgba(255,255,255,0.04)}
.lesson-item.active{background:rgba(255,107,53,0.1);border-right:2px solid var(--accent)}
@media(max-width:768px){.learn-layout{grid-template-columns:1fr}.sidebar{height:auto;position:relative}}
</style>
<div class="learn-layout">
  <!-- Sidebar -->
  <div class="sidebar">
    <div style="padding:1.2rem 1rem;border-bottom:1px solid var(--border);">
      <a href="dashboard.php" style="font-size:0.78rem;color:var(--text-muted);display:flex;align-items:center;gap:0.4rem;margin-bottom:0.8rem"><i class="fas fa-arrow-left"></i> Back</a>
      <h3 style="font-size:0.9rem;font-weight:700;line-height:1.3;margin-bottom:0.5rem"><?=htmlspecialchars($course['title'])?></h3>
      <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;margin-bottom:0.3rem">
        <div style="height:100%;width:<?=$course['progress']?>%;background:linear-gradient(90deg,var(--accent),var(--gold));border-radius:3px"></div>
      </div>
      <div style="font-size:0.72rem;color:var(--text-muted)"><?=$course['progress']?>% complete</div>
    </div>
    <?php foreach($allLessons as $i=>$l): 
      $isDone = in_array($l['id'],$completed);
      $isActive = $currentLesson && $l['id']==$currentLesson['id'];
    ?>
    <a href="learn.php?course=<?=$courseId?>&lesson=<?=$l['id']?>" class="lesson-item <?=$isActive?'active':''?>">
      <div style="width:26px;height:26px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:600;
        background:<?=$isDone?'var(--success)':($isActive?'var(--accent)':'rgba(255,255,255,0.08)')?>;
        color:<?=($isDone||$isActive)?'white':'var(--text-muted)'?>">
        <?=$isDone?'<i class="fas fa-check"></i>':($i+1)?>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:0.8rem;font-weight:<?=$isActive?'600':'400'?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($l['title'])?></div>
        <div style="display:flex;align-items:center;gap:0.45rem;">
          <?php if($l['duration']): ?><div style="font-size:0.7rem;color:var(--text-muted)"><?=$l['duration']?></div><?php endif; ?>
          <?php if(isset($quizByLesson[(int)$l['id']])): ?>
            <span style="font-size:0.62rem;border:1px solid rgba(255,255,255,0.2);border-radius:999px;padding:0.1rem 0.45rem;color:<?= $quizByLesson[(int)$l['id']]['passed'] ? 'var(--success)' : 'var(--gold)' ?>;">Quiz</span>
          <?php endif; ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Main Content -->
  <div style="padding:2rem;max-width:860px;">
    <?php if($currentLesson): ?>
    <div style="background:linear-gradient(135deg,<?=$color?>15,rgba(0,0,0,0.5));border:1px solid var(--border);border-radius:14px;padding:1.1rem 1.2rem;margin-bottom:1rem;">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:0.8rem;flex-wrap:wrap;">
        <div style="font-size:0.8rem;color:var(--text-muted);">Lesson <?= $currentIndex ?> of <?= count($allLessons) ?></div>
        <div style="font-size:0.78rem;color:var(--gold);"><i class="fas fa-clock"></i> <?= htmlspecialchars($currentLesson['duration'] ?: 'Self-paced') ?></div>
      </div>
    </div>
    
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;flex-wrap:wrap;gap:1rem;">
      <h1 style="font-size:1.5rem;font-weight:800"><?=htmlspecialchars($currentLesson['title'])?></h1>
      <?php if($currentQuiz): ?>
        <?php if($currentQuiz['passed']): ?>
          <span class="badge" style="background:rgba(16,185,129,0.15);color:var(--success);border:1px solid rgba(16,185,129,0.3)"><i class="fas fa-check-circle"></i> Quiz Passed</span>
        <?php else: ?>
          <a href="quiz.php?course=<?=$courseId?>&lesson=<?=$currentLesson['id']?>" class="btn btn-accent btn-sm"><i class="fas fa-question-circle"></i> Take Quiz</a>
        <?php endif; ?>
      <?php elseif(!in_array($currentLesson['id'],$completed)): ?>
      <a href="learn.php?course=<?=$courseId?>&lesson=<?=$currentLesson['id']?>&complete=1" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Mark Complete</a>
      <?php else: ?>
      <span class="badge" style="background:rgba(16,185,129,0.15);color:var(--success);border:1px solid rgba(16,185,129,0.3)"><i class="fas fa-check-circle"></i> Completed</span>
      <?php endif; ?>
    </div>
    
    <?php if($currentLesson['content']): ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:1.5rem;margin-bottom:1.5rem;">
      <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.8rem"><i class="fas fa-book-open" style="color:var(--accent)"></i> Lesson Content</h3>
      <p style="color:var(--text-muted);line-height:1.8;font-size:0.9rem"><?=nl2br(htmlspecialchars($currentLesson['content']))?></p>
    </div>
    <?php else: ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:1.5rem;margin-bottom:1.5rem;">
      <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.8rem"><i class="fas fa-book-open" style="color:var(--accent)"></i> Lesson Content</h3>
      <p style="color:var(--text-muted);line-height:1.8;font-size:0.9rem">This lesson is currently text-first and self-paced. Read the objectives, complete the practice step, and take the quiz to mark this lesson complete.</p>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div style="display:flex;justify-content:space-between;gap:1rem;">
      <?php 
      $curr_idx = array_search($currentLesson,$allLessons);
      $prev = $curr_idx>0?$allLessons[$curr_idx-1]:null;
      $next = isset($allLessons[$curr_idx+1])?$allLessons[$curr_idx+1]:null;
      ?>
      <?php if($prev): ?>
      <a href="learn.php?course=<?=$courseId?>&lesson=<?=$prev['id']?>" class="btn btn-ghost"><i class="fas fa-chevron-left"></i> Previous</a>
      <?php else: ?><div></div><?php endif; ?>
      <?php if($next): ?>
      <a href="learn.php?course=<?=$courseId?>&lesson=<?=$next['id']?>" class="btn btn-accent">Next <i class="fas fa-chevron-right"></i></a>
      <?php else: ?>
      <div style="text-align:center;padding:0.8rem 1.5rem;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;color:var(--success);font-weight:600">🎉 Course Complete!</div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:4rem"><p style="color:var(--text-muted)">No lessons available yet.</p></div>
    <?php endif; ?>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
