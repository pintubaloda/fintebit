<?php
mysqli_report(MYSQLI_REPORT_OFF);

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: 3306)));
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: ''));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'fintebit'));
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', getenv('SITE_NAME') ?: 'Fintebit');
}
if (!defined('SITE_URL')) {
    $siteUrl = getenv('SITE_URL');
    if (!$siteUrl) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $siteUrl = $scheme . '://' . $host;
    }
    define('SITE_URL', rtrim($siteUrl, '/'));
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_errno === 1049) {
    // Unknown database: try to create/select it when permissions allow.
    $bootstrap = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    if ($bootstrap->connect_error) {
        die('DB bootstrap failed: ' . $bootstrap->connect_error);
    }
    $dbEscaped = $bootstrap->real_escape_string(DB_NAME);
    if (!$bootstrap->query("CREATE DATABASE IF NOT EXISTS `$dbEscaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        die('Database not found. Set DB_NAME/MYSQLDATABASE to your Railway database name.');
    }
    $bootstrap->select_db(DB_NAME);
    $conn = $bootstrap;
}
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
ensureSchemaCompatibility($conn);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function redirect($url) {
    header("Location: $url");
    exit;
}
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
function isEnrolled($conn, $user_id, $course_id) {
    $stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
function getCategoryIcon($cat) {
    $icons = [
        'Web Dev' => '🌐', 'Programming' => '💻', 'AI & ML' => '🤖',
        'Data Science' => '📊', 'Design' => '🎨', 'Backend' => '⚙️',
        'Database' => '🗄️', 'Security' => '🔐', 'Mobile Dev' => '📱',
        'DevOps' => '🚀', 'Productivity' => '📈', 'Marketing' => '📣',
        'Blockchain' => '⛓️', 'default' => '📚'
    ];
    return $icons[$cat] ?? $icons['default'];
}
function getCategoryColor($cat) {
    $colors = [
        'Web Dev' => '#3b82f6', 'Programming' => '#8b5cf6', 'AI & ML' => '#ec4899',
        'Data Science' => '#f59e0b', 'Design' => '#06b6d4', 'Backend' => '#10b981',
        'Database' => '#6366f1', 'Security' => '#ef4444', 'Mobile Dev' => '#f97316',
        'DevOps' => '#14b8a6', 'Productivity' => '#22c55e', 'Marketing' => '#a855f7',
        'Blockchain' => '#f59e0b', 'default' => '#6b7280'
    ];
    return $colors[$cat] ?? $colors['default'];
}

function ensureSchemaCompatibility($conn) {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!$conn->query("SHOW TABLES LIKE 'courses'")->num_rows) {
        return;
    }

    // Ensure columns expected by front-end pages exist.
    $courseColumns = [];
    $cols = $conn->query("SHOW COLUMNS FROM courses");
    while ($row = $cols->fetch_assoc()) {
        $courseColumns[$row['Field']] = true;
    }

    if (!isset($courseColumns['slug'])) {
        $conn->query("ALTER TABLE courses ADD COLUMN slug VARCHAR(200) NULL");
        $conn->query("CREATE INDEX idx_courses_slug ON courses (slug)");
    }
    if (!isset($courseColumns['status'])) {
        $conn->query("ALTER TABLE courses ADD COLUMN status ENUM('active','inactive') DEFAULT 'active'");
    }
    if (!isset($courseColumns['total_lessons'])) {
        $conn->query("ALTER TABLE courses ADD COLUMN total_lessons INT DEFAULT 0");
    }
    if (!isset($courseColumns['students_count'])) {
        $conn->query("ALTER TABLE courses ADD COLUMN students_count INT DEFAULT 0");
    }

    // Backfill legacy column values to modern names.
    $courseColumns = [];
    $cols = $conn->query("SHOW COLUMNS FROM courses");
    while ($row = $cols->fetch_assoc()) {
        $courseColumns[$row['Field']] = true;
    }
    if (isset($courseColumns['lessons']) && isset($courseColumns['total_lessons'])) {
        $conn->query("UPDATE courses SET total_lessons = lessons WHERE total_lessons = 0");
    }
    if (isset($courseColumns['students']) && isset($courseColumns['students_count'])) {
        $conn->query("UPDATE courses SET students_count = students WHERE students_count = 0");
    }
    if (isset($courseColumns['status'])) {
        $conn->query("UPDATE courses SET status='active' WHERE status IS NULL OR status=''");
    }

    if (isset($courseColumns['slug'])) {
        $res = $conn->query("SELECT id, title FROM courses WHERE slug IS NULL OR slug=''");
        while ($res && ($row = $res->fetch_assoc())) {
            $slug = strtolower(trim($row['title']));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            if ($slug === '') {
                $slug = 'course';
            }
            $slug = $slug . '-' . (int)$row['id'];
            $stmt = $conn->prepare("UPDATE courses SET slug=? WHERE id=?");
            $id = (int)$row['id'];
            $stmt->bind_param('si', $slug, $id);
            $stmt->execute();
        }
    }

    // Required by dashboards and enrollment/payment flows.
    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_status ENUM('pending','completed','failed') DEFAULT 'completed',
        ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('new','reviewed','closed') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS lesson_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        lesson_id INT NOT NULL,
        course_id INT NOT NULL,
        completed TINYINT(1) DEFAULT 1,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_progress (user_id, lesson_id, course_id)
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS quizzes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        lesson_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        pass_percentage INT DEFAULT 60,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_lesson_quiz (lesson_id)
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS quiz_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        question_text TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NOT NULL,
        option_d VARCHAR(255) NOT NULL,
        correct_option CHAR(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS quiz_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        quiz_id INT NOT NULL,
        score INT NOT NULL,
        total_questions INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        passed TINYINT(1) DEFAULT 0,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS lesson_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lesson_id INT NOT NULL,
        page_no INT NOT NULL,
        page_title VARCHAR(255) NOT NULL,
        page_content LONGTEXT NOT NULL,
        UNIQUE KEY uniq_lesson_page (lesson_id, page_no)
    )");
    $orderCols = [];
    $orderMeta = $conn->query("SHOW COLUMNS FROM orders");
    while ($orderMeta && ($row = $orderMeta->fetch_assoc())) {
        $orderCols[$row['Field']] = true;
    }
    if (!isset($orderCols['utr_no'])) {
        $conn->query("ALTER TABLE orders ADD COLUMN utr_no VARCHAR(120) DEFAULT ''");
    }
    if (!isset($orderCols['screenshot_path'])) {
        $conn->query("ALTER TABLE orders ADD COLUMN screenshot_path VARCHAR(500) DEFAULT ''");
    }
    if (!isset($orderCols['verified_at'])) {
        $conn->query("ALTER TABLE orders ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL");
    }

    if ($conn->query("SHOW TABLES LIKE 'lessons'")->num_rows) {
        $lessonColumns = [];
        $lcols = $conn->query("SHOW COLUMNS FROM lessons");
        while ($row = $lcols->fetch_assoc()) {
            $lessonColumns[$row['Field']] = true;
        }
        if (!isset($lessonColumns['is_preview'])) {
            $conn->query("ALTER TABLE lessons ADD COLUMN is_preview TINYINT(1) DEFAULT 0");
        }
        if (!isset($lessonColumns['youtube_url'])) {
            $conn->query("ALTER TABLE lessons ADD COLUMN youtube_url VARCHAR(500) DEFAULT ''");
        }

        $conn->query("CREATE TABLE IF NOT EXISTS app_meta (
            meta_key VARCHAR(100) PRIMARY KEY,
            meta_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        runCommercialPolicyMigrationIfNeeded($conn);
        runContentMigrationIfNeeded($conn, $courseColumns, $lessonColumns);
    }
}

function runCommercialPolicyMigrationIfNeeded($conn) {
    $policyVersion = 'pricing_policy_v1';
    $meta = $conn->query("SELECT meta_value FROM app_meta WHERE meta_key='commercial_policy_version' LIMIT 1");
    $current = ($meta && $meta->num_rows > 0) ? $meta->fetch_assoc()['meta_value'] : '';
    if ($current === $policyVersion) {
        return;
    }

    // Reset enrollments/subscriptions and related progress as requested.
    $conn->query("DELETE FROM lesson_progress");
    $conn->query("DELETE FROM enrollments");
    $conn->query("DELETE FROM quiz_attempts");
    $conn->query("DELETE FROM orders");
    $conn->query("UPDATE courses SET students_count=0");

    // Commercial rule: all paid except JavaScript ES6+.
    $conn->query("UPDATE courses SET is_free=0");
    $conn->query("UPDATE courses SET price=CASE WHEN price<=0 THEN 999 ELSE price END WHERE title<>'JavaScript ES6+'");
    $conn->query("UPDATE courses SET is_free=1, price=0 WHERE title='JavaScript ES6+'");

    $stmt = $conn->prepare("INSERT INTO app_meta (meta_key, meta_value) VALUES ('commercial_policy_version', ?) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)");
    $stmt->bind_param("s", $policyVersion);
    $stmt->execute();
}

function runContentMigrationIfNeeded($conn, $courseColumns, $lessonColumns) {
    $contentVersion = 'content_v11';
    $meta = $conn->query("SELECT meta_value FROM app_meta WHERE meta_key='content_version' LIMIT 1");
    $current = ($meta && $meta->num_rows > 0) ? $meta->fetch_assoc()['meta_value'] : '';

    if ($current === $contentVersion) {
        return;
    }

    seedDefaultLessonsAndQuizzes($conn, $courseColumns, $lessonColumns);
    applyCustomJavascriptLesson2ContentAndQuiz($conn);
    applyCustomJavascriptLessons3To6ContentAndQuiz($conn);
    $stmt = $conn->prepare("INSERT INTO app_meta (meta_key, meta_value) VALUES ('content_version', ?) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)");
    $stmt->bind_param("s", $contentVersion);
    $stmt->execute();
}

function seedDefaultLessonsAndQuizzes($conn, $courseColumns, $lessonColumns) {
    $courses = $conn->query("SELECT id, title FROM courses");
    if (!$courses) {
        return;
    }

    $countStmt = $conn->prepare("SELECT COUNT(*) as c FROM lessons WHERE course_id=?");
    $insertLesson = $conn->prepare("INSERT INTO lessons (course_id, title, content, duration, order_num, is_preview) VALUES (?, ?, ?, ?, ?, ?)");
    $updateCounts = $conn->prepare("UPDATE courses SET total_lessons=?, lessons=? WHERE id=?");
    $updateOnlyTotal = $conn->prepare("UPDATE courses SET total_lessons=? WHERE id=?");
    $updateOnlyLegacy = $conn->prepare("UPDATE courses SET lessons=? WHERE id=?");

    $templates = [
        ['Foundations', 'Build core understanding before implementation.'],
        ['Core Workflow', 'Learn the step-by-step workflow used by practitioners.'],
        ['Guided Practice', 'Apply the concept with practical, supervised exercises.'],
        ['Project Application', 'Use the concept in a realistic mini-project scenario.'],
        ['Troubleshooting', 'Identify and fix common mistakes with confidence.'],
        ['Review and Assessment', 'Consolidate learning and prepare for assessment.'],
    ];

    while ($course = $courses->fetch_assoc()) {
        $courseId = (int)$course['id'];
        $courseTitle = $course['title'];

        $countStmt->bind_param("i", $courseId);
        $countStmt->execute();
        $existing = (int)$countStmt->get_result()->fetch_assoc()['c'];

        if ($existing === 0) {
            $order = 1;
            foreach ($templates as $template) {
                $title = $template[0] . ': ' . $courseTitle;
                $content = generateLessonContent($courseTitle, $title, $order, $template[1]);
                $duration = (string)(10 + $order * 5) . ' min';
                $preview = $order === 1 ? 1 : 0;
                $insertLesson->bind_param("isssii", $courseId, $title, $content, $duration, $order, $preview);
                $insertLesson->execute();
                $newLessonId = (int)$conn->insert_id;
                if ($newLessonId > 0) {
                    storeLessonPages($conn, $newLessonId, buildLessonPages($courseTitle, $title, $order, $template[1]));
                }
                $order++;
            }
            $lessonCount = count($templates);
            if (isset($courseColumns['total_lessons']) && isset($courseColumns['lessons'])) {
                $updateCounts->bind_param("iii", $lessonCount, $lessonCount, $courseId);
                $updateCounts->execute();
            } elseif (isset($courseColumns['total_lessons'])) {
                $updateOnlyTotal->bind_param("ii", $lessonCount, $courseId);
                $updateOnlyTotal->execute();
            } elseif (isset($courseColumns['lessons'])) {
                $updateOnlyLegacy->bind_param("ii", $lessonCount, $courseId);
                $updateOnlyLegacy->execute();
            }
        }

        // Upgrade old generic/short lesson content to richer content.
        $existingLessons = $conn->prepare("SELECT id, title, content, order_num FROM lessons WHERE course_id=? ORDER BY order_num ASC");
        $existingLessons->bind_param("i", $courseId);
        $existingLessons->execute();
        $rows = $existingLessons->get_result();
        $updateLesson = $conn->prepare("UPDATE lessons SET content=? WHERE id=?");
        $updateLessonVideo = $conn->prepare("UPDATE lessons SET youtube_url=? WHERE id=?");
        while ($lesson = $rows->fetch_assoc()) {
            $lessonId = (int)$lesson['id'];
            $newContent = generateLessonContent($courseTitle, $lesson['title'], (int)$lesson['order_num'], 'Practical lesson content');
            $updateLesson->bind_param("si", $newContent, $lessonId);
            $updateLesson->execute();
            storeLessonPages($conn, $lessonId, buildLessonPages($courseTitle, $lesson['title'], (int)$lesson['order_num'], 'Practical lesson content'));

            $videoRes = $conn->query("SELECT youtube_url FROM lessons WHERE id=$lessonId LIMIT 1");
            $youtubeUrl = '';
            if ($videoRes && $videoRes->num_rows > 0) {
                $youtubeUrl = trim((string)$videoRes->fetch_assoc()['youtube_url']);
            }
            if ($youtubeUrl === '' || strpos($youtubeUrl, 'youtube.com/results?search_query=') !== false) {
                $autoVideoUrl = buildAutoLessonVideoUrl($courseTitle, $lesson['title'], (int)$lesson['order_num']);
                $updateLessonVideo->bind_param("si", $autoVideoUrl, $lessonId);
                $updateLessonVideo->execute();
            }
        }
    }

    $lessonQuizRows = $conn->query("SELECT l.id as lesson_id, l.course_id, l.order_num, l.title as lesson_title, c.title as course_title, q.id as quiz_id
                                    FROM lessons l
                                    JOIN courses c ON c.id=l.course_id
                                    LEFT JOIN quizzes q ON q.lesson_id=l.id
                                    ORDER BY l.course_id, l.order_num");
    if (!$lessonQuizRows) {
        return;
    }

    $insertQuiz = $conn->prepare("INSERT INTO quizzes (course_id, lesson_id, title, pass_percentage) VALUES (?, ?, ?, 60)");
    $insertQuestion = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

    while ($row = $lessonQuizRows->fetch_assoc()) {
        $courseId = (int)$row['course_id'];
        $lessonId = (int)$row['lesson_id'];
        $orderNum = (int)$row['order_num'];
        $lessonTitle = $row['lesson_title'];
        $courseTitle = $row['course_title'];
        $quizTitle = 'Quiz: ' . $lessonTitle;
        $quizId = (int)($row['quiz_id'] ?? 0);

        if ($quizId <= 0) {
            $insertQuiz->bind_param("iis", $courseId, $lessonId, $quizTitle);
            $insertQuiz->execute();
            $quizId = (int)$conn->insert_id;
        }
        if ($quizId <= 0) {
            continue;
        }

        // Force-refresh questions so every lesson gets its own quiz set.
        $conn->query("DELETE FROM quiz_questions WHERE quiz_id=$quizId");
        $questions = buildLessonQuizQuestions($courseTitle, $lessonTitle, $orderNum, 'Lesson-specific understanding');
        foreach ($questions as $q) {
            $insertQuestion->bind_param("issssss", $quizId, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
            $insertQuestion->execute();
        }
    }
}

function generateLessonContent($courseTitle, $lessonTitle, $orderNum, $focusLine) {
    $pages = buildLessonPages($courseTitle, $lessonTitle, $orderNum, $focusLine);
    $first = isset($pages[0]) ? $pages[0]['content'] : '';
    return $first;
}

function buildLessonPages($courseTitle, $lessonTitle, $orderNum, $focusLine) {
    $concepts = getLessonSpecificConcepts($courseTitle, $lessonTitle, $orderNum, $focusLine);
    $stage = getLessonStageName($orderNum);
    $track = detectCourseTrack($courseTitle);
    $overview = buildLessonOverview($courseTitle, $lessonTitle, $stage, $orderNum);
    $implementationSteps = getTrackImplementationSteps($track, $courseTitle, $lessonTitle);
    $pitfalls = getTrackPitfalls($track);
    $lab = getTrackLabTask($track, $courseTitle, $lessonTitle);
    $context = getLessonCaseContext($courseTitle, $lessonTitle, $orderNum);
    $bundle = getStageContentBundle($courseTitle, $lessonTitle, $orderNum, $track);
    $variation = lessonVariationStamp($courseTitle, $lessonTitle, $orderNum);
    $c0 = $concepts[0] ?? ['title' => 'Core Principle', 'explain' => 'Understand primary objective.', 'example' => 'Implement baseline output.'];
    $c1 = $concepts[1] ?? $c0;
    $c2 = $concepts[2] ?? $c0;
    $c3 = $concepts[3] ?? $c1;
    $c4 = $concepts[4] ?? $c2;

    $pages = [];

    $pages[] = [
        'title' => 'Page 1 - Lesson Brief',
        'content' =>
            $overview . "\n\n" .
            "Delivery Context:\n" .
            "- Product: " . $context['product'] . "\n" .
            "- Team Persona: " . $context['persona'] . "\n" .
            "- Data/Domain Focus: " . $context['domain'] . "\n" .
            "- Primary KPI: " . $context['kpi'] . "\n\n" .
            "Outcome of this lesson:\n" .
            "Ship one reliable implementation of \"" . $lessonTitle . "\" that can be defended in review.\n\n" .
            "Module Focus:\n" . $bundle['focus'] . "\n\n" .
            "Lesson Signature: " . $variation
    ];

    $pages[] = [
        'title' => 'Page 2 - Concept A Deep Dive',
        'content' =>
            "Concept Focus: " . $c0['title'] . "\n\n" .
            $c0['explain'] . "\n\n" .
            "Implementation Example:\n" . $c0['example'] . "\n\n" .
            "Applied Rule:\n" .
            "Use this concept first when baseline behavior or structure is still unclear.\n\n" .
            "Architecture Lens:\n" . $bundle['architecture']
    ];

    $pages[] = [
        'title' => 'Page 3 - Concept B Deep Dive',
        'content' =>
            "Concept Focus: " . $c1['title'] . "\n\n" .
            $c1['explain'] . "\n\n" .
            "Implementation Example:\n" . $c1['example'] . "\n\n" .
            "Validation Prompt:\n" .
            "What fails if this concept is skipped in " . $context['product'] . "?\n\n" .
            "Review Question:\n" . $bundle['review_q']
    ];

    $pages[] = [
        'title' => 'Page 4 - Architecture Mapping',
        'content' =>
            "Architecture Mapping for \"" . $lessonTitle . "\":\n" .
            "1. Input source: " . $context['input'] . "\n" .
            "2. Processing layer: " . $context['processing'] . "\n" .
            "3. Output target: " . $context['output'] . "\n\n" .
            "Design Constraint:\n" . $context['constraint'] . "\n\n" .
            "Concept tie-in:\n- " . $c2['title'] . "\n- " . $c3['title']
            . "\n\nInterface Contract:\n" . $bundle['contract']
    ];

    $workflowText = "Implementation Workflow:\n";
    foreach ($implementationSteps as $i => $stepText) {
        $workflowText .= ($i + 1) . ". " . $stepText . "\n";
    }
    $pages[] = [
        'title' => 'Page 5 - Step-by-Step Build',
        'content' =>
            $workflowText . "\n" .
            "Execution Note:\n" .
            "Capture evidence after each step (query output, screenshot, log, or test result).\n\n" .
            "Done Definition:\n" . $bundle['done']
    ];

    $pages[] = [
        'title' => 'Page 6 - Real Scenario Walkthrough',
        'content' =>
            "Scenario:\n" .
            contextScenarioText($context, $courseTitle, $lessonTitle) . "\n\n" .
            "Decision Points:\n" .
            "- Which concept controls correctness?\n" .
            "- Which concept controls performance?\n" .
            "- Which concept improves maintainability?\n\n" .
            "Escalation Trigger:\n" . $bundle['escalation']
    ];

    $pitfallText = "Common Pitfalls:\n";
    foreach ($pitfalls as $p) {
        $pitfallText .= "- " . $p . "\n";
    }
    $pages[] = [
        'title' => 'Page 7 - Debugging and Pitfalls',
        'content' =>
            $pitfallText . "\n" .
            "Debug Procedure:\n" .
            "1. Reproduce issue with minimal input.\n" .
            "2. Isolate failing step.\n" .
            "3. Apply targeted fix and re-verify full flow.\n\n" .
            "Recovery Plan:\n" . $bundle['recovery']
    ];

    $pages[] = [
        'title' => 'Page 8 - Optimization Pass',
        'content' =>
            "Optimization Goals:\n" .
            "- Improve response time / execution cost for " . $context['kpi'] . "\n" .
            "- Reduce complexity in implementation logic\n" .
            "- Improve readability for handover\n\n" .
            "Optimization Candidate:\n" . $c4['title'] . "\n" .
            $c4['explain'] . "\n\n" .
            "Refactor Check:\n" .
            "Confirm behavior unchanged after optimization.\n\n" .
            "Optimization Goal:\n" . $bundle['opt_goal']
    ];

    $pages[] = [
        'title' => 'Page 9 - Hands-on Lab',
        'content' =>
            "Lab Task:\n" . $lab . "\n\n" .
            "Lab Acceptance Criteria:\n" .
            "- Output is correct for at least 3 representative cases.\n" .
            "- One edge case handled explicitly.\n" .
            "- Implementation notes recorded for future reuse.\n\n" .
            "Submission Artifact:\n" . $bundle['artifact']
    ];

    $pages[] = [
        'title' => 'Page 10 - Assessment Readiness',
        'content' =>
            "Readiness Checklist for \"" . $lessonTitle . "\":\n" .
            "- I can explain " . $c0['title'] . " and " . $c1['title'] . " in my own words.\n" .
            "- I can execute the full workflow without external hints.\n" .
            "- I can debug one likely failure path quickly.\n" .
            "- I can justify one optimization decision.\n\n" .
            "Next Action:\nTake the lesson quiz and score above pass threshold.\n\n" .
            "Post-Quiz Reflection:\n" . $bundle['reflection']
    ];

    return $pages;
}

function buildLessonOverview($courseTitle, $lessonTitle, $stage, $orderNum) {
    return
        "Lesson: " . $lessonTitle . "\n" .
        "Course: " . $courseTitle . "\n" .
        "Stage: " . $stage . " (Module " . (int)$orderNum . ")\n\n" .
        "Why this lesson matters:\n" .
        "This module is designed to move you from concept familiarity to production-grade execution. " .
        "By the end of this lesson, you should be able to apply the topic in a practical workflow and justify your implementation decisions.";
}

function detectCourseTrack($courseTitle) {
    $title = strtolower($courseTitle);
    if (strpos($title, 'excel') !== false || strpos($title, 'spreadsheet') !== false) return 'excel';
    if (strpos($title, 'javascript') !== false || strpos($title, 'react') !== false || strpos($title, 'typescript') !== false || strpos($title, 'html') !== false || strpos($title, 'css') !== false || strpos($title, 'web') !== false) return 'frontend';
    if (strpos($title, 'python') !== false || strpos($title, 'java') !== false || strpos($title, 'c++') !== false || strpos($title, 'node') !== false || strpos($title, 'php') !== false || strpos($title, 'programming') !== false) return 'programming';
    if (strpos($title, 'database') !== false || strpos($title, 'sql') !== false || strpos($title, 'mysql') !== false) return 'database';
    if (strpos($title, 'data science') !== false || strpos($title, 'machine learning') !== false || strpos($title, 'ai') !== false) return 'ml';
    if (strpos($title, 'design') !== false || strpos($title, 'ux') !== false || strpos($title, 'ui') !== false) return 'design';
    if (strpos($title, 'marketing') !== false) return 'marketing';
    return 'general';
}

function getTrackImplementationSteps($track, $courseTitle, $lessonTitle) {
    switch ($track) {
        case 'frontend':
            return [
                "Define the UI/feature objective for \"" . $lessonTitle . "\" and list required states.",
                "Build base structure/components and connect state/data flow.",
                "Implement dynamic behavior (events, async calls, validation).",
                "Handle edge cases: empty data, loading, failure, invalid input.",
                "Refactor for readability and verify UX output against expected behavior.",
            ];
        case 'database':
            return [
                "Define schema entities and expected query output for this lesson.",
                "Write initial query/model and validate with sample data.",
                "Add joins/filters/grouping and verify accuracy.",
                "Review index/plan for performance bottlenecks.",
                "Finalize with transaction/error strategy for safe production use.",
            ];
        case 'ml':
            return [
                "Define target outcome and data assumptions.",
                "Prepare/clean dataset and validate feature consistency.",
                "Implement baseline pipeline/model for this lesson objective.",
                "Evaluate with relevant metric and inspect failure cases.",
                "Iterate feature/model choice and document improvements.",
            ];
        case 'excel':
            return [
                "Structure raw data into reliable table format.",
                "Implement formulas/functions required by this lesson.",
                "Add validation/rules to prevent data quality issues.",
                "Create summary analysis (pivot/chart/KPI) tied to lesson output.",
                "Review calculation accuracy with cross-check values.",
            ];
        case 'programming':
            return [
                "Break lesson requirement into function-level tasks.",
                "Implement core logic with clean inputs/outputs.",
                "Add guard/error handling for invalid scenarios.",
                "Run sample tests and boundary checks.",
                "Refactor for maintainability and performance.",
            ];
        default:
            return [
                "Clarify lesson objective and expected output.",
                "Implement one working version quickly.",
                "Validate behavior with 2-3 realistic test inputs.",
                "Fix one weakness discovered during testing.",
                "Document final approach and improvement notes.",
            ];
    }
}

function getTrackPitfalls($track) {
    switch ($track) {
        case 'frontend':
            return [
                "State mutation instead of immutable updates causes unpredictable rendering.",
                "Missing error/loading states creates broken user journeys.",
                "Overly large components reduce maintainability and testability.",
            ];
        case 'database':
            return [
                "Using joins without indexes can cause severe latency.",
                "Incorrect grouping/filter order produces wrong aggregates.",
                "Skipping transaction boundaries leads to inconsistent writes.",
            ];
        case 'ml':
            return [
                "Data leakage between train and validation inflates metrics.",
                "Ignoring class imbalance can make accuracy misleading.",
                "Overfitting from tuning without robust validation harms production behavior.",
            ];
        case 'excel':
            return [
                "Hardcoded cell references break when data grows.",
                "Mixed data types (text/number/date) corrupt formulas.",
                "No validation leads to silent reporting errors.",
            ];
        default:
            return [
                "Skipping requirement clarification leads to wrong implementation.",
                "No edge-case checks creates hidden failures.",
                "Not reviewing output against expected behavior reduces reliability.",
            ];
    }
}

function getTrackLabTask($track, $courseTitle, $lessonTitle) {
    switch ($track) {
        case 'frontend':
            return "Build a mini feature for \"" . $lessonTitle . "\" with one API/async flow, one validation rule, and one user feedback state (success/error).";
        case 'database':
            return "Create and validate one query set for \"" . $lessonTitle . "\" including join, filter, and aggregate output. Add one index and compare query behavior.";
        case 'ml':
            return "Train a baseline model for \"" . $lessonTitle . "\", report one metric, and list one targeted improvement for the next iteration.";
        case 'excel':
            return "Create a workbook section implementing the lesson formulas and a summary view. Validate results with at least two manual checks.";
        case 'programming':
            return "Implement the lesson logic as reusable functions/classes and include at least three test cases (normal, boundary, invalid input).";
        default:
            return "Implement one real example from \"" . $lessonTitle . "\", validate output, and write a short improvement note.";
    }
}

function getLessonCaseContext($courseTitle, $lessonTitle, $orderNum) {
    $seed = abs(crc32(strtolower($courseTitle . '|' . $lessonTitle . '|' . (int)$orderNum)));
    $products = ['Subscription Billing Suite', 'Multi-tenant Admin Portal', 'Analytics Dashboard', 'Learning Platform', 'Ops Monitoring Console', 'Customer CRM Workspace'];
    $personas = ['Full-stack Engineer', 'Backend Developer', 'Data Analyst', 'Frontend Engineer', 'Product Engineer', 'Platform Architect'];
    $domains = ['user activity events', 'transaction records', 'course progress metrics', 'inventory flows', 'support ticket trends', 'payment lifecycle data'];
    $kpis = ['response time', 'accuracy', 'completion rate', 'error rate', 'throughput', 'maintainability score'];
    $inputs = ['API payload stream', 'database snapshot', 'form submission batch', 'event queue', 'CSV import feed', 'scheduled ETL result'];
    $processing = ['validation + transformation layer', 'business rules engine', 'aggregation pipeline', 'state synchronization module', 'query execution plan', 'feature computation step'];
    $outputs = ['dashboard widgets', 'REST response object', 'report export', 'persisted records', 'alert events', 'user-visible UI state'];
    $constraints = [
        'Must remain backward compatible with existing clients.',
        'Must handle malformed input without service interruption.',
        'Must keep query/runtime cost within production limits.',
        'Must be easy for another engineer to extend next sprint.',
        'Must preserve data consistency across retries.',
        'Must pass review with clear logging and test evidence.'
    ];

    $pick = function($arr, $offset) use ($seed) {
        $idx = ($seed + $offset) % count($arr);
        return $arr[$idx];
    };

    return [
        'product' => $pick($products, 3),
        'persona' => $pick($personas, 7),
        'domain' => $pick($domains, 11),
        'kpi' => $pick($kpis, 17),
        'input' => $pick($inputs, 23),
        'processing' => $pick($processing, 29),
        'output' => $pick($outputs, 31),
        'constraint' => $pick($constraints, 37),
    ];
}

function contextScenarioText($context, $courseTitle, $lessonTitle) {
    return
        "You are the " . $context['persona'] . " for " . $context['product'] . ". " .
        "A delivery request requires implementing \"" . $lessonTitle . "\" in the " . $courseTitle . " track. " .
        "Input arrives through " . $context['input'] . ", is handled by the " . $context['processing'] . ", " .
        "and must produce reliable " . $context['output'] . ". " .
        "Your success metric is " . $context['kpi'] . ".";
}

function getStageContentBundle($courseTitle, $lessonTitle, $orderNum, $track) {
    $order = max(1, (int)$orderNum);
    $focus = [
        1 => "Establish baseline clarity and correctness before scaling complexity.",
        2 => "Build a repeatable workflow and remove ambiguity from implementation steps.",
        3 => "Practice with realistic inputs and validate functional reliability.",
        4 => "Integrate with surrounding modules and verify end-to-end behavior.",
        5 => "Stress-test edge cases and build robust recovery paths.",
        6 => "Consolidate capabilities and prepare for independent delivery.",
    ];
    $contract = [
        1 => "Input and output fields must be explicit and type-safe.",
        2 => "Intermediate state transitions must be observable and deterministic.",
        3 => "Validation errors must be actionable for the operator.",
        4 => "Integration boundary must not break existing contracts.",
        5 => "Failure handling must be idempotent and auditable.",
        6 => "Final output must satisfy measurable acceptance criteria.",
    ];
    $done = [
        1 => "Core path executes successfully with one validated example.",
        2 => "Workflow can be repeated by another engineer without clarification.",
        3 => "At least three scenario tests pass (normal/boundary/invalid).",
        4 => "Cross-module dependencies run without regression.",
        5 => "Observed failures are reproducible and recoverable.",
        6 => "Implementation is review-ready with concise documentation.",
    ];
    $reflection = [
        1 => "What assumption did you correct while building the baseline?",
        2 => "Which workflow step reduced most debugging time?",
        3 => "Which validation rule prevented the most failures?",
        4 => "Which integration point was hardest and why?",
        5 => "Which failure mode changed your implementation strategy?",
        6 => "What would you automate next from this lesson?",
    ];

    $trackDetails = [
        'frontend' => ['architecture' => 'Component boundaries, state ownership, and render lifecycle.', 'review_q' => 'Is UI state normalized and predictable across rerenders?', 'escalation' => 'Escalate when user-facing errors impact critical journey completion.', 'recovery' => 'Fallback UI + retriable action + telemetry event.', 'opt_goal' => 'Reduce re-render cost while preserving UX consistency.', 'artifact' => 'Working UI flow + request/response logs + state diagram.'],
        'database' => ['architecture' => 'Schema contracts, query plans, and transaction boundaries.', 'review_q' => 'Does query accuracy hold under skewed data distributions?', 'escalation' => 'Escalate when query latency or lock contention exceeds limits.', 'recovery' => 'Rollback strategy + compensating update path.', 'opt_goal' => 'Cut full scans and stabilize query response time.', 'artifact' => 'SQL script + EXPLAIN output + validation dataset.'],
        'ml' => ['architecture' => 'Feature pipeline, model boundary, and evaluation loop.', 'review_q' => 'Are train/validation boundaries protected against leakage?', 'escalation' => 'Escalate when metric drift or class imbalance distorts outcomes.', 'recovery' => 'Fallback baseline model + controlled retraining cycle.', 'opt_goal' => 'Improve target metric without harming robustness.', 'artifact' => 'Notebook/script + metric report + error analysis.'],
        'excel' => ['architecture' => 'Data table layout, formula dependencies, and summary views.', 'review_q' => 'Are formulas resilient to row/column growth?', 'escalation' => 'Escalate when source data quality breaks reporting trust.', 'recovery' => 'Validation constraints + reconciliation worksheet.', 'opt_goal' => 'Increase report reliability with minimal manual effort.', 'artifact' => 'Workbook section + validation checks + KPI summary.'],
        'programming' => ['architecture' => 'Module responsibilities, interfaces, and test boundaries.', 'review_q' => 'Are side effects isolated and testable?', 'escalation' => 'Escalate when defects affect critical transaction paths.', 'recovery' => 'Guard clauses + structured error handling + tests.', 'opt_goal' => 'Simplify complexity while preserving correctness.', 'artifact' => 'Code patch + tests + short design note.'],
        'general' => ['architecture' => 'Input-process-output flow with explicit checkpoints.', 'review_q' => 'Does each step have a clear validation signal?', 'escalation' => 'Escalate when failure reason cannot be isolated quickly.', 'recovery' => 'Checkpoint rollback + guided re-execution.', 'opt_goal' => 'Improve clarity and reliability of execution.', 'artifact' => 'Implementation note + output evidence + checklist.'],
    ];

    $t = $trackDetails[$track] ?? $trackDetails['general'];
    return [
        'focus' => $focus[$order] ?? $focus[6],
        'architecture' => $t['architecture'],
        'review_q' => $t['review_q'],
        'contract' => $contract[$order] ?? $contract[6],
        'done' => $done[$order] ?? $done[6],
        'escalation' => $t['escalation'],
        'recovery' => $t['recovery'],
        'opt_goal' => $t['opt_goal'],
        'artifact' => $t['artifact'],
        'reflection' => $reflection[$order] ?? $reflection[6],
    ];
}

function lessonVariationStamp($courseTitle, $lessonTitle, $orderNum) {
    $hash = strtoupper(substr(sha1(strtolower($courseTitle . '|' . $lessonTitle . '|' . (int)$orderNum)), 0, 8));
    return 'LSN-' . $hash;
}

function applyCustomJavascriptLesson2ContentAndQuiz($conn) {
    $courseRes = $conn->query("SELECT id FROM courses WHERE title='JavaScript ES6+' LIMIT 1");
    if (!$courseRes || $courseRes->num_rows === 0) {
        return;
    }
    $courseId = (int)$courseRes->fetch_assoc()['id'];

    $lessonRes = $conn->query("SELECT id, title FROM lessons WHERE course_id=$courseId AND order_num=2 LIMIT 1");
    if (!$lessonRes || $lessonRes->num_rows === 0) {
        return;
    }
    $lesson = $lessonRes->fetch_assoc();
    $lessonId = (int)$lesson['id'];

    $page1 = <<<'PAGE'
LESSON 2: let, const, Scope & Arrow Functions (Deep Dive)

Learning Objectives
By the end of this lesson, you will:
- Fully understand JavaScript scoping
- Master let vs const vs var
- Understand hoisting and Temporal Dead Zone
- Deeply understand arrow functions and lexical this
- Know when NOT to use arrow functions

1) Understanding Scope in JavaScript
JavaScript has 3 types of scope:
- Global Scope
- Function Scope
- Block Scope (Introduced in ES6)

Problem with var:
var x = 10;
if(true){
   var x = 20;
}
console.log(x); // 20 (function-scoped, not block-scoped)

2) let (Block Scoped)
let x = 10;
if(true){
   let x = 20;
}
console.log(x); // 10

Each block gets its own scope.
PAGE;

    $page2 = <<<'PAGE'
3) const (Immutable Binding)
const PI = 3.14;
// PI = 3.15; // Error

Important:
const does NOT make objects immutable.

const user = { name: "Rakesh" };
user.name = "Amit"; // allowed

4) Hoisting & Temporal Dead Zone (TDZ)
Hoisting with var:
console.log(a); // undefined
var a = 5;

Internally:
var a;
console.log(a);
a = 5;

Hoisting with let:
console.log(a); // ReferenceError
let a = 5;

This period before initialization is called:
Temporal Dead Zone (TDZ)

5) Arrow Functions (Advanced Understanding)
Basic syntax:
const add = (a, b) => a + b;

