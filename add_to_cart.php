<?php
session_start();
include __DIR__ . '/db_connect.php';

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
if ($book_id <= 0) { 
    http_response_code(400); 
    echo json_encode(['error'=>'Invalid book']); 
    exit; 
}

$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

// Check if the book is already in the cart
if ($user_id) {
    $stmt = $conn->prepare("SELECT cart_id, quantity FROM carts WHERE book_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $book_id, $user_id);
} else {
    $stmt = $conn->prepare("SELECT cart_id, quantity FROM carts WHERE book_id = ? AND session_id = ? LIMIT 1");
    $stmt->bind_param("is", $book_id, $session_id);
}
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    // Update quantity
    $stmt2 = $conn->prepare("UPDATE carts SET quantity = quantity + 1 WHERE cart_id = ?");
    $stmt2->bind_param("i", $row['cart_id']);
    $stmt2->execute();
    $stmt2->close();
} else {
    // Insert new cart item
    if ($user_id) {
        $stmt2 = $conn->prepare("INSERT INTO carts (user_id, book_id, quantity) VALUES (?, ?, 1)");
        $stmt2->bind_param("ii", $user_id, $book_id);
    } else {
        $stmt2 = $conn->prepare("INSERT INTO carts (session_id, book_id, quantity) VALUES (?, ?, 1)");
        $stmt2->bind_param("si", $session_id, $book_id);
    }
    $stmt2->execute();
    $stmt2->close();
}
$stmt->close();

// Return updated cart count
if ($user_id) {
    $stmt3 = $conn->prepare("SELECT SUM(quantity) as count FROM carts WHERE user_id = ?");
    $stmt3->bind_param("i", $user_id);
} else {
    $stmt3 = $conn->prepare("SELECT SUM(quantity) as count FROM carts WHERE session_id = ?");
    $stmt3->bind_param("s", $session_id);
}
$stmt3->execute();
$count = $stmt3->get_result()->fetch_assoc()['count'] ?? 0;
$stmt3->close();

echo json_encode(['success'=>true, 'cart_count'=>intval($count)]);
