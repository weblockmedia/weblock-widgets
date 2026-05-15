(function () {
    'use strict';

    function initCarousel(carousel) {
        var track = carousel.querySelector('.wlw-carousel__track');
        var prev  = carousel.querySelector('.wlw-carousel__nav--prev');
        var next  = carousel.querySelector('.wlw-carousel__nav--next');
        if (!track) return;

        function scrollAmount() {
            var slide = track.querySelector('.wlw-carousel__slide');
            if (!slide) return track.clientWidth;
            return slide.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || 16);
        }

        function update() {
            if (!prev || !next) return;
            prev.disabled = track.scrollLeft <= 1;
            next.disabled = (track.scrollLeft + track.clientWidth) >= (track.scrollWidth - 1);
        }

        if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' }); });
        if (next) next.addEventListener('click', function () { track.scrollBy({ left:  scrollAmount(), behavior: 'smooth' }); });

        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.wlw-carousel').forEach(initCarousel);
    });
})();
