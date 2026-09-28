// Bookbang/Homepage.js

document.addEventListener("DOMContentLoaded", function() {
  const slides = document.querySelector(".announcement-slides");
  const slideItems = document.querySelectorAll(".announcement-slide");
  const prevArrow = document.querySelector(".arrow-prev");
  const nextArrow = document.querySelector(".arrow-next");

  let currentIndex = 0;
  const totalSlides = slideItems.length;

  function updateSlidePosition() {
    slides.style.transform = `translateX(-${currentIndex * 100}%)`;
  }

  function showNextSlide() {
    currentIndex = (currentIndex + 1) % totalSlides;
    updateSlidePosition();
  }

  function showPrevSlide() {
    currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
    updateSlidePosition();
  }

  nextArrow.addEventListener("click", showNextSlide);
  prevArrow.addEventListener("click", showPrevSlide);

  setInterval(showNextSlide, 4000);

  // Hide nav on scroll down, show on scroll up
  let lastScrollTop = 0;
  const nav = document.querySelector("main-nav");

  window.addEventListener("scroll", function() {
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    if (scrollTop > lastScrollTop) {
      nav.classList.add("nav-hidden"); // Add class to hide nav
    } else {
      nav.classList.remove("nav-hidden"); // Remove class to show nav
    }
    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // Prevent negative scrolling value
  });
});
