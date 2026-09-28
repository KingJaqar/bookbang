document.addEventListener("DOMContentLoaded", function () {
  const carousel = document.querySelector(".carousel");
  const listHTML = document.querySelector(".carousel .list");
  const nextButton = document.getElementById("next");
  const prevButton = document.getElementById("prev");

  // Carousel navigation (Next / Prev)
  let unAcceptClick;

  function showSlider(type) {
    nextButton.style.pointerEvents = "none";
    prevButton.style.pointerEvents = "none";

    carousel.classList.remove("next", "prev");

    const items = document.querySelectorAll(".carousel .list .item");
    if (type === "next") {
      listHTML.appendChild(items[0]);
      carousel.classList.add("next");
    } else {
      listHTML.prepend(items[items.length - 1]);
      carousel.classList.add("prev");
    }

    clearTimeout(unAcceptClick);
    unAcceptClick = setTimeout(() => {
      nextButton.style.pointerEvents = "auto";
      prevButton.style.pointerEvents = "auto";
    }, 2000);
  }

  nextButton.onclick = () => showSlider("next");
  prevButton.onclick = () => showSlider("prev");
});