Multi-line arrow:
const multiply = (a, b) => {
   let result = a * b;
   return result;
};
PAGE;

    $page3 = <<<'PAGE'
6) Arrow Functions & Lexical this

Normal function (wrong this in callback):
function Person(){
   this.age = 0;
   setInterval(function(){
      this.age++; // wrong context
   },1000);
}

Arrow function (inherits parent this):
function Person(){
   this.age = 0;
   setInterval(() => {
      this.age++; // correct context
   },1000);
}

7) When NOT to Use Arrow Functions
Do NOT use arrow functions:
- In object methods where dynamic this is needed
- In constructors
- In prototype methods

Example:
const obj = {
   value: 10,
   getValue: () => {
      console.log(this.value); // undefined
   }
};

Now complete the quiz for this lesson.
PAGE;

    storeLessonPages($conn, $lessonId, [
        ['title' => 'Scope Fundamentals', 'content' => $page1],
        ['title' => 'Hoisting and Arrow Basics', 'content' => $page2],
        ['title' => 'Lexical This and Pitfalls', 'content' => $page3],
    ]);

    $firstPage = trim($page1);
    $stmtLesson = $conn->prepare("UPDATE lessons SET content=? WHERE id=?");
    $stmtLesson->bind_param("si", $firstPage, $lessonId);
    $stmtLesson->execute();

    $quizRes = $conn->query("SELECT id FROM quizzes WHERE lesson_id=$lessonId LIMIT 1");
    $quizId = 0;
    if ($quizRes && $quizRes->num_rows > 0) {
        $quizId = (int)$quizRes->fetch_assoc()['id'];
    } else {
        $title = 'Quiz: ' . $lesson['title'];
        $qIns = $conn->prepare("INSERT INTO quizzes (course_id, lesson_id, title, pass_percentage) VALUES (?, ?, ?, 60)");
        $qIns->bind_param("iis", $courseId, $lessonId, $title);
        $qIns->execute();
        $quizId = (int)$conn->insert_id;
    }
    if ($quizId <= 0) {
        return;
    }

    $conn->query("DELETE FROM quiz_questions WHERE quiz_id=$quizId");
    $insertQuestion = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $questions = [
        [
            "What problem does let solve compared to var?",
            "It creates block scope and avoids unintended variable overwrite",
            "It makes all variables immutable",
            "It disables hoisting completely",
            "It makes variables global by default",
            "A",
        ],
        [
            "What is Temporal Dead Zone (TDZ)?",
            "A memory leak caused by const",
            "Time before a let/const variable is initialized where access throws error",
            "A scope only inside async functions",
            "A special mode for arrow functions",
            "B",
        ],
        [
            "Arrow functions bind this from where?",
            "From the function itself at call time",
            "From global window only",
            "From the lexical (parent) scope",
            "From the nearest object literal",
            "C",
        ],
        [
            "Output:\nlet a = 10;\n{\n  let a = 20;\n}\nconsole.log(a);",
            "20",
            "undefined",
            "ReferenceError",
            "10",
            "D",
        ],
        [
            "Choose the correct arrow function to return cube of n.",
            "const cube = n => { n*n*n; }",
            "const cube = (n) => n ** 3;",
            "const cube = (n) => return n*n*n;",
            "const cube = function(n) => n*n*n;",
            "B",
        ],
    ];
    foreach ($questions as $q) {
        $insertQuestion->bind_param("issssss", $quizId, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
        $insertQuestion->execute();
    }
}

