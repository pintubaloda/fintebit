<?php
define('INCLUDED', true);
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('../login.php');

$courseId = (int)($_GET['course'] ?? 0);
$lessonId = (int)($_GET['lesson'] ?? 0);
if (!$courseId || !$lessonId) {
    redirect('dashboard.php');
}

$userId = (int)$_SESSION['user_id'];
$enrollmentStmt = $conn->prepare("SELECT c.id, c.title, e.progress FROM courses c JOIN enrollments e ON e.course_id=c.id WHERE c.id=? AND e.user_id=?");
$enrollmentStmt->bind_param('ii', $courseId, $userId);
$enrollmentStmt->execute();
$course = $enrollmentStmt->get_result()->fetch_assoc();
if (!$course) {
    redirect('dashboard.php');
}

$lessonStmt = $conn->prepare('SELECT id, title, content FROM lessons WHERE id=? AND course_id=?');
$lessonStmt->bind_param('ii', $lessonId, $courseId);
$lessonStmt->execute();
$lesson = $lessonStmt->get_result()->fetch_assoc();
if (!$lesson) {
    redirect("learn.php?course=$courseId");
}

$quizStmt = $conn->prepare('SELECT * FROM quizzes WHERE lesson_id=? LIMIT 1');
$quizStmt->bind_param('i', $lessonId);
$quizStmt->execute();
$quiz = $quizStmt->get_result()->fetch_assoc();

if (!$quiz) {
    $createQuiz = $conn->prepare('INSERT INTO quizzes (course_id, lesson_id, title, pass_percentage) VALUES (?, ?, ?, 60)');
    $quizTitle = 'Quiz: ' . $lesson['title'];
    $createQuiz->bind_param('iis', $courseId, $lessonId, $quizTitle);
    $createQuiz->execute();
    $quizId = (int)$conn->insert_id;

    $insertQuestion = $conn->prepare('INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $questions = [
        [
            'What is the main purpose of this lesson?',
            'Understand and apply core concepts',
            'Skip the fundamentals',
            'Avoid practical work',
            'Ignore lesson objectives',
            'A',
        ],
        [
            'Which strategy is best for learning this topic?',
            'Memorize without practice',
            'Practice examples and check understanding',
            'Avoid feedback',
            'Only read headings',
            'B',
        ],
        [
            'How should you complete this lesson effectively?',
            'Stop after one section',
            'Skip assessments',
            'Apply concepts in a mini task',
            'Avoid revision',
            'C',
        ],
    ];
    foreach ($questions as $q) {
        $insertQuestion->bind_param('issssss', $quizId, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
        $insertQuestion->execute();
    }

    $quizStmt->execute();
    $quiz = $quizStmt->get_result()->fetch_assoc();
}

if (!$quiz) {
    redirect("learn.php?course=$courseId&lesson=$lessonId");
}

$questionsRes = $conn->query('SELECT * FROM quiz_questions WHERE quiz_id=' . (int)$quiz['id'] . ' ORDER BY id ASC');
$questions = $questionsRes ? $questionsRes->fetch_all(MYSQLI_ASSOC) : [];

$attemptRes = $conn->query('SELECT * FROM quiz_attempts WHERE user_id=' . $userId . ' AND quiz_id=' . (int)$quiz['id'] . ' ORDER BY attempted_at DESC LIMIT 1');
$lastAttempt = $attemptRes ? $attemptRes->fetch_assoc() : null;

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($questions)) {
    $score = 0;
    foreach ($questions as $question) {
        $selected = $_POST['q'][$question['id']] ?? '';
        if ($selected === $question['correct_option']) {
            $score++;
        }
    }

    $totalQuestions = count($questions);
    $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100, 2) : 0;
    $passPercentage = (int)$quiz['pass_percentage'];
    $passed = $percentage >= $passPercentage ? 1 : 0;

    $attemptStmt = $conn->prepare('INSERT INTO quiz_attempts (user_id, quiz_id, score, total_questions, percentage, passed) VALUES (?, ?, ?, ?, ?, ?)');
    $attemptStmt->bind_param('iiiidi', $userId, $quiz['id'], $score, $totalQuestions, $percentage, $passed);
    $attemptStmt->execute();

    if ($passed) {
        $completeStmt = $conn->prepare('INSERT IGNORE INTO lesson_progress (user_id, lesson_id, course_id, completed, completed_at) VALUES (?, ?, ?, 1, NOW())');
        $completeStmt->bind_param('iii', $userId, $lessonId, $courseId);
        $completeStmt->execute();

        $doneRes = $conn->query('SELECT COUNT(*) as c FROM lesson_progress WHERE user_id=' . $userId . ' AND course_id=' . $courseId . ' AND completed=1');
        $done = $doneRes ? (int)$doneRes->fetch_assoc()['c'] : 0;
        $totalLessonsRes = $conn->query('SELECT COUNT(*) as c FROM lessons WHERE course_id=' . $courseId);
        $totalLessons = $totalLessonsRes ? (int)$totalLessonsRes->fetch_assoc()['c'] : 0;
        $progress = $totalLessons > 0 ? min(100, (int)round(($done / $totalLessons) * 100)) : 0;
        $conn->query('UPDATE enrollments SET progress=' . $progress . ' WHERE user_id=' . $userId . ' AND course_id=' . $courseId);
    }

    $result = [
        'score' => $score,
        'total' => $totalQuestions,
        'percentage' => $percentage,
        'passed' => $passed,
        'required' => (int)$quiz['pass_percentage'],
    ];
}

