(function () {
  window.menutoggle = window.menutoggle || function () {
    const menu = document.getElementById("MenuItems");
    const button = document.querySelector(".menu-icon");
    if (!menu) return;

    const isOpen = menu.classList.toggle("show");
    if (button) {
      button.setAttribute("aria-expanded", isOpen ? "true" : "false");
    }
  };

  document.addEventListener("DOMContentLoaded", function () {
    const carousel = document.querySelector("[data-home-carousel]");
    if (!carousel) return;

    const slides = Array.from(carousel.querySelectorAll(".home-carousel__slide"));
    const indicators = Array.from(carousel.querySelectorAll("[data-carousel-index]"));
    const prev = carousel.querySelector("[data-carousel-prev]");
    const next = carousel.querySelector("[data-carousel-next]");
    let active = Math.max(0, slides.findIndex((slide) => slide.classList.contains("is-active")));
    let timer = null;

    function showSlide(index) {
      if (!slides.length) return;
      active = (index + slides.length) % slides.length;

      slides.forEach((slide, slideIndex) => {
        const selected = slideIndex === active;
        slide.classList.toggle("is-active", selected);
        slide.setAttribute("aria-hidden", selected ? "false" : "true");
      });

      indicators.forEach((indicator, indicatorIndex) => {
        const selected = indicatorIndex === active;
        indicator.classList.toggle("is-active", selected);
        indicator.setAttribute("aria-current", selected ? "true" : "false");
      });
    }

    function restartAutoplay() {
      if (timer) window.clearInterval(timer);
      timer = window.setInterval(() => showSlide(active + 1), 6500);
    }

    if (prev) {
      prev.addEventListener("click", () => {
        showSlide(active - 1);
        restartAutoplay();
      });
    }

    if (next) {
      next.addEventListener("click", () => {
        showSlide(active + 1);
        restartAutoplay();
      });
    }

    indicators.forEach((indicator) => {
      indicator.addEventListener("click", () => {
        showSlide(Number(indicator.dataset.carouselIndex || 0));
        restartAutoplay();
      });
    });

    carousel.addEventListener("mouseenter", () => {
      if (timer) window.clearInterval(timer);
    });

    carousel.addEventListener("mouseleave", restartAutoplay);

    showSlide(active);
    restartAutoplay();
  });
})();
