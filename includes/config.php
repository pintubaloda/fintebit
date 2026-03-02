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
        while ($lesson = $rows->fetch_assoc()) {
            $text = trim((string)$lesson['content']);
            $isGeneric = strlen($text) < 140
                || stripos($text, 'fundamentals and workflow') !== false
                || stripos($text, 'consistent learning and implementation') !== false;
            if ($isGeneric) {
                $newContent = generateLessonContent($courseTitle, $lesson['title'], (int)$lesson['order_num'], 'Practical lesson content');
                $lessonId = (int)$lesson['id'];
                $updateLesson->bind_param("si", $newContent, $lessonId);
                $updateLesson->execute();
            }

            // Apply a high-detail example lesson for JavaScript ES6+ core lesson.
            if (
                stripos($courseTitle, 'JavaScript ES6+') !== false &&
                stripos($lesson['title'], 'Core') !== false
            ) {
                $lessonId = (int)$lesson['id'];
                $es6Content = getJavascriptEs6CoreLessonContent();
                $updateLesson->bind_param("si", $es6Content, $lessonId);
                $updateLesson->execute();
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

function generateLessonContent($courseTitle, $lessonTitle, $orderNum, $focusLine) {
    $step = max(1, (int)$orderNum);
    $practiceTask = "Create a short implementation related to \"" . $courseTitle . "\" and validate the result with one test case.";
    if ($step === 1) {
        $practiceTask = "Set up your working environment and document the initial setup steps for future reuse.";
    } elseif ($step === 2) {
        $practiceTask = "Implement one complete workflow and note where each concept is applied.";
    } elseif ($step >= 5) {
        $practiceTask = "Debug two common errors and write a short checklist to prevent them.";
    }

    return
        "Lesson goal:\n" .
        "Master \"" . $lessonTitle . "\" for " . $courseTitle . " with practical understanding.\n\n" .
        "What you will learn:\n" .
        "1. " . $focusLine . "\n" .
        "2. How this topic fits into real implementation work.\n" .
        "3. Common mistakes and how to avoid them.\n\n" .
        "Step-by-step guide:\n" .
        "- Start with the core concept and define expected output.\n" .
        "- Implement the concept in a small, focused example.\n" .
        "- Validate output, then refactor for clarity and reuse.\n\n" .
        "Practice task:\n" .
        $practiceTask . "\n\n" .
        "Completion checklist:\n" .
        "- You can explain the concept in your own words.\n" .
        "- You can implement it without copying.\n" .
        "- You can identify at least one optimization or improvement.\n\n" .
        "Next step:\n" .
        "Attempt the lesson quiz and pass it to mark this lesson complete.";
}

function getJavascriptEs6CoreLessonContent() {
    return
        "Lesson: Core Concepts of JavaScript ES6+\n\n" .
        "JavaScript ES6 (ECMAScript 2015) introduced major improvements that modernized the language. " .
        "If you're building SaaS platforms, APIs, and real-time systems, understanding ES6+ is essential for frontend apps, Node.js services, and modern frameworks.\n\n" .
        "1) let and const (Block Scope)\n" .
        "Before ES6, var had function scope and often caused bugs.\n\n" .
        "let (can be reassigned)\n" .
        "let count = 5;\n" .
        "count = 10; // allowed\n\n" .
        "const (cannot be reassigned)\n" .
        "const apiUrl = \"https://api.example.com\";\n" .
        "// apiUrl = \"newurl\"; // Error\n\n" .
        "Key Difference:\n" .
        "- var -> function scoped\n" .
        "- let and const -> block scoped { }\n" .
        "- Prefer const by default\n\n" .
        "2) Arrow Functions (=>)\n" .
        "Traditional function:\n" .
        "function add(a, b) {\n" .
        "  return a + b;\n" .
        "}\n\n" .
        "ES6 arrow function:\n" .
        "const add = (a, b) => a + b;\n\n" .
        "Arrow functions do not bind their own this, making them great for callbacks.\n\n" .
        "3) Template Literals (Backticks)\n" .
        "const name = \"Rakesh\";\n" .
        "const message = `Hello, ${name}! Welcome to ES6.`;\n\n" .
        "Benefits: cleaner dynamic strings, interpolation, multi-line support.\n\n" .
        "4) Destructuring\n" .
        "Array destructuring:\n" .
        "const numbers = [1, 2, 3];\n" .
        "const [a, b] = numbers;\n\n" .
        "Object destructuring:\n" .
        "const user = { id: 1, name: \"Rakesh\" };\n" .
        "const { id, name } = user;\n\n" .
        "Very useful in APIs and frontend frameworks.\n\n" .
        "5) Spread Operator (...)\n" .
        "Array example:\n" .
        "const arr1 = [1, 2];\n" .
        "const arr2 = [...arr1, 3, 4];\n\n" .
        "Object example:\n" .
        "const user = { name: \"Rakesh\" };\n" .
        "const updatedUser = { ...user, role: \"Admin\" };\n\n" .
        "Important for immutable state updates (React, Redux, etc.).\n\n" .
        "6) Default Parameters\n" .
        "function greet(name = \"Guest\") {\n" .
        "  return `Hello ${name}`;\n" .
        "}\n\n" .
        "If no value is passed, default is used.\n\n" .
        "7) Modules (import / export)\n" .
        "Export:\n" .
        "export const sum = (a, b) => a + b;\n\n" .
        "Import:\n" .
        "import { sum } from './math.js';\n\n" .
        "Essential for scalable architecture.\n\n" .
        "8) Promises (Asynchronous Programming)\n" .
        "const fetchData = () => {\n" .
        "  return new Promise((resolve, reject) => {\n" .
        "    resolve(\"Data received\");\n" .
        "  });\n" .
        "};\n\n" .
        "fetchData()\n" .
        "  .then(data => console.log(data))\n" .
        "  .catch(error => console.error(error));\n\n" .
        "9) Async / Await\n" .
        "const fetchData = async () => {\n" .
        "  return \"Data received\";\n" .
        "};\n\n" .
        "async function run() {\n" .
        "  const data = await fetchData();\n" .
        "  console.log(data);\n" .
        "}\n\n" .
        "Cleaner syntax and easier error handling with try/catch.\n\n" .
        "10) Classes\n" .
        "class User {\n" .
        "  constructor(name) {\n" .
        "    this.name = name;\n" .
        "  }\n\n" .
        "  greet() {\n" .
        "    return `Hello ${this.name}`;\n" .
        "  }\n" .
        "}\n\n" .
        "const user1 = new User(\"Rakesh\");";
}
