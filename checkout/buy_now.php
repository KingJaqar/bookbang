<?php
session_start();
include __DIR__ . '/../db_connect.php';

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
if(!$book_id) {
    echo json_encode(['success'=>false]);
    exit;
}

// Resolve user ID
$user_id = $_SESSION['user_id'] ?? null;
$session_key = session_id();

// Store "buy now" item in session
$_SESSION['buy_now'] = [
    'book_id' => $book_id,
    'user_id' => $user_id,
    'session_key' => $session_key
];

echo json_encode(['success'=>true]);
