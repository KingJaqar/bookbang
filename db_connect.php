<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$configPath = __DIR__ . '/hosting-config.php';
$hostingConfig = is_file($configPath) ? require $configPath : [];
$host = getenv('DB_HOST') ?: ($hostingConfig['DB_HOST'] ?? 'localhost');
$user = getenv('DB_USER') ?: ($hostingConfig['DB_USER'] ?? 'root');
$pass = getenv('DB_PASSWORD') ?: ($hostingConfig['DB_PASSWORD'] ?? '');
$db = getenv('DB_NAME') ?: ($hostingConfig['DB_NAME'] ?? 'bookbang_db');
$port = (int) (getenv('DB_PORT') ?: ($hostingConfig['DB_PORT'] ?? 3306));

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log('Bookbang database connection failed: ' . $error->getMessage());
    http_response_code(500);
    die('Database connection failed. Check DB_HOST, DB_USER, DB_PASSWORD, and DB_NAME, then import database/schema.sql.');
}
?>
