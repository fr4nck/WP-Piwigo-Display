document.addEventListener('DOMContentLoaded', function () {
    initSplideSliders();
    initLightbox();
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

function initLightbox() {
    var links = Array.prototype.slice.call(document.querySelectorAll('.wp-piwigo-display-lightbox-enabled [data-wpd-lightbox="true"]'));

    if (!links.length) {
        return;
    }

    var overlay = document.createElement('div');
    overlay.className = 'wp-piwigo-display-lightbox';
    overlay.innerHTML = '' +
        '<button type="button" class="wp-piwigo-display-lightbox-close" aria-label="Fermer">×</button>' +
        '<button type="button" class="wp-piwigo-display-lightbox-prev" aria-label="Image précédente">‹</button>' +
        '<img class="wp-piwigo-display-lightbox-image" alt="">' +
        '<button type="button" class="wp-piwigo-display-lightbox-next" aria-label="Image suivante">›</button>' +
        '<div class="wp-piwigo-display-lightbox-caption"></div>';

    document.body.appendChild(overlay);

    var image = overlay.querySelector('.wp-piwigo-display-lightbox-image');
    var caption = overlay.querySelector('.wp-piwigo-display-lightbox-caption');
    var close = overlay.querySelector('.wp-piwigo-display-lightbox-close');
    var previous = overlay.querySelector('.wp-piwigo-display-lightbox-prev');
    var next = overlay.querySelector('.wp-piwigo-display-lightbox-next');
    var current = 0;

    function open(index) {
        current = index;
        update();
        overlay.classList.add('is-open');
        document.body.classList.add('wp-piwigo-display-lightbox-open');
    }

    function update() {
        var link = links[current];
        var title = link.dataset.wpdTitle || '';

        image.src = link.href;
        image.alt = title;
        caption.textContent = title;
        caption.hidden = title === '';
    }

    function closeLightbox() {
        overlay.classList.remove('is-open');
        document.body.classList.remove('wp-piwigo-display-lightbox-open');
        image.src = '';
    }

    function goTo(index) {
        current = (index + links.length) % links.length;
        update();
    }

    links.forEach(function (link, index) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            open(index);
        });
    });

    close.addEventListener('click', closeLightbox);
    previous.addEventListener('click', function () { goTo(current - 1); });
    next.addEventListener('click', function () { goTo(current + 1); });

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (!overlay.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape') {
            closeLightbox();
        }

        if (event.key === 'ArrowLeft') {
            goTo(current - 1);
        }

        if (event.key === 'ArrowRight') {
            goTo(current + 1);
        }
    });
}
