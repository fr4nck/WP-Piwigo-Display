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

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'wp-piwigo-display-lightbox-close';
    close.setAttribute('aria-label', 'Fermer');
    close.textContent = '×';

    var previous = document.createElement('button');
    previous.type = 'button';
    previous.className = 'wp-piwigo-display-lightbox-prev';
    previous.setAttribute('aria-label', 'Image précédente');
    previous.textContent = '‹';

    var image = document.createElement('img');
    image.className = 'wp-piwigo-display-lightbox-image';
    image.alt = '';

    var next = document.createElement('button');
    next.type = 'button';
    next.className = 'wp-piwigo-display-lightbox-next';
    next.setAttribute('aria-label', 'Image suivante');
    next.textContent = '›';

    var caption = document.createElement('div');
    caption.className = 'wp-piwigo-display-lightbox-caption';

    overlay.appendChild(close);
    overlay.appendChild(previous);
    overlay.appendChild(image);
    overlay.appendChild(next);
    overlay.appendChild(caption);

    document.body.appendChild(overlay);
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
