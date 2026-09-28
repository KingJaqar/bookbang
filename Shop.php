<?php
$shopHost = getenv('SHOP_DB_HOST') ?: 'localhost';
$shopUser = getenv('SHOP_DB_USER') ?: 'root';
$shopPassword = getenv('SHOP_DB_PASSWORD') ?: '';
$shopDatabase = getenv('SHOP_DB_NAME') ?: 'bookbang_products';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
  $conn = new mysqli($shopHost, $shopUser, $shopPassword, $shopDatabase);
  $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
  error_log('Legacy shop database connection failed: ' . $error->getMessage());
  http_response_code(500);
  die('The legacy shop requires its own configured product database.');
}

// Get filter values from URL parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$brand = isset($_GET['brand']) ? $_GET['brand'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12; // Number of products per page

// Build the SQL query based on filters
$sql = "SELECT * FROM products WHERE 1=1";

if ($category != 'all') {
    $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
}

if ($brand != 'all') {
    $sql .= " AND brand = '" . $conn->real_escape_string($brand) . "'";
}

// Add sorting
switch ($sort) {
    case 'price-low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price-high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name-asc':
        $sql .= " ORDER BY product_name ASC";
        break;
    case 'name-desc':
        $sql .= " ORDER BY product_name DESC";
        break;
    default:
        // Assuming you want featured products first (you might need to add a 'featured' column to your table)
        $sql .= " ORDER BY product_id ASC"; 
}

// Count total products for pagination
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_result = $conn->query($count_sql);
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $items_per_page);

// Add pagination limits
$offset = ($page - 1) * $items_per_page;
$sql .= " LIMIT $offset, $items_per_page";

// Execute the main query to get products
$productResult = $conn->query($sql);

// Get categories and brands for dropdowns
$categories_sql = "SELECT DISTINCT category FROM products ORDER BY category";
$categoryResult = $conn->query($categories_sql);

$brands_sql = "SELECT DISTINCT brand FROM products ORDER BY brand";
$brandResult = $conn->query($brands_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Motoworld</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./includes/footer.css">
    <link rel="stylesheet" href="./includes/navbar.css">
      <style>
        /* General Styles */
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
          background-color: #f5f5f5;
        }
        
        /* Hero Section */
        .hero-section {
          background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('./Assets/Logo/shop1.jpg');
          background-size: cover;
          background-position: center;
          height: 550px;
          color: white;
          text-align: center;
          padding: 80px 20px;
          margin-bottom: 30px;
        }
        
        .hero-section h1 {
          font-size: 36px;
          margin-bottom: 15px;
        }
        
        .hero-section p {
          font-size: 18px;
          max-width: 600px;
          margin: 0 auto;
        }
        
        /* Filter Section */
        .filter-section {
          padding: 0 20px;
          margin-bottom: 30px;
        }
        
        .filter-container {
          display: flex;
          flex-wrap: wrap;
          gap: 20px;
          max-width: 1200px;
          margin: 0 auto;
          background-color: white;
          padding: 20px;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .filter-group {
          display: flex;
          flex-direction: column;
          min-width: 200px;
        }
        
        .filter-group label {
          margin-bottom: 8px;
          font-size: 14px;
          color: #666;
        }
        
        .filter-group select {
          padding: 10px;
          border: 1px solid #ddd;
          border-radius: 4px;
          background-color: white;
          cursor: pointer;
        }
        
        /* Products Container */
        .products-container {
          padding: 0 20px;
          margin-bottom: 50px;
        }
        
        .products-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
          gap: 30px;
          max-width: 1200px;
          margin: 0 auto;
        }
        
        /* Product Card */
        .product-card {
          background-color: white;
          border-radius: 8px;
          overflow: hidden;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          transition: transform 0.2s, box-shadow 0.2s;
          cursor: pointer;
          padding: 15px;
        }
        
        .product-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
          width: 100%;
          height: 200px;
          object-fit: cover;
          margin-bottom: 15px;
          border-radius: 4px;
        }
        
        .no-image {
          width: 100%;
          height: 200px;
          display: flex;
          align-items: center;
          justify-content: center;
          background-color: #f0f0f0;
          color: #999;
          font-size: 14px;
          margin-bottom: 15px;
          border-radius: 4px;
        }
        
        .product-name {
          font-size: 16px;
          margin-bottom: 10px;
          color: #333;
          min-height: 40px;
        }
        
        .product-meta {
          display: flex;
          justify-content: space-between;
          margin-bottom: 10px;
          font-size: 14px;
          color: #666;
        }
        
        .product-category, .product-brand {
          background-color: #f0f0f0;
          padding: 3px 8px;
          border-radius: 4px;
        }
        
        .product-price {
          font-size: 18px;
          font-weight: bold;
          color: #ff3f3f;
          margin-bottom: 15px;
        }
        
        .add-to-cart {
          display: block;
          width: 100%;
          padding: 10px;
          background-color: #333;
          color: white;
          text-align: center;
          border: none;
          border-radius: 4px;
          font-weight: bold;
          cursor: pointer;
          transition: background-color 0.2s;
        }
        
        .add-to-cart:hover {
          background-color: #555;
        }
        
        /* No Products Message */
        .no-products {
          grid-column: 1 / -1;
          text-align: center;
          padding: 50px 20px;
          font-size: 16px;
          color: #666;
        }
        
        /* Pagination */
        .pagination {
          display: flex;
          justify-content: center;
          gap: 10px;
          margin: 30px 0 50px;
        }
        
        .pagination a {
          width: 40px;
          height: 40px;
          display: flex;
          align-items: center;
          justify-content: center;
          border: 1px solid #ddd;
          border-radius: 4px;
          cursor: pointer;
          text-decoration: none;
          color: #333;
          transition: all 0.2s;
        }
        
        .pagination a.active {
          background-color: #333;
          color: white;
          border-color: #333;
        }
        
        .pagination a:hover:not(.active) {
          border-color: #333;
        }
        
        /* Chat Button */
        .chat-button {
          position: fixed;
          bottom: 30px;
          right: 30px;
          width: 60px;
          height: 60px;
          border-radius: 50%;
          background-color: #ff3f3f;
          color: white;
          border: none;
          box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;
          cursor: pointer;
          transition: transform 0.2s, background-color 0.2s;
        }
        
        .chat-button:hover {
          transform: scale(1.1);
          background-color: #e63636;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
          .filter-container {
              flex-direction: column;
          }
          
          .filter-group {
              width: 100%;
          }
          
          .products-grid {
              grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
              gap: 15px;
          }
          
          .product-name {
              font-size: 14px;
          }
          
          .product-price {
              font-size: 16px;
          }
        }
    </style>
