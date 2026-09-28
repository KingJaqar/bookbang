<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ST Moto & Gear</title>

  <!-- CSS -->
   <link rel="stylesheet" href="Homepage.css" />

  <!-- Include your existing CSS files here -->
  <link rel="stylesheet" href="./includes/footer.css">
  <link rel="stylesheet" href="./includes/navbar.css">


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
crossorigin=""/>
<link rel="stylesheet" href="../bookbang-theme.css" />
</head>
<body>

  <div id="navbar"></div>

  <!-- Carousel -->
  <div id="homepage-carousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="htdocs\SIAPHP\bookbang\homepage-image\header1.jpg" class="d-block w-100" alt="Slide 1">
      </div>
      <div class="carousel-item">
        <img src="htdocs\SIAPHP\bookbang\homepage-image\2.jpg" class="d-block w-100" alt="Slide 2">
      </div>
      <div class="carousel-item">
        <img src="htdocs\SIAPHP\bookbang\homepage-image\header3.jpg" class="d-block w-100" alt="Slide 3">
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#homepage-carousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#homepage-carousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>

  <!-- Announcement Bar -->
  <div class="announcement-bar">
    <ul class="announcement-slider">
      <li>SAVE UP TO 50% OFF ON SELECTED ITEMS</li>
      <li>Spend P4,500 & Get P100 Shipping Discount!</li>
    </ul>
  </div>

  <!-- New Arrivals -->
  <section class="section new-arrivals text-center py-5">
    <h2 class="section-title">Newest Arrivals</h2>
    <div class="container">
      <div class="row g-4 justify-content-center">
        <!-- Repeat for 3 arrivals -->
        <div class="col-md-4">
          <div class="product-card">
            <img src="Assets/Arrival/newarrival1.png" alt="Product 1" class="product-image" />
          </div>
        </div>
        <div class="col-md-4">
          <div class="product-card">
            <img src="Assets/Arrival/newarrival4.png" alt="Product 2" class="product-image" />
          </div>
        </div>
        <div class="col-md-4">
          <div class="product-card">
            <img src="Assets/Arrival/newarrival5.png" alt="Product 3" class="product-image" />
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="container adverts">
    <div class="row g-4">
      <!-- Advertisement-style Container -->
      <div class="col-md-4">
        <div class="product-box text-start ad-container">
          <h3 class="ad-title">Dainese Pro Gear</h3>
          <p class="ad-description">Dainese's Pro-Armor gear offers a compelling combination of safety, comfort, and versatility...</p>
          <a href="Shop.php" class="btn btn-warning ad-cta">Shop Now</a>
        </div>
      </div>
  
      <!-- Product Container 1 - Modified to be non-clickable -->
      <div class="col-md-4 text-center">
        <div class="product-box">
          <div class="product-image-container">
            <img src="Assets/Product/Dainese_Carbon_3_Short_Gloves.png" alt="Helmet Product" class="product-image">
          </div>
          <h3>₱4,999.00</h3>
          <div class="star-rating mt-2 d-flex justify-content-center align-items-center gap-2">
            <div>
              <i class="fas fa-star text-primary"></i>
              <i class="fas fa-star text-primary"></i>
              <i class="fas fa-star text-primary"></i>
              <i class="fas fa-star text-primary"></i>
              <i class="far fa-star text-primary"></i>
            </div>
            <span class="rating-count text-muted">(134)</span>
          </div>
        </div>
      </div>
  
      <!-- Product Container 2 - Modified to be non-clickable -->
      <div class="col-md-4 text-center">
        <div class="product-box">
          <div class="product-image-container">
            <img src="Assets/Product/dainese-super-speed-leather-trousers-black-white.png" alt="Another Product" class="product-image">
          </div>
          <h3>₱3,499.00</h3>
          <div class="star-rating mt-2 d-flex justify-content-center align-items-center gap-2">
            <div>
              <i class="fas fa-star text-primary"></i>
              <i class="fas fa-star text-primary"></i>
              <i class="fas fa-star text-primary"></i>
              <i class="far fa-star text-primary"></i>
              <i class="far fa-star text-primary"></i>
            </div>
            <span class="rating-count text-muted">(87)</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <section class="news-section my-4">
    <div class="container">
      <div class="section-header d-flex justify-content-between align-items-center mb-3">
        <div class="section-title">
          <h3 class="news-heading m-0">News</h3>
        </div>
        <a href="News.php" class="view-all-link">View all</a>
      </div>
      
      <div class="row">
        <!-- News Item 1 -->
        <div class="col-md-4 mb-4">
          <div class="news-card">
            <a href="#" class="text-decoration-none text-dark">
              <div class="news-img-container mb-3">
                <img src="Assets/News/future.jpg" alt="News 1" class="img-fluid w-100">
              </div>
              <h5 class="news-title text-uppercase fw-bold">RIDER SAFETY GEAR TECHNOLOGY SHOWCASE</h5>
              <p class="news-excerpt">Experience firsthand the latest rider safety gear technology with premium quality products and innovative features.</p>
              <p class="news-date small text-muted">April 4, 2024</p>
            </a>
          </div>
        </div>
        
        <!-- News Item 2 -->
        <div class="col-md-4 mb-4">
          <div class="news-card">
            <a href="#" class="text-decoration-none text-dark">
              <div class="news-img-container mb-3">
                <img src="Assets/News/ls2 news.jpg" alt="News 2" class="img-fluid w-100">
              </div>
              <h5 class="news-title text-uppercase fw-bold">RIDE SMART, LEVEL UP WITH LS2</h5>
              <p class="news-excerpt">Built on a foundation of safety, innovation, and style - LS2's Brand Pillars define quality motorcycle helmets and accessories.</p>
              <p class="news-date small text-muted">November 14, 2023</p>
            </a>
          </div>
        </div>
        
        <!-- News Item 3 -->
        <div class="col-md-4 mb-4">
          <div class="news-card">
            <a href="#" class="text-decoration-none text-dark">
              <div class="news-img-container mb-3">
                <img src="Assets/News/shop.jpg" alt="News 3" class="img-fluid w-100">
              </div>
              <h5 class="news-title text-uppercase fw-bold">ST MOTO CEBU GRAND UPGRADE</h5>
              <p class="news-excerpt">Rev up your riding experience. We've moved to a bigger and better store location, a leading concept store for all your motorcycle needs.</p>
              <p class="news-date small text-muted">October 27, 2023</p>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- Floating Chat Button (Optional) -->
  <div class="floating-chat">
    <button class="btn btn-danger rounded-circle p-3">
      <i class="bi bi-chat-fill"></i>
    </button>
  </div>


  <div class="brief-description">
    <h3 class="text-center">ST Moto & Gear</h3>
    <p class="text-center">Your one-stop shop for all motorcycle gear and accessories. We offer a wide range of products from helmets to apparel, ensuring safety and style on the road.</p>
    <p class="text-center">Explore our latest arrivals and exclusive offers to gear up for your next ride!</p>
  </div>
  <!-- Add sections for Featured Products, Best Sellers, Footer as before -->

  <!-- Leaflet MAP Part-->
  <div class="info-container">
    <div class="info-box">
        <strong>Region:</strong> <span id="region">NCR</span>
    </div>
    <div class="info-box">
        <strong>Stores:</strong> <span id="store">BGC</span>
    </div>
</div>

<div class="map-container">
  <div id="map"></div>
</div>

<div class="back-to-top">
    <a href="#top">Back to top</a>
</div>


<div id="footer"></div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
  crossorigin=""></script>
<script src="map.js"></script>
<script>
  // Load the navbar
  fetch('./includes/navbar.php')
    .then(res => res.text())
    .then(data => document.getElementById('navbar').innerHTML = data);

  // Load the footer
  fetch('./includes/footer.php')
    .then(res => res.text())
    .then(data => document.getElementById('footer').innerHTML = data);
</script>
</body>
</html>