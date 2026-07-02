document.addEventListener('DOMContentLoaded', function () {
    if (typeof Splide === 'undefined') {
        return;
    }

    document.querySelectorAll('.wp-piwigo-display-slider.splide').forEach(function (slider) {
        var autoplay = slider.dataset.autoplay === 'true';
        var interval = parseInt(slider.dataset.interval || '5000', 10);

        new Splide(slider, {
            type: 'loop',
            perPage: 1,
            autoplay: autoplay,
            interval: interval,
            pauseOnHover: true,
            pauseOnFocus: true,
            arrows: true,
            pagination: true,
            keyboard: true,
            lazyLoad: false
        }).mount();
    });
});
