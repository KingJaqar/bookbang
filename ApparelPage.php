<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ST Moto & Gear - Rider Apparel</title>
    <link rel="stylesheet" href="ApparelPage.css">
    <link rel="stylesheet" href="bookbang-theme.css">
    <link rel="stylesheet" href="./includes/footer.css">
    <link rel="stylesheet" href="./includes/navbar.css">
</head>
<body>
    <div id="navbar"></div>
    
    <section class="hero">
        <div class="hero-content">
            <h2>Premium Motorcycle Apparel</h2>
            <p>Discover high-quality riding gear designed for safety, comfort, and style on every journey.</p>
        </div>
    </section>
    
    <section class="apparel-intro-section">
        <div class="apparel-intro-content">
            <h2>The Right Gear for Every Ride</h2>
            <p>At ST Moto & Gear, we offer premium motorcycle apparel that combines advanced protection with comfort and style. Our collection features riding gear that meets the highest safety standards while providing the functionality and aesthetics that riders demand.</p>
            <p>We understand that choosing the right apparel is essential for your safety and comfort on the road or track. That's why we offer a diverse selection of top-tier motorcycle clothing across various categories, each designed for specific riding conditions and styles.</p>
            <p>Whether you're looking for track-ready leathers, all-weather touring gear, or casual riding shirts and jeans, our premium selection delivers the quality, protection, and style you deserve for every journey on two wheels.</p>
        </div>
    </section>
    
    <section class="apparel-categories-section">
        <h2>Featured Apparel Categories</h2>
        <div class="apparel-grid">
            <div class="apparel-card">
                <img src="./Assets/Apparel/leather.jpg" alt="Leather Jackets">
                <div class="apparel-info">
                    <h3>Leather Jackets</h3>
                    <p>Premium leather jackets crafted from top-grain cowhide and kangaroo leather, designed to provide maximum abrasion resistance and impact protection. Available with advanced armor systems and aerodynamic sport profiles or classic styling.</p>
                    <div class="apparel-features">Features: CE-Approved Armor, Perforation, Stretch Panels</div>
                </div>
            </div>
            
            <div class="apparel-card">
                <img src="./Assets/Apparel/textile.jpg" alt="Textile Jackets">
                <div class="apparel-info">
                    <h3>Textile Jackets</h3>
                    <p>Versatile textile jackets constructed with advanced materials like Cordura, Ballistic nylon, and Gore-Tex. Designed for all-weather protection with waterproof membranes, ventilation systems, and removable thermal liners for year-round riding.</p>
                    <div class="apparel-features">Features: Waterproofing, Climate Control, Impact Protection</div>
                </div>
            </div>
            
            <div class="apparel-card">
                <img src="./Assets/Apparel/pantss.jpg" alt="Riding Pants">
                <div class="apparel-info">
                    <h3>Riding Pants</h3>
                    <p>Purpose-built motorcycle pants ranging from full leather race pants to armored riding jeans and textile touring options. Engineered with reinforced impact zones, advanced armor, and ergonomic design for comfort in the riding position.</p>
                    <div class="apparel-features">Features: Knee Sliders, Adjustable Armor, Connectivity Systems</div>
                </div>
            </div>
            
            <div class="apparel-card">
                <img src="./Assets/Apparel/jersey.jpg" alt="Riding Jerseys">
                <div class="apparel-info">
                    <h3>Riding Jerseys</h3>
                    <p>Technical off-road jerseys designed for motocross and adventure riding. Constructed with moisture-wicking fabrics, extended rear profiles, and strategic ventilation to keep riders cool and comfortable during intense riding sessions.</p>
                    <div class="apparel-features">Features: Breathable Fabrics, Padded Elbows, Anti-Microbial</div>
                </div>
            </div>
            
            <div class="apparel-card">
                <img src="./Assets/Apparel/suits.jpeg" alt="Riding Suits">
                <div class="apparel-info">
                    <h3>Riding Suits</h3>
                    <p>One and two-piece racing suits engineered for maximum protection at high speeds. Featuring premium leather construction, aerodynamic speed humps, pre-curved design, and extensive impact protection for track and aggressive street riding.</p>
                    <div class="apparel-features">Features: Airbag Compatible, Kangaroo Leather, Race Profile</div>
                </div>
            </div>
            
            <div class="apparel-card">
                <img src="./Assets/Apparel/layers.jpg" alt="Protective Layers">
                <div class="apparel-info">
                    <h3>Protective Layers</h3>
                    <p>Technical base layers and armored shirts designed to enhance protection and comfort. From compression garments with integrated cooling technology to armored shirts with removable protectors for versatile impact protection.</p>
                    <div class="apparel-features">Features: Back Protectors, Chest Guards, Cooling Technology</div>
                </div>
            </div>
        </div>
        
        <div class="shop-btn-container">
            <a href="./Shop.php" class="shop-btn">SHOP ALL APPAREL</a>
        </div>
    </section>

    <section class="apparel-benefits">
        <h2>Why Quality Riding Apparel Matters</h2>
        <div class="benefits-grid">
            <div class="benefit-item">
                <div class="benefit-icon">
                    <img src="./Assets/Apparel/shielder.png" alt="Protection Icon">
                </div>
                <h3>Superior Protection</h3>
                <p>Our premium riding apparel is engineered with advanced impact protection systems, abrasion-resistant materials, and strategic reinforcement to help keep you safe in all riding conditions.</p>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <img src="./Assets/Apparel/comfort.png" alt="Comfort Icon">
                </div>
                <h3>All-Day Comfort</h3>
                <p>Ergonomically designed specifically for the riding position, our apparel features stretch panels, pre-curved construction, and precise fit systems to ensure comfort during long rides.</p>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <img src="./Assets/Apparel/sunny.png" alt="Weather Icon">
                </div>
                <h3>Weather Adaptation</h3>
                <p>From waterproof membranes to advanced ventilation systems and removable thermal liners, our technical apparel keeps you comfortable in changing weather conditions.</p>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <img src="./Assets/Apparel/zippy.png" alt="Style Icon">
                </div>
                <h3>Distinctive Style</h3>
                <p>Our riding apparel combines technical performance with thoughtful design and distinctive aesthetics, ensuring you look as good as you feel on and off the bike.</p>
            </div>
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