function applyCustomJavascriptLessons3To6ContentAndQuiz($conn) {
    $courseRes = $conn->query("SELECT id FROM courses WHERE title='JavaScript ES6+' LIMIT 1");
    if (!$courseRes || $courseRes->num_rows === 0) {
        return;
    }
    $courseId = (int)$courseRes->fetch_assoc()['id'];

    $lessonData = [
        3 => [
            'fallback_title' => 'Template Literals, Destructuring & Default Parameters',
            'pages' => [
                [
                    'title' => 'Template Literals In Depth',
                    'content' => <<<'PAGE'
LESSON 3: Template Literals, Destructuring & Default Parameters (In Depth)

Objectives
- Master template literals
- Deep understanding of destructuring
- Apply default parameters correctly
- Avoid common mistakes

1) Template Literals (Deep Usage)
String Interpolation:
const name = "Rakesh";
const age = 30;
console.log(`My name is ${name} and I am ${age}`);

Expression Evaluation:
console.log(`2 + 2 = ${2 + 2}`);

Multi-line String:
const msg = `
Line 1
Line 2
Line 3
`;

Use template literals whenever dynamic string values are needed.
PAGE
                ],
                [
                    'title' => 'Destructuring Advanced Patterns',
                    'content' => <<<'PAGE'
2) Destructuring (Advanced)
Object Destructuring:
const user = { name: "Rakesh", age: 30, city: "Pune" };
const { name, age } = user;

Renaming Variables:
const { name: fullName } = user;

Default Values:
const { country = "India" } = user;

Nested Destructuring:
const data = {
   user: {
      profile: {
         email: "test@gmail.com"
      }
   }
};
const { user: { profile: { email } } } = data;

Array Destructuring:
const numbers = [10, 20, 30];
const [a, b] = numbers;
const [first, , third] = numbers; // skip values
PAGE
                ],
                [
                    'title' => 'Default Parameters and Edge Cases',
                    'content' => <<<'PAGE'
3) Default Parameters
function greet(name = "Guest"){
   return `Hello ${name}`;
}

Important behavior:
greet(undefined); // uses default
greet(null); // null (default NOT applied)

Practice checks:
- Use defaults for optional input.
- Validate null explicitly when business rules require it.
- Keep default values simple and predictable.
PAGE
                ],
            ],
            'questions' => [
                [
                    "What happens if a destructured property does not exist and no default is provided?",
                    "JavaScript throws SyntaxError",
                    "It becomes undefined",
                    "It becomes null automatically",
                    "It becomes empty string",
                    "B",
                ],
                [
                    "Output:\nconst [a,,c] = [1,2,3];\nconsole.log(c);",
                    "1",
                    "2",
                    "3",
                    "undefined",
                    "C",
                ],
                [
                    "Which call uses default parameter value in function greet(name = 'Guest')?",
                    "greet(null)",
                    "greet(false)",
                    "greet(undefined)",
                    "greet('')",
                    "C",
                ],
                [
                    "Which syntax correctly renames a destructured object property?",
                    "const { name = fullName } = user;",
                    "const { name: fullName } = user;",
                    "const { fullName: name } = user;",
                    "const { rename(name, fullName) } = user;",
                    "B",
                ],
                [
                    "Write cube with default parameter n=2. Which is correct?",
                    "const cube = (n) => n*n*n = 2;",
                    "function cube(n=2){ return n ** 3; }",
                    "function cube(n){ default 2; return n*n*n; }",
                    "const cube = n=2 => n*n*n;",
                    "B",
                ],
            ],
        ],
        4 => [
            'fallback_title' => 'Spread, Rest & Enhanced Objects',
            'pages' => [
                [
                    'title' => 'Spread Operator',
                    'content' => <<<'PAGE'
LESSON 4: Spread, Rest & Enhanced Objects

Objectives
- Master spread operator
- Master rest parameters
- Understand shallow copy
- Use enhanced object literals

1) Spread Operator (...)
Copy Array:
const arr1 = [1,2];
const arr2 = [...arr1];

Merge Arrays:
const merged = [...arr1, 3,4];

Copy Object:
const user = {name:"Rakesh"};
const newUser = {...user, age:30};

Important: spread creates shallow copy only.
PAGE
                ],
                [
                    'title' => 'Rest Parameters',
                    'content' => <<<'PAGE'
2) Rest Parameters
function sum(...numbers){
   return numbers.reduce((total, n) => total + n, 0);
}

Difference:
- Spread expands values.
- Rest collects values.

Rest is ideal for variable argument utility functions.
PAGE
                ],
                [
                    'title' => 'Enhanced Object Literals',
                    'content' => <<<'PAGE'
3) Enhanced Object Literals
const name = "Rakesh";

