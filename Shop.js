// Import products data
import products from './products.js';


document.addEventListener('DOMContentLoaded', function() {
    // Initialize the product grid
    initializeProductGrid();
    
    // Set up filter listeners
    setupFilters();
});

function initializeProductGrid() {
    const productsGrid = document.getElementById('products-grid');
    productsGrid.innerHTML = ''; // Clear existing products
    
    // Convert products object to array for easier filtering/sorting
    const productsArray = Object.values(products);
    
    // Get current filter values
    const categoryFilter = document.getElementById('category-filter').value;
    const brandFilter = document.getElementById('brand-filter').value;
    const sortBy = document.getElementById('sort-by').value;
    
    // Apply filters
    let filteredProducts = productsArray;
    
    // Filter by category
    if (categoryFilter !== 'all') {
        filteredProducts = filteredProducts.filter(product => product.category === categoryFilter);
    }
    
    // Filter by brand
    if (brandFilter !== 'all') {
        filteredProducts = filteredProducts.filter(product => product.brand === brandFilter);
    }
    
    // Apply sorting
    switch (sortBy) {
        case 'price-low':
            filteredProducts.sort((a, b) => a.currentPrice - b.currentPrice);
            break;
        case 'price-high':
            filteredProducts.sort((a, b) => b.currentPrice - a.currentPrice);
            break;
        case 'name-asc':
            filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'name-desc':
            filteredProducts.sort((a, b) => b.name.localeCompare(a.name));
            break;
        default:
            // 'featured' or any other default sorting
            // We'll keep the original order
            break;
    }
    
    // Render filtered products
    filteredProducts.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
    
    // Show message if no products found
    if (filteredProducts.length === 0) {
        productsGrid.innerHTML = `
            <div class="no-products-message">
                <h3>No products found</h3>
                <p>Try changing your filter settings</p>
            </div>
        `;
    }
}

function createProductCard(product) {
    const productCard = document.createElement('div');
    productCard.className = 'product-card';
    
    // Create product image container
    const imageContainer = document.createElement('div');
    imageContainer.className = 'product-image';
    
    // Create product image
    const productImage = document.createElement('img');
    productImage.src = product.mainImage;
    productImage.alt = product.name;
    imageContainer.appendChild(productImage);
    
    // Add discount badge if applicable
    if (product.discount) {
        const discountBadge = document.createElement('div');
        discountBadge.className = 'discount-badge';
        discountBadge.textContent = `-${product.discount}%`;
        imageContainer.appendChild(discountBadge);
    }
    
    // Create product details section
    const productDetails = document.createElement('div');
    productDetails.className = 'product-details';
    
    // Create product name
    const productName = document.createElement('h3');
    productName.className = 'product-name';
    productName.textContent = product.name;
    productDetails.appendChild(productName);
    
    // Create rating stars
    const ratingContainer = document.createElement('div');
    ratingContainer.className = 'rating';
    
    const starsContainer = document.createElement('div');
    starsContainer.className = 'stars';
    
    for (let i = 1; i <= 5; i++) {
        const starIcon = document.createElement('i');
        if (i <= product.reviews.averageRating) {
            starIcon.className = 'fas fa-star'; // Filled star
        } else if (i - 0.5 <= product.reviews.averageRating) {
            starIcon.className = 'fas fa-star-half-alt'; // Half star
        } else {
            starIcon.className = 'far fa-star'; // Empty star
        }
        starsContainer.appendChild(starIcon);
    }
    ratingContainer.appendChild(starsContainer);
    
    const reviewCount = document.createElement('span');
    reviewCount.className = 'review-count';
    reviewCount.textContent = `(${product.reviews.count})`;
    ratingContainer.appendChild(reviewCount);
    
    productDetails.appendChild(ratingContainer);
    
    // Create price container
    const priceContainer = document.createElement('div');
    priceContainer.className = 'price-container';
    
    const currentPrice = document.createElement('span');
    currentPrice.className = 'current-price';
    currentPrice.textContent = `₱${product.currentPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    priceContainer.appendChild(currentPrice);
    
    if (product.discount) {
        const originalPrice = document.createElement('span');
        originalPrice.className = 'original-price';
        originalPrice.textContent = `₱${product.originalPrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        priceContainer.appendChild(originalPrice);
    }
    
    productDetails.appendChild(priceContainer);
    
    // Create view details button
    const viewDetailsButton = document.createElement('a');
    viewDetailsButton.className = 'view-details-btn';
    viewDetailsButton.textContent = 'View Details';
    viewDetailsButton.href = `productPage.html?id=${product.id}`;
    productDetails.appendChild(viewDetailsButton);
    
    // Assemble the product card
    productCard.appendChild(imageContainer);
    productCard.appendChild(productDetails);
    
    // Add click handler for the whole card
    productCard.addEventListener('click', (e) => {
        // Prevent triggering if clicking on the button
        if (e.target !== viewDetailsButton) {
            window.location.href = `productPage.html?id=${product.id}`;
        }
    });
    
    return productCard;
}

function setupFilters() {
    const categoryFilter = document.getElementById('category-filter');
    const brandFilter = document.getElementById('brand-filter');
    const sortBy = document.getElementById('sort-by');
    
    categoryFilter.addEventListener('change', initializeProductGrid);
    brandFilter.addEventListener('change', initializeProductGrid);
    sortBy.addEventListener('change', initializeProductGrid);
}