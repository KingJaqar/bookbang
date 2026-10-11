// The hosted catalogue is available before the account and ordering service launches.
document.addEventListener('click', (event) => {
  const button = event.target.closest('button');
  if (!button) return;
  const requiresAccount = button.matches('.seeMore, .add-to-cart-btn, .buy-now-btn, .submit-review-btn')
    || button.textContent.trim() === 'Create Account';
  if (requiresAccount) {
    event.preventDefault();
    window.location.assign('/unavailable.html');
  }
});