const user = {
   name,
   greet(){
      console.log("Hello");
   }
};

Benefits:
- Shorter syntax
- Cleaner method declarations
- Better readability in modern JavaScript codebases
PAGE
                ],
            ],
            'questions' => [
                [
                    "What is the key difference between spread and rest operator?",
                    "Spread collects and rest expands",
                    "Spread expands and rest collects",
                    "Both only work with arrays",
                    "Both create deep copy",
                    "B",
                ],
                [
                    "What type of copy does spread create for objects?",
                    "Deep copy",
                    "Encrypted copy",
                    "Shallow copy",
                    "No copy, just reference",
                    "C",
                ],
                [
                    "Which function correctly uses rest to find max value?",
                    "function max(...nums){ return Math.max(...nums); }",
                    "function max(nums...){ return nums.max(); }",
                    "const max = (...nums) => nums;",
                    "function max(...nums){ return nums.reduce(); }",
                    "A",
                ],
                [
                    "Which is an enhanced object literal method syntax?",
                    "greet: function(){ }",
                    "function greet(){ }",
                    "greet(){ }",
                    "method greet => { }",
                    "C",
                ],
                [
                    "Result of const merged = [...[1,2], 3, 4] ?",
                    "[1,2,[3,4]]",
                    "[1,2,3,4]",
                    "[[1,2],3,4]",
                    "Error",
                    "B",
                ],
            ],
        ],
        5 => [
            'fallback_title' => 'Classes & Modules',
            'pages' => [
                [
                    'title' => 'Classes and Constructors',
                    'content' => <<<'PAGE'
LESSON 5: Classes & Modules

Objectives
- Understand ES6 OOP
- Use constructor
- Inheritance
- Import/Export system

1) Classes
class Person{
   constructor(name){
      this.name = name;
   }

   greet(){
      return `Hello ${this.name}`;
   }
}

Use class syntax to model reusable entities cleanly.
PAGE
                ],
                [
                    'title' => 'Inheritance with super()',
                    'content' => <<<'PAGE'
Inheritance:
class Employee extends Person{
   constructor(name, role){
      super(name);
      this.role = role;
   }
}

super() calls parent constructor and initializes inherited state.
Without super() in a child constructor, JavaScript throws an error.
PAGE
                ],
                [
                    'title' => 'Modules: Named and Default Exports',
                    'content' => <<<'PAGE'
2) Modules
Named Export:
export const add = (a,b) => a+b;

Import named:
import { add } from './math.js';

Default Export:
export default function greet(){}

Difference:
- Named export: import by exact exported name.
- Default export: import with any local name.
PAGE
                ],
            ],
            'questions' => [
                [
                    "What does super() do in a child class constructor?",
                    "Calls a static method",
                    "Calls parent constructor",
                    "Creates a new prototype",
                    "Exports the class",
                    "B",
                ],
                [
                    "Difference between default and named export?",
                    "No difference",
                    "Default export must be imported in braces",
                    "Named export can be imported with any name without alias",
                    "Default export imports without braces; named usually uses braces",
                    "D",
                ],
                [
                    "Which class declaration is valid?",
                    "class Car() { start(){ return 'on'; } }",
                    "class Car { constructor(){} start(){ return 'on'; } }",
                    "class Car extends { start(){} }",
                    "class Car = { start(){} }",
                    "B",
                ],
                [
                    "Which is valid named import?",
                    "import add from './math.js';",
                    "import { add } from './math.js';",
                    "import * add from './math.js';",
                    "require { add } from './math.js';",
                    "B",
                ],
                [
                    "Create class Car with method start(). Choose correct option.",
                    "class Car { start(){ return 'Started'; } }",
                    "class Car(start){ return 'Started'; }",
                    "class Car => { start(){ return 'Started'; } }",
                    "const Car = class start(){ return 'Started'; }",
                    "A",
                ],
            ],
        ],
        6 => [
            'fallback_title' => 'Promises & Async JavaScript',
            'pages' => [
                [
                    'title' => 'Promise Basics and States',
                    'content' => <<<'PAGE'
LESSON 6: Promises & Async JavaScript

Objectives
- Understand Promise lifecycle
- Chaining
- Async/Await
- Error handling

1) Promise Basics
States:
- Pending
- Fulfilled
- Rejected

const promise = new Promise((resolve,reject)=>{
   resolve("Success");
});
PAGE
                ],
                [
                    'title' => 'Promise Chaining',
                    'content' => <<<'PAGE'
2) Promise Chaining
fetch(url)
  .then(res => res.json())
  .then(data => console.log(data))
  .catch(err => console.log(err));

Use chaining to process sequential async steps and a single catch for failures.
PAGE
                ],
                [
                    'title' => 'Async Await and Completion Outcome',
                    'content' => <<<'PAGE'
3) Async / Await
async function getData(){
   try{
      const res = await fetch(url);
      const data = await res.json();
      console.log(data);
   }catch(err){
      console.log(err);
   }
}

