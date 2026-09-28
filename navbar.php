<?php

//  Bookbang/navbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/app_url.php';

$loggedIn = !empty($_SESSION['user']);
$username = $_SESSION['user'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$applicationPath = bookbang_app_base();
$adminHref = $applicationPath . '/admin.php';

// Get cart count safely
$cart_count = 0;
if ($user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) AS count FROM carts WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt->close();
    }
} else {
    // OPTION A: No guest cart – always show 0 for guests
    $cart_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Project CSS -->
  <link rel="stylesheet" href="<?= htmlspecialchars($applicationPath . '/navbar.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <nav id="main-nav">
  <div class="navbar-container">
    <div class="navbar-left">
      <img src="<?= htmlspecialchars($applicationPath . '/Assets/bookbanglogo.png', ENT_QUOTES, 'UTF-8') ?>" alt="BookBang Logo" class="logo">
      <span class="logo-text">BookBang</span>
    </div>
    <div class="navbar-center">
      <input type="text" class="search-input" placeholder="Search books, authors, genres...">
      <button class="search-btn"><i class="bi bi-search"></i></button>
    </div>
    <div class="navbar-right">
      <a href="<?= htmlspecialchars($applicationPath . '/Homepage.php', ENT_QUOTES, 'UTF-8') ?>">Home</a>
      <a href="<?= htmlspecialchars($applicationPath . '/ProductPage.php', ENT_QUOTES, 'UTF-8') ?>">MyBooks</a>
      <a href="<?= htmlspecialchars($applicationPath . '/cart/cart.php', ENT_QUOTES, 'UTF-8') ?>">
        Cart <span id="cart-badge" style="background:red;color:white;border-radius:50%;padding:0 6px;font-size:12px;vertical-align:super;"><?= $cart_count ?></span>
      </a>
      <?php if ($loggedIn): ?>
        <a href="<?= htmlspecialchars($applicationPath . '/orders/order.php', ENT_QUOTES, 'UTF-8') ?>">Orders</a>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <a href="<?= htmlspecialchars($adminHref, ENT_QUOTES, 'UTF-8') ?>">Admin</a>
        <?php endif; ?>

        <a href="<?= htmlspecialchars($applicationPath . '/user/user.php', ENT_QUOTES, 'UTF-8') ?>">
            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
        </a>

        <a href="<?= htmlspecialchars($applicationPath . '/logout.php', ENT_QUOTES, 'UTF-8') ?>">Logout</a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($applicationPath . '/signup.php', ENT_QUOTES, 'UTF-8') ?>">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
