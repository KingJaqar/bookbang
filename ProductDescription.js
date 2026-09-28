// Bookbang/ProductDescription.js

// JavaScript for Product Page Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    const decreaseBtn = document.querySelector('.decrease');
    const increaseBtn = document.querySelector('.increase');
    const quantityInput = document.querySelector('.quantity-input');

    decreaseBtn.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });

    increaseBtn.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value);
        quantityInput.value = currentValue + 1;
    });

    // Color selection
    const colorOptions = document.querySelectorAll('.color-option');
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            colorOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // Size selection
    const sizeOptions = document.querySelectorAll('.size-option:not(.unavailable)');
    sizeOptions.forEach(option => {
        option.addEventListener('click', function() {
            sizeOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    // Carousel controls
     const addToCartBtn = document.querySelector('.add-to-cart-btn');
    const buyNowBtn   = document.querySelector('.buy-now-btn');
    const notification = document.getElementById('cart-notification');

    function showNotification(msg) {
        if (notification) {
            notification.innerText = msg;
            notification.style.display = 'block';
            setTimeout(() => notification.style.display = 'none', 3000);
        }
    }

    function addToCart(bookId) {
         return fetch('add_to_cart.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'book_id=' + encodeURIComponent(bookId)
}).then(res => res.json());

    }

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const bookTitle = document.querySelector('.product-title').innerText;

            addToCart(bookId).then(data => {
                if (data.success) {
                    showNotification(`"${bookTitle}" added to cart!`);
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
    fetch('checkout/buy_now.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'book_id=' + encodeURIComponent(bookId)
    }).then(res => res.json())
      .then(data => {
          if(data.success){
                  window.location.href = 'checkout/checkout.php';
          } else {
              alert('Failed to initiate Buy Now');
          }
      });
});
}

});


