<!-- Bookbang/ProductPage.php -->

<?php
include 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BookBang - Featured Titles & New Releases</title>
  <link rel="stylesheet" href="ProductPage.css">
  <link rel="stylesheet" href="bookbang-theme.css">
</head>
<body>

<div id="navbar"></div>

<section class="categories-section">
  <h2>📚 Our Curated Collection: Featured Titles & New Releases</h2>
</section>

<div class="book-shelf">
<?php
$sql = "SELECT * FROM books";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    echo '
    <div class="book" onclick="window.location.href=\'ProductDescription.php?id=' . $row['book_id'] . '\'">
      <div class="cover">
        <div class="book-header" style="background-image: url(' . $row['image'] . ');"></div>
      </div>
      <div class="book-info">
        <h3>' . htmlspecialchars($row['title']) . '</h3>
        <p>Author: ' . htmlspecialchars($row['author']) . '</p>
        <p>Genre: ' . htmlspecialchars($row['genre']) . '</p>
        <p class="price">₱' . number_format($row['price'], 2) . '</p>
      </div>
    </div>';
  }
}
?>
</div>

<div id="footer"></div>

<script>
  fetch('navbar.php').then(r => r.text()).then(d => document.getElementById('navbar').innerHTML = d);
  fetch('footer.php').then(r => r.text()).then(d => document.getElementById('footer').innerHTML = d);
</script>

</body>
</html>
