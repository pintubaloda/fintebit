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
if (!defined('SITE_URL')) {
    $siteUrl = getenv('SITE_URL');
    if (!$siteUrl) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $siteUrl = $scheme . '://' . $host;
    }
    define('SITE_URL', rtrim($siteUrl, '/'));
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
if (!$conn->connect_error) {
    $dbEscaped = $conn->real_escape_string(DB_NAME);
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbEscaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);
    $conn->set_charset('utf8mb4');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sanitize($conn, $str) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($str))));
}
?>
