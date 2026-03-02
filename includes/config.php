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

    if ($conn->query("SHOW TABLES LIKE 'lessons'")->num_rows) {
        $lessonColumns = [];
        $lcols = $conn->query("SHOW COLUMNS FROM lessons");
        while ($row = $lcols->fetch_assoc()) {
            $lessonColumns[$row['Field']] = true;
        }
        if (!isset($lessonColumns['is_preview'])) {
            $conn->query("ALTER TABLE lessons ADD COLUMN is_preview TINYINT(1) DEFAULT 0");
        }

        seedDefaultLessonsAndQuizzes($conn, $courseColumns, $lessonColumns);
    }
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
        ['Getting Started', 'Understand the fundamentals and workflow for this course.'],
        ['Core Concepts', 'Learn the essential concepts you must know before practice.'],
        ['Hands-on Practice', 'Apply concepts with practical examples and mini tasks.'],
        ['Real-world Implementation', 'Use this lesson to connect theory with real projects.'],
        ['Summary and Next Steps', 'Review key takeaways and plan your next improvements.'],
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
                $content = $template[1] . ' In "' . $courseTitle . '", focus on consistent learning and implementation.';
                $duration = (string)(10 + $order * 5) . ' min';
                $preview = $order === 1 ? 1 : 0;
                $insertLesson->bind_param("isssii", $courseId, $title, $content, $duration, $order, $preview);
                $insertLesson->execute();
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
    }

    $missingQuizzes = $conn->query("SELECT l.id as lesson_id, l.course_id, l.title as lesson_title, c.title as course_title
                                    FROM lessons l
                                    JOIN courses c ON c.id=l.course_id
                                    LEFT JOIN quizzes q ON q.lesson_id=l.id
                                    WHERE q.id IS NULL");
    if (!$missingQuizzes) {
        return;
    }

    $insertQuiz = $conn->prepare("INSERT INTO quizzes (course_id, lesson_id, title, pass_percentage) VALUES (?, ?, ?, 60)");
    $insertQuestion = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");

    while ($row = $missingQuizzes->fetch_assoc()) {
        $courseId = (int)$row['course_id'];
        $lessonId = (int)$row['lesson_id'];
        $lessonTitle = $row['lesson_title'];
        $courseTitle = $row['course_title'];
        $quizTitle = 'Quiz: ' . $lessonTitle;

        $insertQuiz->bind_param("iis", $courseId, $lessonId, $quizTitle);
        $insertQuiz->execute();
        $quizId = (int)$conn->insert_id;
        if ($quizId <= 0) {
            continue;
        }

        $questions = [
            [
                "What is the main objective of \"" . $lessonTitle . "\" in " . $courseTitle . "?",
                "Understand and apply the key lesson concepts",
                "Skip fundamentals and jump randomly",
                "Only memorize definitions",
                "Ignore practice activities",
                "A",
            ],
            [
                "Which approach gives the best learning result for this lesson?",
                "Read once and avoid exercises",
                "Practice examples and review mistakes",
                "Only watch videos without action",
                "Rely only on guesswork",
                "B",
            ],
            [
                "To complete this lesson effectively, you should:",
                "Stop after the first concept",
                "Avoid taking notes",
                "Apply concepts in a small task or project",
                "Skip all assessments",
                "C",
            ],
        ];

        foreach ($questions as $q) {
            $insertQuestion->bind_param("issssss", $quizId, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]);
            $insertQuestion->execute();
        }
    }
}
