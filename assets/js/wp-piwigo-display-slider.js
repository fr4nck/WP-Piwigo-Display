(function () {
    'use strict';

    var i18n = window.WPDSliderI18n || {};

    function label(key, fallback) {
        return typeof i18n[key] === 'string' && i18n[key] ? i18n[key] : fallback;
    }

    function numberedLabel(template, number) {
        return String(template).replace('%d', String(number));
    }

    function setActiveThumbnail(thumbnails, index) {
        thumbnails.forEach(function (thumbnail, thumbnailIndex) {
            var active = thumbnailIndex === index;
            thumbnail.classList.toggle('is-active', active);
            thumbnail.setAttribute('aria-current', active ? 'true' : 'false');
        });
    }

    function removeNativeFallbackControls(slider) {
        slider.querySelectorAll('.wpd-native-slider-controls, .wpd-native-slider-pagination').forEach(function (element) {
            element.remove();
        });
    }

    function bindNativeFallback(slider) {
        if (slider.dataset.wpdFallbackBound === 'true') {
            return;
        }

        var slides = Array.prototype.slice.call(slider.querySelectorAll('.splide__slide'));
        var thumbnails = Array.prototype.slice.call(slider.querySelectorAll('.wp-piwigo-display-slider-thumbnail'));
        var navigation = slider.dataset.navigation || 'thumbnails';
        var fallbackDots = [];
        var currentIndex = 0;

        if (!slides.length) {
            return;
        }

        slider.dataset.wpdFallbackBound = 'true';

        function showSlide(index) {
            if (!slides.length) {
                return;
            }

            currentIndex = (index + slides.length) % slides.length;
            slides.forEach(function (slide, slideIndex) {
                var active = slideIndex === currentIndex;
                slide.hidden = !active;
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
            setActiveThumbnail(thumbnails, currentIndex);
            fallbackDots.forEach(function (dot, dotIndex) {
                var active = dotIndex === currentIndex;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-current', active ? 'true' : 'false');
            });
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

        if (slides.length > 1) {
            var controls = document.createElement('div');
            controls.className = 'wpd-native-slider-controls';

            var previous = document.createElement('button');
            previous.type = 'button';
            previous.className = 'wp-piwigo-display-slider-arrow wp-piwigo-display-slider-prev';
            previous.setAttribute('aria-label', label('previous', 'Previous image'));
            previous.textContent = '‹';
            previous.addEventListener('click', function () {
                if (slider.dataset.wpdSliderInitialized !== 'true') {
                    showSlide(currentIndex - 1);
                }
            });

            var next = document.createElement('button');
            next.type = 'button';
            next.className = 'wp-piwigo-display-slider-arrow wp-piwigo-display-slider-next';
            next.setAttribute('aria-label', label('next', 'Next image'));
            next.textContent = '›';
            next.addEventListener('click', function () {
                if (slider.dataset.wpdSliderInitialized !== 'true') {
                    showSlide(currentIndex + 1);
                }
            });

            controls.appendChild(previous);
            controls.appendChild(next);
            slider.appendChild(controls);
        }

        if (navigation === 'dots' && slides.length > 1) {
            var pagination = document.createElement('div');
            pagination.className = 'wp-piwigo-display-slider-pagination wpd-native-slider-pagination';
            pagination.setAttribute('aria-label', label('navigation', 'Slideshow navigation'));

            slides.forEach(function (slide, index) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'wp-piwigo-display-slider-dot';
                dot.setAttribute('aria-label', numberedLabel(label('showImage', 'Show image %d'), index + 1));
                dot.addEventListener('click', function () {
                    if (slider.dataset.wpdSliderInitialized !== 'true') {
                        showSlide(index);
                    }
                });
                fallbackDots.push(dot);
                pagination.appendChild(dot);
            });
            slider.appendChild(pagination);
        }

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

        removeNativeFallbackControls(slider);
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
