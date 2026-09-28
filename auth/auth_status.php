// Bookbang/auth/auth_status.php

// auth_status.php - returns JSON { logged_in: bool, username: string|null }
<?php
session_start();
header('Content-Type: application/json');
include 'db_connect.php';

$response = ['logged_in' => false, 'username' => null];

if (!empty($_SESSION['user'])) {
    $response['logged_in'] = true;
    $response['username'] = $_SESSION['user'];
    // optional: if user_id missing, try to resolve and set it
    if (empty($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $_SESSION['user']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $_SESSION['user_id'] = intval($row['user_id']);
        }
        $stmt->close();
    }
}

echo json_encode($response);
