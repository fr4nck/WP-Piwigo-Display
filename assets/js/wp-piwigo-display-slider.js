document.addEventListener('DOMContentLoaded', function () {
    initSplideSliders();
});

function initSplideSliders() {
    if (typeof Splide === 'undefined') {
        return;
    }

    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('.wp-piwigo-display-slider.splide').forEach(function (slider) {
        var autoplay = !reducedMotion && slider.dataset.autoplay === 'true';
        var interval = parseInt(slider.dataset.interval || '5000', 10);
        var configuredSpeed = parseInt(slider.dataset.speed || '500', 10);
        var navigation = slider.dataset.navigation || 'thumbnails';
        var transition = ['slide', 'fade', 'none'].indexOf(slider.dataset.transition) !== -1 ? slider.dataset.transition : 'slide';
        var direction = slider.dataset.direction === 'rtl' ? 'rtl' : 'ltr';
        var thumbnails = Array.prototype.slice.call(slider.querySelectorAll('.wp-piwigo-display-slider-thumbnail'));
        var isFade = !reducedMotion && transition === 'fade';
        var speed = reducedMotion || transition === 'none' ? 0 : configuredSpeed;

        var splide = new Splide(slider, {
            type: isFade ? 'fade' : 'loop',
            direction: direction,
            perPage: 1,
            autoplay: autoplay,
            interval: interval,
            speed: speed,
            rewind: isFade,
            pauseOnHover: true,
            pauseOnFocus: true,
            arrows: true,
            pagination: navigation === 'dots',
            keyboard: true,
            drag: !isFade
        });

        splide.on('move', function (newIndex) {
            thumbnails.forEach(function (thumbnail, index) {
                thumbnail.classList.toggle('is-active', index === newIndex);
            });
        });

        thumbnails.forEach(function (thumbnail) {
            thumbnail.addEventListener('click', function () {
                var index = parseInt(thumbnail.dataset.slideIndex || '0', 10);
                splide.go(index);
            });
        });

        splide.mount();
    });
}
