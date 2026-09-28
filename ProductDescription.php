<?php

// Bookbang/ProductDescription.php 

session_start();
include 'db_connect.php';
require_once __DIR__ . '/app_url.php';

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Resolve user_id for logged-in users
function resolveUserId($conn) {
    if (!empty($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    if (!empty($_SESSION['user'])) {
        $username = $_SESSION['user'];
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $_SESSION['user_id'] = intval($row['user_id']);
            $stmt->close();
            return $_SESSION['user_id'];
        }
        $stmt->close();
    }
    return null;
}

$user_id = resolveUserId($conn);
$session_key = session_id();

// Ensure library table exists
$conn->query("
CREATE TABLE IF NOT EXISTS library (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  session_id VARCHAR(128) NULL,
  book_id INT NOT NULL,
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_entry (user_id, session_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Handle Add to Library AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_library') {
    $book_id_post = intval($_POST['book_id']);
    $success = false;
    if ($user_id) {
        $stmt = $conn->prepare("INSERT IGNORE INTO library (user_id, book_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $book_id_post);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO library (session_id, book_id) VALUES (?, ?)");
        $stmt->bind_param("si", $session_key, $book_id_post);
        $success = $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['success' => $success]);
    exit;
}

// Fetch main book info
$book_stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$book_stmt->bind_param("i", $book_id);
$book_stmt->execute();
$book = $book_stmt->get_result()->fetch_assoc();
if (!$book) die("Book not found.");

// Fetch related books by genre
$related_stmt = $conn->prepare("SELECT * FROM books WHERE genre = ? AND book_id != ? LIMIT 5");
$related_stmt->bind_param("si", $book['genre'], $book_id);
$related_stmt->execute();
$related = $related_stmt->get_result();

// Check if book is already in library
if ($user_id) {
    $stmt = $conn->prepare("SELECT 1 FROM library WHERE user_id = ? AND book_id = ? LIMIT 1");
    $stmt->bind_param("ii", $user_id, $book_id);
} else {
    $stmt = $conn->prepare("SELECT 1 FROM library WHERE session_id = ? AND book_id = ? LIMIT 1");
    $stmt->bind_param("si", $session_key, $book_id);
}
$stmt->execute();
$stmt->store_result();
$in_library = $stmt->num_rows > 0;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book Bang | <?= htmlspecialchars($book['title']) ?></title>
<link rel="stylesheet" href="ProductDescription.css">
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="footer.css">
<link rel="stylesheet" href="bookbang-theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div id="navbar"></div>

<section class="product-container">
  <div class="product-images">
    <div class="main-image">
      <img src="<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
      <?php if ($book['discount'] > 0): ?>
        <div class="discount-badge">-<?= $book['discount'] ?>%</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="product-details">
    <h1 class="product-title"><?= htmlspecialchars($book['title']) ?></h1>
    <p class="author">by <?= htmlspecialchars($book['author']) ?></p>
    <p class="genre"><strong>Genre:</strong> <?= htmlspecialchars($book['genre']) ?></p>
    <p class="product-description"><?= nl2br(htmlspecialchars($book['description'])) ?></p>

    <div class="price-container">
      <?php 
        $discounted = $book['price'] * (1 - $book['discount'] / 100);
        echo '<span class="current-price">₱' . number_format($discounted, 2) . '</span>';
        if ($book['discount'] > 0) echo '<span class="original-price">₱' . number_format($book['price'], 2) . '</span>';
      ?>
    </div>

  <div class="action-buttons">
  <button class="add-to-cart-btn" data-book-id="<?= $book_id ?>">
    Add to Cart
  </button>
  <button class="buy-now-btn" data-book-id="<?= $book_id ?>">
    <i class="fa-solid fa-cart-shopping"></i> Buy Now
  </button>
</div>


    <span class="pickup-location"><i class="fa-solid fa-download"></i> Available for instant download</span>
  </div>
</section>

<section class="recommended-section">
  <h2>Similar Books You May Like</h2>
  <div class="book-shelf">
    <?php while ($r = $related->fetch_assoc()): ?>
      <div class="book" onclick="window.location.href='ProductDescription.php?id=<?= $r['book_id'] ?>'">
        <div class="cover">
          <div class="book-header" style="background-image: url('<?= htmlspecialchars($r['image']) ?>');"></div>
        </div>
        <div class="book-info">
          <h3><?= htmlspecialchars($r['title']) ?></h3>
          <p class="price">₱<?= number_format($r['price'], 2) ?></p>
          <p class="details"><?= htmlspecialchars($r['author'] ?? '') ?></p>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<div id="footer"></div>

<!-- Notification Div -->
<div id="cart-notification" style="
    position: fixed;
    top: 20px;
    right: 20px;
    background: #2ecc71;
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 9999;
"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    const buyNowBtn   = document.querySelector('.buy-now-btn');
    const notification = document.getElementById('cart-notification');
    const cartBadge = document.getElementById('cart-badge');

    function showNotification(msg) {
        if (notification) {
            notification.innerText = msg;
            notification.style.display = 'block';
            setTimeout(() => notification.style.display = 'none', 3000);
        }
    }

    function addToCart(bookId) {
         return fetch('<?= htmlspecialchars(bookbang_url('add_to_cart.php'), ENT_QUOTES, 'UTF-8') ?>', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'book_id=' + encodeURIComponent(bookId)
        }).then(res => res.json());
    }

    function updateCartBadge() {
        fetch('<?= htmlspecialchars(bookbang_url('cart_count.php'), ENT_QUOTES, 'UTF-8') ?>')
            .then(res => res.json())
            .then(data => {
                if(cartBadge) cartBadge.innerText = data.count ?? 0;
            });
    }

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const bookTitle = document.querySelector('.product-title').innerText;

            addToCart(bookId).then(data => {
                if (data.success) {
                    showNotification(`"${bookTitle}" added to cart!`);
                    updateCartBadge(); // <-- live update badge
                } else {
                    showNotification(`Failed to add "${bookTitle}" to cart`);
                }
            }).catch(() => {
                showNotification(`Successfully added "${bookTitle}" to cart`);
            });
        });
    }

   if (buyNowBtn) {
    buyNowBtn.addEventListener('click', function() {
        const bookId = this.dataset.bookId;
        fetch('<?= htmlspecialchars(bookbang_url('checkout/buy_now.php'), ENT_QUOTES, 'UTF-8') ?>', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'book_id=' + encodeURIComponent(bookId)
        }).then(res => res.json())
          .then(data => {
              if(data.success){
                  window.location.href = '<?= htmlspecialchars(bookbang_url('checkout/checkout.php'), ENT_QUOTES, 'UTF-8') ?>';
              } else {
                  alert('Failed to initiate Buy Now');
              }
          });
    });
   }

   // Initialize cart badge
   updateCartBadge();
});

// Load navbar
fetch('<?= htmlspecialchars(bookbang_url('navbar.php'), ENT_QUOTES, 'UTF-8') ?>')
    .then(r => r.text())
    .then(html => document.getElementById('navbar').innerHTML = html);
</script>

</body>
</html>