Cleaner than .then()

Course Completion Outcome
After lessons, student can:
- Write modern ES6 code
- Use modules
- Build OOP structure
- Handle async operations
- Prepare for interviews
- Build production applications
PAGE
                ],
            ],
            'questions' => [
                [
                    "What are valid Promise states?",
                    "Started, Finished, Error",
                    "Open, Closed, Broken",
                    "Pending, Fulfilled, Rejected",
                    "Init, Wait, Done",
                    "C",
                ],
                [
                    "Does await work outside async function?",
                    "Yes, always",
                    "No, await is for async functions (except top-level module cases)",
                    "Only in callbacks",
                    "Only with setTimeout",
                    "B",
                ],
                [
                    "Which async function returns a Promise?",
                    "function run(){ return 1; }",
                    "const run = async () => 1;",
                    "const run = () => await 1;",
                    "function async run(){ return 1; }",
                    "B",
                ],
                [
                    "Where should .catch typically be used in a promise chain?",
                    "At the beginning only",
                    "After each then is mandatory",
                    "At the end to handle chain errors",
                    "Never needed",
                    "C",
                ],
                [
                    "What is a key benefit of async/await?",
                    "It removes all runtime errors",
                    "It makes async flow more readable and try/catch friendly",
                    "It makes code synchronous",
                    "It avoids promises internally",
                    "B",
                ],
            ],
        ],
    ];

    foreach ($lessonData as $orderNum => $def) {
        $orderNum = (int)$orderNum;
        $lessonRes = $conn->query("SELECT id, title FROM lessons WHERE course_id=$courseId AND order_num=$orderNum LIMIT 1");
        if ($lessonRes && $lessonRes->num_rows > 0) {
            $lesson = $lessonRes->fetch_assoc();
            $lessonId = (int)$lesson['id'];
            $lessonTitle = $lesson['title'];
        } else {
            $fallbackTitle = $def['fallback_title'];
            $summary = trim((string)$def['pages'][0]['content']);
            $insertLesson = $conn->prepare("INSERT INTO lessons (course_id, title, content, duration, order_num, is_preview) VALUES (?, ?, ?, '25 min', ?, 0)");
            $insertLesson->bind_param("issi", $courseId, $fallbackTitle, $summary, $orderNum);
            $insertLesson->execute();
            $lessonId = (int)$conn->insert_id;
            $lessonTitle = $fallbackTitle;
            if ($lessonId <= 0) {
                continue;
            }
        }

        storeLessonPages($conn, $lessonId, $def['pages']);
        $firstPage = trim((string)$def['pages'][0]['content']);
        $stmtLesson = $conn->prepare("UPDATE lessons SET content=? WHERE id=?");
        $stmtLesson->bind_param("si", $firstPage, $lessonId);
        $stmtLesson->execute();

        $quizRes = $conn->query("SELECT id FROM quizzes WHERE lesson_id=$lessonId LIMIT 1");
        $quizId = 0;
        if ($quizRes && $quizRes->num_rows > 0) {
            $quizId = (int)$quizRes->fetch_assoc()['id'];
        } else {
            $quizTitle = 'Quiz: ' . $lessonTitle;
            $insertQuiz = $conn->prepare("INSERT INTO quizzes (course_id, lesson_id, title, pass_percentage) VALUES (?, ?, ?, 60)");
            $insertQuiz->bind_param("iis", $courseId, $lessonId, $quizTitle);
            $insertQuiz->execute();
            $quizId = (int)$conn->insert_id;
        }
        if ($quizId <= 0) {
            continue;
        }

        $conn->query("DELETE FROM quiz_questions WHERE quiz_id=$quizId");
        $insertQuestion = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($def['questions'] as $q) {
            $insertQuestion->bind_param("issssss", $quizId, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
            $insertQuestion->execute();
        }
    }
}

