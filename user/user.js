// Bookbang/user/user.js


// user.js - small utilities for user pages (updates navbar after fetch)
document.addEventListener('DOMContentLoaded', function () {
  // When the page has included navbar.php via server-side include, we still try to patch it.
  // If navbar is loaded via fetch (for .html fallback), the inline script in user.html handles it.
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
  }).catch(()=>{/*silently ignore*/});
});
