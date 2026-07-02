document.addEventListener('DOMContentLoaded', function () {
    initSliders();
    initLightbox();
});

function initSliders() {
    document.querySelectorAll('.wp-piwigo-display-slider').forEach(function (slider) {
        var slides = Array.prototype.slice.call(slider.querySelectorAll('.wp-piwigo-display-slide'));
        var previous = slider.querySelector('.wp-piwigo-display-slider-prev');
        var next = slider.querySelector('.wp-piwigo-display-slider-next');
        var pagination = slider.querySelector('.wp-piwigo-display-slider-pagination');
        var autoplay = slider.dataset.autoplay === 'true';
        var interval = parseInt(slider.dataset.interval || '5000', 10);
        var current = 0;
        var timer = null;

        if (!slides.length) {
            return;
        }

        function renderPagination() {
            if (!pagination) {
                return;
            }

            pagination.innerHTML = '';

            slides.forEach(function (_, index) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'wp-piwigo-display-slider-dot';
                button.setAttribute('aria-label', 'Afficher l’image ' + (index + 1));
                button.addEventListener('click', function () {
                    goTo(index);
                    restart();
                });
                pagination.appendChild(button);
            });
        }

        function update() {
            slides.forEach(function (slide, index) {
                slide.classList.toggle('is-active', index === current);
            });

            if (pagination) {
                Array.prototype.slice.call(pagination.children).forEach(function (dot, index) {
                    dot.classList.toggle('is-active', index === current);
                });
            }
        }

        function goTo(index) {
            current = (index + slides.length) % slides.length;
            update();
        }

        function start() {
            if (!autoplay || slides.length < 2) {
                return;
            }

            timer = window.setInterval(function () {
                goTo(current + 1);
            }, interval);
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function restart() {
            stop();
            start();
        }

        if (previous) {
            previous.addEventListener('click', function () {
                goTo(current - 1);
                restart();
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                goTo(current + 1);
                restart();
            });
        }

        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);
        slider.addEventListener('focusin', stop);
        slider.addEventListener('focusout', start);

        slider.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                goTo(current - 1);
                restart();
            }

            if (event.key === 'ArrowRight') {
                goTo(current + 1);
                restart();
            }
        });

        renderPagination();
        update();
        start();
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