<body>
    <div id="navbar"></div>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1>Find Your Perfect Gear</h1>
        <p>Quality motorcycle gear for every rider</p>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-container">
            <form action="shop.php" method="GET" id="filter-form">
                <div class="filter-group">
                    <label for="category-filter">Category</label>
                    <select id="category-filter" name="category" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        <?php
                        if ($categoryResult && $categoryResult->num_rows > 0) {
                            while($catRow = $categoryResult->fetch_assoc()) {
                                $selected = (isset($_GET['category']) && $_GET['category'] == $catRow['category']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($catRow['category']) . "' $selected>" . htmlspecialchars($catRow['category']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="brand-filter">Brands</label>
                    <select id="brand-filter" name="brand" onchange="this.form.submit()">
                        <option value="all">All Brands</option>
                        <?php
                        if ($brandResult && $brandResult->num_rows > 0) {
                            while($brandRow = $brandResult->fetch_assoc()) {
                                $selected = (isset($_GET['brand']) && $_GET['brand'] == $brandRow['brand']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($brandRow['brand']) . "' $selected>" . htmlspecialchars($brandRow['brand']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort-by">Sort By</label>
                    <select id="sort-by" name="sort" onchange="this.form.submit()">
                        <option value="featured" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'featured') ? 'selected' : ''; ?>>Featured</option>
                        <option value="price-low" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-low') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price-high" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name-asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name-asc') ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name-desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name-desc') ? 'selected' : ''; ?>>Name: Z to A</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="products-container">
        <div class="products-grid" id="products-grid">
            <?php
            // Display products
            if ($productResult && $productResult->num_rows > 0) {
                while($row = $productResult->fetch_assoc()) {
                    ?>
                    <div class="product-card" onclick="window.location.href='productPage.php?id=<?php echo $row['product_id']; ?>'">
                        <?php if (!empty($row['images'])): ?>
                            <img src="<?php echo htmlspecialchars($row['images']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="no-image">No Image Available</div>
                        <?php endif; ?>
                        
                        <h3 class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></h3>
                        
                        <div class="product-meta">
                            <span class="product-category"><?php echo htmlspecialchars($row['category']); ?></span>
                            <span class="product-brand"><?php echo htmlspecialchars($row['brand']); ?></span>
                        </div>
                        
                        <p class="product-price">₱<?php echo number_format($row['price'], 2); ?></p>
                        
                        <button class="add-to-cart" data-product-id="<?php echo $row['product_id']; ?>" onclick="event.stopPropagation(); addToCart(<?php echo $row['product_id']; ?>)">
                            Add to Cart
                        </button>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-products">No products found matching your criteria.</div>';
            }
            ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?category=<?php echo urlencode($category); ?>&brand=<?php echo urlencode($brand); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $i; ?>" 
               class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <!-- Chat Button -->
    <button class="chat-button">
        <i class="fas fa-comment"></i>
    </button>

    <div id="footer"></div>

    <!-- JAVASCRIPT -->
     
    <script>
        // Load the navbar
        fetch('./includes/navbar.php')
          .then(res => res.text())
          .then(data => document.getElementById('navbar').innerHTML = data);
      
        // Load the footer
        fetch('./includes/footer.php')
          .then(res => res.text())
          .then(data => document.getElementById('footer').innerHTML = data);
          
        // Add to cart functionality
        function addToCart(productId) {
            // Here you would implement AJAX to add to cart
            alert('Product ' + productId + ' added to cart!');
            // Prevent the click from propagating to the parent element
            event.stopPropagation();
        }
        
        // Make product cards clickable to navigate to product page
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function() {
                const productId = this.querySelector('.add-to-cart').getAttribute('data-product-id');
                window.location.href = 'productPage.php?id=' + productId;
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>