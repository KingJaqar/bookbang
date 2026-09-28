// Bookbang/cart/cart.js


// cart.js - small UI improvements for cart page (keeps behavior minimal)
document.addEventListener('DOMContentLoaded', function () {
  // Update navbar according to auth status (for fetch-based navbar)
  fetch('auth_status.php').then(r => r.json()).then(data => {
    const right = document.querySelector('#main-nav .navbar-right');
    if (!right) return;
    if (data.logged_in) {
      right.innerHTML = `<a href="Homepage.php">Home</a>
                         <a href="ProductPage.php">MyBooks</a>
                         <a href="cart.php">Cart</a>
                         <div class="user-chip"><img src="assets/bookbanglogo.png" alt="profile" class="mini"/><span>${data.username}</span></div>
                         <a href="logout.php">Logout</a>`;
    } else {
      right.innerHTML = `<a href="Homepage.php">Home</a>
                         <a href="ProductPage.php">MyBooks</a>
                         <a href="cart.php">Cart</a>
                         <a href="login.php">SignIn</a>`;
    }
  }).catch(()=>{/*ignore errors*/});
});
