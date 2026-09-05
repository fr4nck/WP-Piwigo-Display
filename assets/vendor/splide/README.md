# Splide vendor provenance

Piwigo Display vendors Splide locally so the distributed WordPress plugin does not load executable JavaScript or CSS from a third-party CDN.

- Package: `@splidejs/splide`
- Version: `4.1.4`
- Upstream repository: `https://github.com/Splidejs/splide`
- Upstream package: `https://www.npmjs.com/package/@splidejs/splide`
- License: MIT
- Copyright: 2022 Naotoshi Fujita

The vendored files must correspond to the upstream 4.1.4 distribution:

- `dist/js/splide.min.js` -> `assets/vendor/splide/splide.min.js`
- `dist/css/splide.min.css` -> `assets/vendor/splide/splide.min.css`
- `LICENSE` -> `assets/vendor/splide/LICENSE`

Do not replace these files with an unrelated CDN build. When updating Splide, update the version, this provenance note and the bundled license together.
