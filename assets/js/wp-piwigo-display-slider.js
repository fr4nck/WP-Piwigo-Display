document.addEventListener('DOMContentLoaded', function () {
    initSplideSliders();
});

function initSplideSliders() {
    if (typeof Splide === 'undefined') {
        return;
    }

    document.querySelectorAll('.wp-piwigo-display-slider.splide').forEach(function (slider) {
        var autoplay = slider.dataset.autoplay === 'true';
        var interval = parseInt(slider.dataset.interval || '5000', 10);
        var speed = parseInt(slider.dataset.speed || '500', 10);
        var navigation = slider.dataset.navigation || 'thumbnails';
        var thumbnails = Array.prototype.slice.call(slider.querySelectorAll('.wp-piwigo-display-slider-thumbnail'));

        var splide = new Splide(slider, {
            type: 'loop',
            perPage: 1,
            autoplay: autoplay,
            interval: interval,
            speed: speed,
            pauseOnHover: true,
            pauseOnFocus: true,
            arrows: true,
            pagination: navigation === 'dots',
            keyboard: true,
            drag: true,
            rewind: false
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
