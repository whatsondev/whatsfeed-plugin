document.addEventListener("DOMContentLoaded", function() {
    if (document.querySelector(".whatsfeed-carousel .swiper")) {
        new Swiper(".whatsfeed-carousel .swiper", {
            slidesPerView: 3,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                480: { slidesPerView: 1 }
            }
        });
    }
});