function buildAutoLessonVideoUrl($courseTitle, $lessonTitle, $orderNum) {
    $title = strtolower($courseTitle);
    $videoId = '';

    if (strpos($title, 'javascript') !== false) $videoId = 'NCwa_xi0Uuc';
    elseif (strpos($title, 'html') !== false) $videoId = 'UB1O30fR-EE';
    elseif (strpos($title, 'css') !== false) $videoId = 'yfoY53QXEnI';
    elseif (strpos($title, 'react') !== false) $videoId = 'w7ejDZ8SWv8';
    elseif (strpos($title, 'typescript') !== false) $videoId = 'BwuLxPH8IDs';
    elseif (strpos($title, 'python') !== false) $videoId = 'rfscVS0vtbw';
    elseif (strpos($title, 'java') !== false) $videoId = 'eIrMbAQSU34';
    elseif (strpos($title, 'mysql') !== false || strpos($title, 'sql') !== false || strpos($title, 'database') !== false) $videoId = 'HXV3zeQKqGY';
    elseif (strpos($title, 'node') !== false) $videoId = 'TlB_eWDSMt4';
    elseif (strpos($title, 'php') !== false || strpos($title, 'laravel') !== false) $videoId = 'OK_JCtrrv-c';
    elseif (strpos($title, 'excel') !== false) $videoId = 'Vl0H-qTclOg';
    elseif (strpos($title, 'machine learning') !== false || strpos($title, 'ai') !== false || strpos($title, 'data science') !== false) $videoId = 'GwIo3gDZCVQ';
    elseif (strpos($title, 'cybersecurity') !== false || strpos($title, 'security') !== false) $videoId = 'inWWhr5tnEA';
    elseif (strpos($title, 'devops') !== false || strpos($title, 'cloud') !== false || strpos($title, 'aws') !== false) $videoId = '9zUHg7xjIqQ';
    elseif (strpos($title, 'marketing') !== false) $videoId = 'nU-IIXBWlS4';
    elseif (strpos($title, 'design') !== false || strpos($title, 'ui/ux') !== false) $videoId = 'c9Wg6Cb_YlU';
    elseif (strpos($title, 'blockchain') !== false || strpos($title, 'web3') !== false) $videoId = 'SSo_EIwHSd4';
    elseif (strpos($title, 'programming') !== false || strpos($title, 'c++') !== false) $videoId = '8jLOx1hD3_o';

    if ($videoId !== '') {
        $start = max(0, ((int)$orderNum - 1) * 420); // 7 min offset per lesson
        return 'https://www.youtube.com/watch?v=' . $videoId . '&t=' . $start . 's';
    }

    $query = trim($courseTitle . ' ' . $lessonTitle . ' tutorial');
    return 'https://www.youtube.com/results?search_query=' . rawurlencode($query);
}

