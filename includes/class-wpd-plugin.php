.wp-piwigo-display-gallery {
    --wpd-image-fit: cover;
    --wpd-image-height: 180px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0;
}

.wp-piwigo-display-item {
    margin: 0;
}

.wp-piwigo-display-item a {
    display: block;
    text-decoration: none;
    overflow: hidden;
}

.wp-piwigo-display-item img {
    display: block;
    width: 100%;
    height: var(--wpd-image-height);
    object-fit: var(--wpd-image-fit);
    transition: transform 180ms ease, opacity 180ms ease;
}

.wp-piwigo-display-item a:hover img,
.wp-piwigo-display-item a:focus img {
    transform: scale(1.03);
    opacity: 0.95;
}

.wp-piwigo-display-item figcaption {
    margin-top: 0.35rem;
    font-size: 0.85rem;
    line-height: 1.3;
}

.wp-piwigo-display-slider {
    --wpd-slider-height: ;
    --wpd-slider-ratio: 16/9;
    --wpd-image-fit: cover;
    position: relative;
    width: 100%;
    max-width: 100%;
    margin: 1.5rem 0;
}

.wp-piwigo-display-slider-track {
    position: relative;
    aspect-ratio: var(--wpd-slider-ratio);
    overflow: hidden;
    background: rgba(0, 0, 0, 0.04);
}

.wp-piwigo-display-slider[style*="--wpd-slider-height: 1"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 2"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 3"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 4"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 5"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 6"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 7"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 8"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 9"] .wp-piwigo-display-slider-track {
    aspect-ratio: auto;
    height: var(--wpd-slider-height);
}

.wp-piwigo-display-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    pointer-events: none;
    transition: opacity 350ms ease;
}

.wp-piwigo-display-slide.is-active {
    opacity: 1;
    pointer-events: auto;
}

.wp-piwigo-display-slide-link {
    display: block;
    width: 100%;
    height: 100%;
}

.wp-piwigo-display-slide img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: var(--wpd-image-fit);
}

.wp-piwigo-display-slider-arrow {
    position: absolute;
    top: 50%;
    z-index: 2;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    border: 0;
    border-radius: 999px;
    transform: translateY(-50%);
    color: #111;
    background: rgba(255, 255, 255, 0.75);
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
}

.wp-piwigo-display-slider-arrow:hover,
.wp-piwigo-display-slider-arrow:focus {
    background: rgba(255, 255, 255, 0.95);
}

.wp-piwigo-display-slider-prev {
    left: 0.75rem;
}

.wp-piwigo-display-slider-next {
    right: 0.75rem;
}

.wp-piwigo-display-slider-pagination {
    display: flex;
    justify-content: center;
    gap: 0.35rem;
    margin-top: 0.65rem;
}

.wp-piwigo-display-slider-dot {
    width: 0.55rem;
    height: 0.55rem;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: currentColor;
    opacity: 0.3;
    cursor: pointer;
}

.wp-piwigo-display-slider-dot.is-active {
    opacity: 0.85;
}

.wp-piwigo-display-slide-caption {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    padding: 0.45rem 0.75rem;
    font-size: 0.85rem;
    line-height: 1.3;
    color: #fff;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.55));
    pointer-events: none;
}

.wp-piwigo-display-rounded .wp-piwigo-display-item,
.wp-piwigo-display-rounded .wp-piwigo-display-item a,
.wp-piwigo-display-rounded .wp-piwigo-display-item img,
.wp-piwigo-display-rounded .wp-piwigo-display-slider-track {
    border-radius: 8px;
}

.wp-piwigo-display-lightbox-open {
    overflow: hidden;
}

.wp-piwigo-display-lightbox {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: rgba(0, 0, 0, 0.86);
}

.wp-piwigo-display-lightbox.is-open {
    display: flex;
}

.wp-piwigo-display-lightbox-image {
    max-width: 92vw;
    max-height: 86vh;
    object-fit: contain;
}

.wp-piwigo-display-lightbox-close,
.wp-piwigo-display-lightbox-prev,
.wp-piwigo-display-lightbox-next {
    position: absolute;
    z-index: 2;
    border: 0;
    color: #fff;
    background: transparent;
    cursor: pointer;
}

.wp-piwigo-display-lightbox-close {
    top: 1rem;
    right: 1rem;
    font-size: 2.25rem;
}

.wp-piwigo-display-lightbox-prev,
.wp-piwigo-display-lightbox-next {
    top: 50%;
    transform: translateY(-50%);
    font-size: 3rem;
}

.wp-piwigo-display-lightbox-prev {
    left: 1rem;
}

.wp-piwigo-display-lightbox-next {
    right: 1rem;
}

.wp-piwigo-display-lightbox-caption {
    position: absolute;
    right: 1rem;
    bottom: 1rem;
    left: 1rem;
    color: #fff;
    text-align: center;
    font-size: 0.95rem;
}

.wp-piwigo-display-error {
    padding: 0.75rem 1rem;
    border-left: 4px solid currentColor;
    background: rgba(0, 0, 0, 0.05);
}


.wp-piwigo-display-slider-thumbnails {
    display: flex;
    gap: 0.4rem;
    margin-top: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
}

