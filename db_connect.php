<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db = getenv('DB_NAME') ?: 'bookbang_db';

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log('Bookbang database connection failed: ' . $error->getMessage());
    http_response_code(500);
    die('Database connection failed. Check DB_HOST, DB_USER, DB_PASSWORD, and DB_NAME, then import database/schema.sql.');
}
?>