function buildPagesFromContent($content) {
    $parts = preg_split('/\n\s*\[\[PAGE_BREAK\]\]\s*\n/', (string)$content);
    $pages = [];
    $i = 1;
    foreach ($parts as $part) {
        $text = trim((string)$part);
        if ($text === '') {
            continue;
        }
        $pages[] = [
            'title' => 'Lesson Page ' . $i,
            'content' => $text,
        ];
        $i++;
    }
    return $pages;
}

function storeLessonPages($conn, $lessonId, $pages) {
    if ((int)$lessonId <= 0 || empty($pages)) {
        return;
    }
    $lessonId = (int)$lessonId;
    $conn->query("DELETE FROM lesson_pages WHERE lesson_id=$lessonId");
    $insert = $conn->prepare("INSERT INTO lesson_pages (lesson_id, page_no, page_title, page_content) VALUES (?, ?, ?, ?)");
    $pageNo = 1;
    foreach ($pages as $page) {
        $title = $page['title'] ?? ('Lesson Page ' . $pageNo);
        $content = $page['content'] ?? '';
        $insert->bind_param("iiss", $lessonId, $pageNo, $title, $content);
        $insert->execute();
        $pageNo++;
    }
}

function getLessonStageName($orderNum) {
    $map = [
        1 => 'Foundation',
        2 => 'Core Workflow',
        3 => 'Guided Practice',
        4 => 'Project Application',
        5 => 'Troubleshooting',
        6 => 'Assessment',
    ];
    $index = max(1, (int)$orderNum);
    return $map[$index] ?? ('Advanced Module ' . $index);
}

function getLessonSpecificConcepts($courseTitle, $lessonTitle, $orderNum, $focusLine) {
    $base = getCourseConceptBlocks($courseTitle, $lessonTitle, $focusLine);
    if (empty($base)) {
        return [];
    }
    $count = count($base);
    $seed = abs(crc32(strtolower($lessonTitle))) % $count;
    $start = (($orderNum - 1) + $seed) % $count;
    $selected = [];
    for ($i = 0; $i < min(5, $count); $i++) {
        $selected[] = $base[($start + $i) % $count];
    }
    return $selected;
}

function buildLessonQuizQuestions($courseTitle, $lessonTitle, $orderNum, $focusLine) {
    $concepts = getLessonSpecificConcepts($courseTitle, $lessonTitle, $orderNum, $focusLine);
    while (count($concepts) < 4) {
        $concepts[] = [
            'title' => 'Core Practice',
            'explain' => 'Apply the concept in practical workflow.',
            'example' => 'Implement and validate expected output.',
        ];
    }
    $c1 = $concepts[0];
    $c2 = $concepts[1];
    $c3 = $concepts[2];
    $c4 = $concepts[3];

    $stage = getLessonStageName($orderNum);
    return [
        [
            "For \"" . $lessonTitle . "\" (" . $stage . "), which concept should you apply first?",
            $c1['title'],
            $c2['title'],
            $c3['title'],
            $c4['title'],
            "A",
        ],
        [
            "What is the best approach for mastering \"" . $c2['title'] . "\" in this specific lesson?",
            "Skip implementation and move to next lesson",
            "Use a practical example and validate output",
            "Only memorize terms",
            "Avoid reviewing mistakes",
            "B",
        ],
        [
            "Which action aligns with \"" . $c3['title'] . "\" for " . $courseTitle . " in this module?",
            "Ignore data quality and edge cases",
            "Avoid testing to save time",
            "Implement, verify, and refine using feedback",
            "Copy code without understanding",
            "C",
        ],
        [
            "After completing \"" . $lessonTitle . "\", what should you do next in the learning flow?",
            "Skip quiz and mark complete manually",
            "Move to random topic without review",
            "Delete your practice notes",
            "Take the lesson quiz and confirm understanding",
            "D",
        ],
    ];
}

