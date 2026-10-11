<?php

// Bookbang/ProductDescription.php 

require_once __DIR__ . '/app.php';
$book_id = intval($_GET['id'] ?? 0);
// Fetch main book info
$book_stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$book_stmt->bind_param("i", $book_id);
$book_stmt->execute();
$book = $book_stmt->get_result()->fetch_assoc();
if (!$book) { http_response_code(404); exit('Book not found. <a href="ProductPage.php">Browse books</a>'); }

// Fetch related books by genre
$related_stmt = $conn->prepare("SELECT * FROM books WHERE genre = ? AND book_id != ? LIMIT 5");
$related_stmt->bind_param("si", $book['genre'], $book_id);
$related_stmt->execute();
$related = $related_stmt->get_result();

[$where,$types,$args]=bb_owner();
$in_library=(bool)bb_one('SELECT id FROM library WHERE '.$where.' AND book_id=?',$types.'i',array_merge($args,[$book_id]));
$reviews=bb_rows('SELECT username,rating,review_text,created_at FROM reviews WHERE book_id=? ORDER BY review_id DESC LIMIT 50','i',[$book_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Book Bang | <?= htmlspecialchars($book['title']) ?></title>
<link rel="stylesheet" href="ProductDescription.css"><link rel="stylesheet" href="backend.css">
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="footer.css">
<link rel="stylesheet" href="bookbang-theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

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
<form action="library.php" method="post"><input type="hidden" name="csrf" value="<?= bb_h(bb_csrf()) ?>"><input type="hidden" name="book_id" value="<?= $book_id ?>"><input type="hidden" name="action" value="<?= $in_library ? 'remove' : 'save' ?>"><button><?= $in_library ? 'Remove from saved books' : 'Save book' ?></button></form></div>

    <span class="pickup-location"><i class="fa-solid fa-download"></i> Cash on Delivery available</span>
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

<?php include 'footer.php'; ?>

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


<section class="bb-panel"><h2>Reader reviews</h2>
<?php foreach($reviews as $review): ?><article><strong><?= bb_h($review['username']) ?> · <?= (int)$review['rating'] ?>/5</strong><p><?= nl2br(bb_h($review['review_text'])) ?></p></article><?php endforeach; ?>
<?php if(!$reviews): ?><p>No reviews yet.</p><?php endif; ?>
<?php if(bb_user_id()): ?><form action="submit_review.php" method="post"><input type="hidden" name="csrf" value="<?= bb_h(bb_csrf()) ?>"><input type="hidden" name="book_id" value="<?= $book_id ?>"><label>Rating<select name="rating"><?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?>/5</option><?php endfor; ?></select></label><label>Your review<textarea name="review_text" maxlength="2000" required></textarea></label><button>Post review</button></form><?php else: ?><p><a href="login.php">Sign in</a> to write a review.</p><?php endif; ?></section>
<script>
const csrf=<?= json_encode(bb_csrf()) ?>;
const notice=document.getElementById('cart-notification');
async function action(button,url){
 button.disabled=true;
 try{const response=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({book_id:button.dataset.bookId,csrf})});const data=await response.json();if(!response.ok||!data.success) throw new Error(data.error||'Please try again.');
 if(data.url){window.location.href=data.url;return;}
 document.getElementById('cart-badge').textContent=data.cart_count;
 notice.textContent='Book added to cart.';notice.style.background='#2ecc71';
 }catch(error){notice.textContent='Could not complete this action. '+error.message;notice.style.background='#aa2233';}
 finally{button.disabled=false;notice.style.display='block';setTimeout(()=>notice.style.display='none',4000);}
}
document.querySelector('.add-to-cart-btn').addEventListener('click',function(){action(this,<?= json_encode(bookbang_url('add_to_cart.php')) ?>)});
document.querySelector('.buy-now-btn').addEventListener('click',function(){action(this,<?= json_encode(bookbang_url('checkout/buy_now.php')) ?>)});
</script>


</body>
</html>
