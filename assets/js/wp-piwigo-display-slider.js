(function () {
    'use strict';

    function setActiveThumbnail(thumbnails, index) {
        thumbnails.forEach(function (thumbnail, thumbnailIndex) {
            var active = thumbnailIndex === index;
            thumbnail.classList.toggle('is-active', active);
            thumbnail.setAttribute('aria-current', active ? 'true' : 'false');
        });
    }

    function bindNativeFallback(slider) {
        if (slider.dataset.wpdFallbackBound === 'true') {
            return;
        }

        var slides = Array.prototype.slice.call(slider.querySelectorAll('.splide__slide'));
        var thumbnails = Array.prototype.slice.call(slider.querySelectorAll('.wp-piwigo-display-slider-thumbnail'));

        if (!slides.length || !thumbnails.length) {
            return;
        }

        slider.dataset.wpdFallbackBound = 'true';

        function showSlide(index) {
            slides.forEach(function (slide, slideIndex) {
                var active = slideIndex === index;
                slide.hidden = !active;
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
            setActiveThumbnail(thumbnails, index);
        }

        thumbnails.forEach(function (thumbnail) {
            thumbnail.addEventListener('click', function (event) {
                if (slider.dataset.wpdSliderInitialized === 'true') {
                    return;
                }

                event.preventDefault();
                var index = parseInt(thumbnail.dataset.slideIndex || '0', 10);
                if (!Number.isNaN(index) && slides[index]) {
                    showSlide(index);
                }
            });
        });

        showSlide(0);
    }

    function initSplideSlider(slider) {
        if (slider.dataset.wpdSliderInitialized === 'true' || typeof Splide === 'undefined') {
            return false;
        }

        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var autoplay = !reducedMotion && slider.dataset.autoplay === 'true';
        var interval = parseInt(slider.dataset.interval || '5000', 10);
        var configuredSpeed = parseInt(slider.dataset.speed || '500', 10);
        var navigation = slider.dataset.navigation || 'thumbnails';
        var transition = ['slide', 'fade', 'none'].indexOf(slider.dataset.transition) !== -1 ? slider.dataset.transition : 'slide';
        var direction = slider.dataset.direction === 'rtl' ? 'rtl' : 'ltr';
        var thumbnails = Array.prototype.slice.call(slider.querySelectorAll('.wp-piwigo-display-slider-thumbnail'));
        var slides = Array.prototype.slice.call(slider.querySelectorAll('.splide__slide'));
        var isFade = !reducedMotion && transition === 'fade';
        var speed = reducedMotion || transition === 'none' ? 0 : configuredSpeed;

        slides.forEach(function (slide) {
            slide.hidden = false;
            slide.removeAttribute('aria-hidden');
        });

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

        splide.on('mounted moved', function (newIndex) {
            setActiveThumbnail(thumbnails, typeof newIndex === 'number' ? newIndex : splide.index);
        });

        thumbnails.forEach(function (thumbnail) {
            thumbnail.addEventListener('click', function (event) {
                event.preventDefault();
                var index = parseInt(thumbnail.dataset.slideIndex || '0', 10);
                if (!Number.isNaN(index)) {
                    splide.go(index);
                }
            });
        });

        slider.dataset.wpdSliderInitialized = 'true';
        splide.mount();
        return true;
    }

    function initSliders() {
        var sliders = Array.prototype.slice.call(document.querySelectorAll('.wp-piwigo-display-slider.splide'));

        sliders.forEach(function (slider) {
            bindNativeFallback(slider);
            initSplideSlider(slider);
        });

        if (typeof Splide === 'undefined' && sliders.length) {
            var attempts = 0;
            var timer = window.setInterval(function () {
                attempts += 1;
                if (typeof Splide !== 'undefined') {
                    window.clearInterval(timer);
                    sliders.forEach(initSplideSlider);
                } else if (attempts >= 20) {
                    window.clearInterval(timer);
                }
            }, 250);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSliders, { once: true });
    } else {
        initSliders();
    }
}());
