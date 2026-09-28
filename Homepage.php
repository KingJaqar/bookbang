<!-- Bookbang/Homepage.php  -->
<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// === Create a table to track weekly carousel updates if not exists ===
$conn->query("
  CREATE TABLE IF NOT EXISTS weekly_featured (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(50),
    book_ids VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )
");

// === Helper function: Get or update weekly random carousel (IDs 1–25 only) ===
function getWeeklyBooks($conn, $section, $limit) {
  $stmt = $conn->prepare("SELECT * FROM weekly_featured WHERE section = ? ORDER BY id DESC LIMIT 1");
  $stmt->bind_param("s", $section);
  $stmt->execute();
  $result = $stmt->get_result();

  $now = new DateTime();
  $bookIds = [];
  $weekPassed = true;

  if ($row = $result->fetch_assoc()) {
    $updatedAt = new DateTime($row['updated_at']);
    $interval = $now->diff($updatedAt);
    if ($interval->days < 7) { // still within a week
      $weekPassed = false;
      $bookIds = explode(',', $row['book_ids']);
    }
  }
  $stmt->close();

  // ✅ Limit random selection to book_id between 1 and 25
  if ($weekPassed) {
    $ids = [];
    $res = $conn->query("SELECT book_id FROM books WHERE book_id BETWEEN 1 AND 25 ORDER BY RAND() LIMIT $limit");
    while ($r = $res->fetch_assoc()) $ids[] = $r['book_id'];
    $bookIds = $ids;

    $idStr = implode(',', $bookIds);
    $insert = $conn->prepare("INSERT INTO weekly_featured (section, book_ids, updated_at) VALUES (?, ?, NOW())");
    $insert->bind_param("ss", $section, $idStr);
    $insert->execute();
    $insert->close();
  }

  // Fetch the selected books
  $idList = implode(',', array_map('intval', $bookIds));
  return $conn->query("SELECT * FROM books WHERE book_id IN ($idList)");
}

// === Fetch weekly randomized carousel books ===
$carouselResult = getWeeklyBooks($conn, 'carousel', 6);

// === Fetch fixed 'New Arrivals' ===
$newArrivalIDs = [1, 6, 11, 16, 21];
$idList = implode(',', array_map('intval', $newArrivalIDs));
$newArrivalsResult = $conn->query("SELECT * FROM books WHERE book_id IN ($idList)");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book Bang</title>

  <!-- CSS -->
  <link rel="stylesheet" href="Homepage.css" />
  <link rel="stylesheet" href="Carousel.css" />
  <link rel="stylesheet" href="footer.css">
  <link rel="stylesheet" href="navbar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="bookbang-theme.css">
</head>

<body>
  <!-- Navbar -->
  <?php include 'navbar.php'; ?>

  <!-- === Carousel Section === -->
  <div class="carousel">
    <div class="list">
      <?php while ($book = $carouselResult->fetch_assoc()): ?>
        <div class="item">
          <div class="poster">
            <div class="CarouselMoviePosters">
              <a href="ProductDescription.php?id=<?= $book['book_id'] ?>">
                <img src="<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
              </a>
            </div>
          </div>
          <div class="introduce">
            <div class="title">Top Books This Week!</div>
            <div class="topic"><?= htmlspecialchars($book['title']) ?></div>
            <div class="des"><?= htmlspecialchars($book['description']) ?></div>
            <a href="ProductDescription.php?id=<?= $book['book_id'] ?>">
              <button class="seeMore">BUY NOW &#8599;</button>
            </a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <div class="arrows">
      <button id="prev"><</button>
      <button id="next">></button>
    </div>
  </div>

  <!-- === Announcement Bar === -->
  <div class="announcement-bar">
    <ul class="announcement-slider">
      <li>SAVE UP TO 50% OFF ON SELECTED ITEMS</li>
      <li>Spend ₱4,999 & Get ₱100 Discount!</li>
    </ul>
  </div>

  <!-- === New Arrivals Section === -->
  <div class="text-center"><h3>New Arrivals</h3></div>

  <div class="book-shelf">
    <?php while ($book = $newArrivalsResult->fetch_assoc()): ?>
      <div class="book">
        <a href="ProductDescription.php?id=<?= $book['book_id'] ?>" style="text-decoration:none; color:inherit;">
          <div class="cover">
            <img src="<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" style="width:100%; border-radius:8px;">
          </div>
          <div class="book-info">
            <h3><?= htmlspecialchars($book['title']) ?></h3>
            <p>Author: <?= htmlspecialchars($book['author']) ?></p>
            <p>Genre: <?= htmlspecialchars($book['genre']) ?></p>
            <p class="price">₱<?= number_format($book['price'], 2) ?></p>
            <p class="details">
              Pages: <?= htmlspecialchars($book['pages']) ?>,<br>
              <?= htmlspecialchars($book['collection']) ?>
            </p>
          </div>
        </a>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- Floating Chat -->
  <div class="floating-chat">
    <button class="btn btn-danger rounded-circle p-3">
      <i class="bi bi-chat-fill"></i>
    </button>
  </div>

  <!-- Brief Description -->
  <div class="brief-description">
    <h3 class="text-center">A world of reading awaits you</h3>
    <p class="text-center">
      Create a BookBang account and sign up for our emails. Find out about new releases, shop deals on books, and get reading recommendations from our team.
    </p>
    <p class="text-center">
      Discover bestsellers, indie gems, and exclusive digital releases to fill your virtual bookshelf anytime, anywhere!
    </p>
    <div class="text-center"><button>Create Account</button></div>
  </div>

  <!-- Footer -->
  <?php include 'footer.php'; ?>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="Carousel.js"></script>
</body>
</html>

<?php $conn->close(); ?>