$pageTitle = 'Quiz: ' . $lesson['title'];
?>
<?php include '../includes/header.php'; ?>
<div style="padding:2.5rem 0;">
  <div class="container" style="max-width:900px;">
    <a href="learn.php?course=<?= $courseId ?>&lesson=<?= $lessonId ?>" class="btn btn-ghost btn-sm" style="margin-bottom:1rem;"><i class="fas fa-arrow-left"></i> Back to Lesson</a>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:0.4rem;"><?= htmlspecialchars($quiz['title']) ?></h1>
    <p style="color:var(--text-muted);margin-bottom:1.4rem;">Course: <?= htmlspecialchars($course['title']) ?> | Pass mark: <?= (int)$quiz['pass_percentage'] ?>%</p>

    <?php if ($lastAttempt): ?>
      <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:0.9rem 1rem;margin-bottom:1rem;font-size:0.9rem;">
        Last attempt: <strong><?= (int)$lastAttempt['score'] ?>/<?= (int)$lastAttempt['total_questions'] ?></strong> (<?= (float)$lastAttempt['percentage'] ?>%)
        <?php if ((int)$lastAttempt['passed'] === 1): ?>
          <span style="color:var(--success);font-weight:700;"> - Passed</span>
        <?php else: ?>
          <span style="color:#f59e0b;font-weight:700;"> - Retry</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($result): ?>
      <div style="background:<?= $result['passed'] ? 'rgba(16,185,129,0.12)' : 'rgba(245,158,11,0.12)' ?>;border:1px solid <?= $result['passed'] ? 'rgba(16,185,129,0.4)' : 'rgba(245,158,11,0.4)' ?>;border-radius:12px;padding:1rem 1.2rem;margin-bottom:1.2rem;">
        <strong><?= $result['passed'] ? 'Quiz passed' : 'Quiz not passed' ?></strong>
        <div style="margin-top:0.35rem;">Score: <?= $result['score'] ?>/<?= $result['total'] ?> (<?= $result['percentage'] ?>%). Required: <?= $result['required'] ?>%.</div>
      </div>
    <?php endif; ?>

    <?php if (empty($questions)): ?>
      <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:1.2rem;">No quiz questions found for this lesson.</div>
    <?php else: ?>
      <form method="POST">
        <?php foreach ($questions as $idx => $q): ?>
          <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:1.2rem;margin-bottom:1rem;">
            <div style="font-weight:700;margin-bottom:0.9rem;"><?= ($idx + 1) ?>. <?= htmlspecialchars($q['question_text']) ?></div>
            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
              <?php $field = 'option_' . strtolower($opt); ?>
              <label style="display:flex;align-items:flex-start;gap:0.6rem;margin-bottom:0.55rem;cursor:pointer;">
                <input type="radio" name="q[<?= (int)$q['id'] ?>]" value="<?= $opt ?>" required>
                <span><strong><?= $opt ?>.</strong> <?= htmlspecialchars($q[$field]) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-accent"><i class="fas fa-check-circle"></i> Submit Quiz</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
