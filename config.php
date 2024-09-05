<?php
// Error reporting (for production, errors should be logged, not displayed)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/your/error.log');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_strong_database_password');
define('DB_NAME', 'octocrypt_db');

// Redis configuration
define('REDIS_HOST', 'localhost');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', 'your_strong_redis_password');

// Application settings
define('APP_NAME', 'OctoCrypt');
define('APP_URL', 'https://octocrypt.eu');

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.");
}
$conn->set_charset("utf8mb4");

// Redis connection
$redis = new Redis();
try {
    $redis->connect(REDIS_HOST, REDIS_PORT);
    $redis->auth(REDIS_PASSWORD);
} catch (Exception $e) {
    error_log("Redis connection failed: " . $e->getMessage());
    die("Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.");
}

// Security function
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Authentication function
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . APP_URL . "/login.php");
        exit();
    }
}

// Clean up function
function cleanUp() {
    global $conn, $redis;
    $conn->close();
    $redis->close();
}

// Register clean up function
register_shutdown_function('cleanUp');

// Set default timezone
date_default_timezone_set('Europe/Berlin');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rate Limiting 
function checkRateLimit($key, $limit, $period) {
    global $redis;
    $current = $redis->incr($key);
    if ($current === 1) {
        $redis->expire($key, $period);
    }
    return $current > $limit;
}