.wp-piwigo-display-slider-thumbnail {
    flex: 0 0 auto;
    width: 72px;
    height: 48px;
    padding: 0;
    border: 2px solid transparent;
    background: transparent;
    cursor: pointer;
    opacity: 0.62;
}

.wp-piwigo-display-slider-thumbnail.is-active {
    border-color: currentColor;
    opacity: 1;
}

.wp-piwigo-display-slider-thumbnail img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wp-piwigo-display-rounded .wp-piwigo-display-slider-thumbnail,
.wp-piwigo-display-rounded .wp-piwigo-display-slider-thumbnail img {
    border-radius: 4px;
}

@media (max-width: 600px) {
    .wp-piwigo-display-gallery {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 0.65rem;
    }

    .wp-piwigo-display-slider-arrow {
        width: 2rem;
        height: 2rem;
        font-size: 1.5rem;
    }

    .wp-piwigo-display-slider-thumbnail {
        width: 56px;
        height: 38px;
    }
}


.wp-piwigo-display-gallery.wp-piwigo-display-raw {
    align-items: start;
}

.wp-piwigo-display-gallery.wp-piwigo-display-raw .wp-piwigo-display-item img {
    height: auto;
    object-fit: contain;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slider-track {
    aspect-ratio: auto;
    height: auto;
    min-height: 0;
    background: transparent;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide {
    position: relative;
    display: none;
    opacity: 1;
    pointer-events: none;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide.is-active {
    display: block;
    pointer-events: auto;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide-link {
    height: auto;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide img {
    width: 100%;
    height: auto;
    object-fit: contain;
}


/* Correctif 1.0.3 : mode brut stabilisé */
.wp-piwigo-display-slider {
    --wpd-slider-speed: 500ms;
}

.wp-piwigo-display-slide {
    transition: opacity var(--wpd-slider-speed) ease;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slider-track {
    position: relative;
    aspect-ratio: 16 / 9;
    height: auto;
    max-height: 78vh;
    min-height: 260px;
    background: rgba(0, 0, 0, 0.04);
}

.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 1"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 2"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 3"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 4"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 5"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 6"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 7"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 8"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider.wp-piwigo-display-raw[style*="--wpd-slider-height: 9"] .wp-piwigo-display-slider-track {
    aspect-ratio: auto;
    height: var(--wpd-slider-height);
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide {
    position: absolute;
    inset: 0;
    display: block;
    opacity: 0;
    pointer-events: none;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide.is-active {
    opacity: 1;
    pointer-events: auto;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide-link {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide img {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}


/* Correctif 1.0.4 : slider stable, sans déformation ni recadrage par défaut */
.wp-piwigo-display-slider .wp-piwigo-display-slider-track {
    position: relative;
    aspect-ratio: var(--wpd-slider-ratio);
    height: auto;
    min-height: 260px;
    max-height: 78vh;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.04);
}

.wp-piwigo-display-slider[style*="--wpd-slider-height: 1"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 2"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 3"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 4"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 5"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 6"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 7"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 8"] .wp-piwigo-display-slider-track,
.wp-piwigo-display-slider[style*="--wpd-slider-height: 9"] .wp-piwigo-display-slider-track {
    aspect-ratio: auto;
    height: var(--wpd-slider-height);
}

.wp-piwigo-display-slider .wp-piwigo-display-slide {
    position: absolute;
    inset: 0;
    display: block;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--wpd-slider-speed) linear;
}

.wp-piwigo-display-slider .wp-piwigo-display-slide.is-active {
    opacity: 1;
    pointer-events: auto;
}

.wp-piwigo-display-slider .wp-piwigo-display-slide-link {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.wp-piwigo-display-slider .wp-piwigo-display-slide img {
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    object-fit: var(--wpd-current-image-fit, contain);
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slider-track {
    aspect-ratio: var(--wpd-slider-ratio);
    height: auto;
    min-height: 260px;
    max-height: 78vh;
    background: rgba(0, 0, 0, 0.04);
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide {
    position: absolute;
    inset: 0;
    display: block;
    opacity: 0;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide.is-active {
    opacity: 1;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide-link {
    height: 100%;
}

.wp-piwigo-display-slider.wp-piwigo-display-raw .wp-piwigo-display-slide img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}


/* 1.0.6 : retour à Splide */
.wp-piwigo-display-slider.splide .splide__track {
    aspect-ratio: var(--wpd-slider-ratio);
    background: rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 1"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 2"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 3"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 4"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 5"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 6"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 7"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 8"] .splide__track,
.wp-piwigo-display-slider.splide[style*="--wpd-slider-height: 9"] .splide__track {
    aspect-ratio: auto;
    height: var(--wpd-slider-height);
}

.wp-piwigo-display-slider.splide .splide__slide {
    height: auto;
}

.wp-piwigo-display-slider.splide .wp-piwigo-display-slide-link {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.wp-piwigo-display-slider.splide .splide__slide img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: var(--wpd-image-fit, contain);
}

.wp-piwigo-display-slider.splide .splide__arrow {
    opacity: 0.82;
}

.wp-piwigo-display-slider.splide .splide__pagination {
    bottom: 0.5rem;
}

.wp-piwigo-display-rounded.wp-piwigo-display-slider.splide .splide__track {
    border-radius: 8px;
}
