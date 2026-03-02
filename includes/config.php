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
