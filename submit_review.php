<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $book_id = intval($_POST['book_id']);
  $username = $conn->real_escape_string($_POST['username']);
  $rating = intval($_POST['rating']);
  $review_text = $conn->real_escape_string($_POST['review_text']);

  $sql = "INSERT INTO reviews (book_id, username, rating, review_text) VALUES ($book_id, '$username', $rating, '$review_text')";
  $conn->query($sql);

  // Redirect back to the product page
  header("Location: ProductDescription.php?id=" . $book_id);
  exit;
}
?>
