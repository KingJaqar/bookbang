<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ST Moto & Gear - Premium Brands</title>
    <link rel="stylesheet" href="BrandsPage.css">
    <link rel="stylesheet" href="bookbang-theme.css">
    <link rel="stylesheet" href="./includes/footer.css">
    <link rel="stylesheet" href="./includes/navbar.css">
</head>
<body>
    <div id="navbar"></div>
    
    <section class="hero">
        <div class="hero-content">
            <h2>Premium Motorcycle Gear Brands</h2>
            <p>Discover world-class motorcycle gear from the industry's most trusted and innovative brands.</p>
        </div>
    </section>
    
    <section class="brands-intro-section">
        <div class="brands-intro-content">
            <h2>The Best Brands for Every Rider</h2>
            <p>At ST Moto & Gear, we carefully select only the finest motorcycle brands that combine cutting-edge technology, exceptional safety standards, and timeless style. Our collection features established industry leaders who have earned their reputation through decades of innovation and commitment to rider protection.</p>
            <p>We understand that choosing the right gear is a personal decision that depends on your riding style, preferences, and needs. That's why we offer a diverse selection of top-tier brands, each with their own unique approach to design, functionality, and aesthetics.</p>
            <p>Whether you're a track-day enthusiast, an adventure rider, or an urban commuter, our premium brands deliver the quality, performance, and reliability you deserve for every journey on two wheels.</p>
        </div>
    </section>
    
    <section class="featured-brands">
        <h2>Featured Brands</h2>
        <div class="brand-grid">
            <div class="brand-card">
                <img src="./Assets/BrandLogo/dainese.png" alt="Dainese Logo">
                <div class="brand-info">
                    <h3>Dainese</h3>
                    <p>Italian excellence in protective motorcycle gear since 1972. Dainese pioneered innovative safety technologies including the back protector and D-air® airbag systems, setting new standards in rider protection.</p>
                    <div class="brand-specialty">Specialty: Racing Suits, Leather Jackets, Airbag Technology</div>
                </div>
            </div>
            
            <div class="brand-card">
                <img src="./Assets/BrandLogo/alpinestars.png" alt="Alpinestars Logo">
                <div class="brand-info">
                    <h3>Alpinestars</h3>
                    <p>Founded in 1963, Alpinestars has evolved from a boot manufacturer to a comprehensive technical apparel company with a rich racing heritage and cutting-edge protective gear for motorcyclists.</p>
                    <div class="brand-specialty">Specialty: Racing Gear, Boots, Technical Apparel</div>
                </div>
            </div>
            
            <div class="brand-card">
                <img src="./Assets/BrandLogo/shoei.png" alt="Shoei Logo">
                <div class="brand-info">
                    <h3>Shoei</h3>
                    <p>Japanese precision and craftsmanship define Shoei helmets, handmade since 1959. Known for premium quality construction, advanced aerodynamics, and superior comfort for serious riders.</p>
                    <div class="brand-specialty">Specialty: Premium Helmets, Advanced Safety Features</div>
                </div>
            </div>
            
            <div class="brand-card">
                <img src="./Assets/BrandLogo/foxra.png" alt="Fox Racing Logo">
                <div class="brand-info">
                    <h3>Fox Racing</h3>
                    <p>Born in 1974, Fox Racing started as a small distribution business and grew into an iconic brand synonymous with motocross and off-road riding culture, combining performance with distinctive style.</p>
                    <div class="brand-specialty">Specialty: Off-Road Gear, Motocross Equipment, Performance Apparel</div>
                </div>
            </div>
            
            <div class="brand-card">
                <img src="./Assets/BrandLogo/agv.png" alt="AGV Logo">
                <div class="brand-info">
                    <h3>AGV</h3>
                    <p>Italian helmet manufacturer founded in 1947 with deep racing heritage. AGV combines innovative technology with iconic design, protecting champions like Valentino Rossi throughout their illustrious careers.</p>
                    <div class="brand-specialty">Specialty: Racing Helmets, Sport Helmets, Visors</div>
                </div>
            </div>
            
            <div class="brand-card">
                <img src="./Assets/BrandLogo/revit.png" alt="Rev'it Logo">
                <div class="brand-info">
                    <h3>Rev'it</h3>
                    <p>Dutch design meets advanced functionality. Since 1995, Rev'it has created innovative motorcycle apparel that seamlessly blends protection, comfort, and sophisticated European styling for the modern rider.</p>
                    <div class="brand-specialty">Specialty: Adventure Gear, Urban Apparel, All-Weather Equipment</div>
                </div>
            </div>
        </div>
        
        <div class="shop-btn-container">
            <a href="Shop.php" class="shop-btn">SHOP ALL BRANDS</a>
        </div>
    </section>

    <section class="brand-partners">
        <h2>Our Brand Partners</h2>
        <div class="brand-logos">
            <div class="brand-logo"><img src="./Assets/BrandLogo/dainese.png" alt="Dainese"></div>
            <div class="brand-logo"><img src="./Assets/BrandLogo/alphinestars.png" alt="Alpinestars"></div>
            <div class="brand-logo"><img src="./Assets/BrandLogo/shoei.png" alt="Shoei"></div>
            <div class="brand-logo"><img src="./Assets/BrandLogo/foxra.png" alt="Fox Racing"></div>
            <div class="brand-logo"><img src="./Assets/BrandLogo/agv.png" alt="AGV"></div>
            <div class="brand-logo"><img src="./Assets/BrandLogo/revit.png" alt="Rev'it"></div>
        </div>
    </section>

    <div id="footer"></div>
  
    <!-- JAVASCRIPT -->
    <script src="Shop.js"></script>
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