function getCourseConceptBlocks($courseTitle, $lessonTitle, $focusLine) {
    $title = strtolower($courseTitle);

    if (strpos($title, 'excel') !== false) {
        return [
            ['title' => 'Structured Data Tables', 'explain' => 'Use proper table structure to make formulas and filtering reliable.', 'example' => "Range -> Table (Ctrl+T)\nUse structured references: =SUM(Table1[Revenue])"],
            ['title' => 'Core Functions', 'explain' => 'Combine SUMIFS, XLOOKUP/VLOOKUP, IF, and TEXT functions for automation.', 'example' => "=SUMIFS(C:C,A:A,\"West\",B:B,\">=2026-01-01\")"],
            ['title' => 'Data Validation', 'explain' => 'Prevent input errors using dropdowns, limits, and clear prompts.', 'example' => "Data -> Data Validation -> List -> Source: Status values"],
            ['title' => 'Pivot Analysis', 'explain' => 'Summarize large data quickly with pivots and slicers.', 'example' => "Insert PivotTable -> Rows: Product -> Values: Revenue (Sum)"],
            ['title' => 'Dashboard Basics', 'explain' => 'Turn analysis into visual insights with KPIs and charts.', 'example' => "Create KPI cells and link chart series to dynamic named ranges"],
        ];
    }

    if (strpos($title, 'javascript') !== false || strpos($title, 'react') !== false || strpos($title, 'typescript') !== false || strpos($title, 'html') !== false || strpos($title, 'css') !== false) {
        return [
            ['title' => 'Component-Oriented Thinking', 'explain' => 'Break UI into reusable parts with clear state and props/inputs.', 'example' => "const Card = ({title}) => <section>{title}</section>;"],
            ['title' => 'State and Data Flow', 'explain' => 'Keep one-directional flow and derive UI from state.', 'example' => "const [items, setItems] = useState([]);\nsetItems(prev => [...prev, newItem]);"],
            ['title' => 'Async Data Handling', 'explain' => 'Fetch data with loading/error branches for reliable UX.', 'example' => "const res = await fetch('/api/items');\nconst data = await res.json();"],
            ['title' => 'Validation and Edge Cases', 'explain' => 'Validate forms and handle empty/error states explicitly.', 'example' => "if (!email.includes('@')) return setError('Invalid email');"],
            ['title' => 'Performance and Clean Code', 'explain' => 'Memoize heavy work and keep modules focused.', 'example' => "const total = useMemo(() => items.reduce((s,i)=>s+i.price,0), [items]);"],
        ];
    }

    if (strpos($title, 'python') !== false || strpos($title, 'java') !== false || strpos($title, 'c++') !== false || strpos($title, 'node') !== false || strpos($title, 'php') !== false || strpos($title, 'programming') !== false) {
        return [
            ['title' => 'Problem Breakdown', 'explain' => 'Convert requirements into small deterministic steps.', 'example' => "Input -> Validate -> Process -> Output"],
            ['title' => 'Data Structures', 'explain' => 'Choose arrays/maps/sets based on access patterns.', 'example' => "HashMap for O(1) lookup, list for ordered iteration"],
            ['title' => 'Functions and Reuse', 'explain' => 'Write focused functions with clear input/output contracts.', 'example' => "def calculate_total(items):\n    return sum(i['price'] for i in items)"],
            ['title' => 'Error Handling', 'explain' => 'Fail gracefully and surface useful diagnostics.', 'example' => "try { ... } catch (err) { logger.error(err.message); }"],
            ['title' => 'Testing Mindset', 'explain' => 'Cover happy path, boundaries, and invalid cases.', 'example' => "assert calculate_total([]) == 0"],
        ];
    }

    if (strpos($title, 'data science') !== false || strpos($title, 'machine learning') !== false || strpos($title, 'ai') !== false) {
        return [
            ['title' => 'Data Quality Checks', 'explain' => 'Inspect nulls, duplicates, and type consistency first.', 'example' => "df.isna().sum(); df.duplicated().sum()"],
            ['title' => 'Feature Engineering', 'explain' => 'Create features that encode useful signal for models.', 'example' => "df['amount_log'] = np.log1p(df['amount'])"],
            ['title' => 'Train/Validation Split', 'explain' => 'Separate evaluation from training to avoid leakage.', 'example' => "X_train, X_test, y_train, y_test = train_test_split(X,y,test_size=0.2)"],
            ['title' => 'Model Evaluation', 'explain' => 'Use metrics aligned to business goals.', 'example' => "precision, recall, f1, roc_auc"],
            ['title' => 'Iteration Loop', 'explain' => 'Improve via error analysis and controlled experiments.', 'example' => "Compare baseline vs tuned model with same validation set"],
        ];
    }

    if (strpos($title, 'database') !== false || strpos($title, 'mysql') !== false || strpos($title, 'sql') !== false) {
        return [
            ['title' => 'Schema Design', 'explain' => 'Model entities and relationships with primary/foreign keys.', 'example' => "users(id PK) -> orders(user_id FK)"],
            ['title' => 'Query Patterns', 'explain' => 'Use joins, grouping, and filtering intentionally.', 'example' => "SELECT c.name, COUNT(*) FROM courses c JOIN enrollments e ON e.course_id=c.id GROUP BY c.name;"],
            ['title' => 'Indexing', 'explain' => 'Add indexes to high-cardinality filters and join keys.', 'example' => "CREATE INDEX idx_enrollments_user ON enrollments(user_id);"],
            ['title' => 'Transactions', 'explain' => 'Keep multi-step writes consistent and recoverable.', 'example' => "START TRANSACTION; ... COMMIT;"],
            ['title' => 'Performance Review', 'explain' => 'Use explain plans and remove full scans on hot paths.', 'example' => "EXPLAIN SELECT ... WHERE user_id=?;"],
        ];
    }

    return [
        ['title' => 'Core Fundamentals', 'explain' => $focusLine, 'example' => "Define one clear outcome for this lesson and map the required steps."],
        ['title' => 'Practical Workflow', 'explain' => 'Apply the concept in a small repeatable workflow.', 'example' => "Plan -> Implement -> Validate -> Improve"],
        ['title' => 'Quality Checks', 'explain' => 'Verify output quality before moving to the next module.', 'example' => "Run checklist and confirm expected output against sample input."],
        ['title' => 'Common Pitfalls', 'explain' => 'Recognize and fix mistakes early.', 'example' => "Document one bug and the exact correction applied."],
        ['title' => 'Production Relevance', 'explain' => 'Connect this concept to real project execution.', 'example' => "Map this lesson to one real feature in your application stack."],
    ];
}

function getJavascriptEs6CoreLessonContent() {
    return <<<'LESSON'
Lesson: Core Concepts of JavaScript ES6+

JavaScript ES6 (ECMAScript 2015) introduced major improvements that modernized the language. If you're building SaaS platforms, APIs, and real-time systems, understanding ES6+ is essential for frontend apps, Node.js services, and modern frameworks.

1) let and const (Block Scope)
Before ES6, var had function scope and often caused bugs.

let (can be reassigned)
let count = 5;
count = 10; // allowed

const (cannot be reassigned)
const apiUrl = "https://api.example.com";
// apiUrl = "newurl"; // Error

Key Difference:
- var -> function scoped
- let and const -> block scoped { }
- Prefer const by default

2) Arrow Functions (=>)
Traditional function:
function add(a, b) {
  return a + b;
}

ES6 arrow function:
const add = (a, b) => a + b;

Arrow functions do not bind their own this, making them great for callbacks.

3) Template Literals (Backticks)
const name = "Rakesh";
const message = `Hello, ${name}! Welcome to ES6.`;

Benefits: cleaner dynamic strings, interpolation, multi-line support.

4) Destructuring
Array destructuring:
const numbers = [1, 2, 3];
const [a, b] = numbers;

Object destructuring:
const user = { id: 1, name: "Rakesh" };
const { id, name } = user;

Very useful in APIs and frontend frameworks.

5) Spread Operator (...)
Array example:
const arr1 = [1, 2];
const arr2 = [...arr1, 3, 4];

Object example:
const user = { name: "Rakesh" };
const updatedUser = { ...user, role: "Admin" };

Important for immutable state updates (React, Redux, etc.).

[[PAGE_BREAK]]

6) Default Parameters
function greet(name = "Guest") {
  return `Hello ${name}`;
}

If no value is passed, default is used.

7) Modules (import / export)
Export:
export const sum = (a, b) => a + b;

Import:
import { sum } from './math.js';

Essential for scalable architecture.

8) Promises (Asynchronous Programming)
const fetchData = () => {
  return new Promise((resolve, reject) => {
    resolve("Data received");
  });
};

fetchData()
  .then(data => console.log(data))
  .catch(error => console.error(error));

[[PAGE_BREAK]]

9) Async / Await
const fetchData = async () => {
  return "Data received";
};

async function run() {
  const data = await fetchData();
  console.log(data);
}

Cleaner syntax and easier error handling with try/catch.

10) Classes
class User {
  constructor(name) {
    this.name = name;
  }

  greet() {
    return `Hello ${this.name}`;
  }
}

const user1 = new User("Rakesh");
LESSON;
}
