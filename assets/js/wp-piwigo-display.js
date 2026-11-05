document.addEventListener('DOMContentLoaded', function () {
    initLightbox();
});

function initLightbox() {
    var links = Array.prototype.slice.call(document.querySelectorAll('.wp-piwigo-display-lightbox-enabled [data-wpd-lightbox="true"]'));

    if (!links.length) {
        return;
    }

    var overlay = document.createElement('div');
    overlay.className = 'wp-piwigo-display-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Visionneuse de photos Piwigo');

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
    var previousFocus = null;

    function open(index) {
        previousFocus = document.activeElement;
        current = index;
        update();
        overlay.classList.add('is-open');
        document.body.classList.add('wp-piwigo-display-lightbox-open');
        close.focus();
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
        var wasOpen = overlay.classList.contains('is-open');
        overlay.classList.remove('is-open');
        document.body.classList.remove('wp-piwigo-display-lightbox-open');
        image.src = '';
        if (wasOpen && previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
        previousFocus = null;
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

        if (event.key === 'Tab') {
            var focusable = [close, previous, next];
            var active = document.activeElement;
            if (event.shiftKey && (active === close || focusable.indexOf(active) === -1)) {
                event.preventDefault();
                next.focus();
            } else if (!event.shiftKey && (active === next || focusable.indexOf(active) === -1)) {
                event.preventDefault();
                close.focus();
            }
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeLightbox();
            return;
        }

        if (event.key === 'ArrowLeft') {
            goTo(current - 1);
        }

        if (event.key === 'ArrowRight') {
            goTo(current + 1);
        }
    });
}
