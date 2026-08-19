# Piwigo Display

WordPress plugin for building and displaying Piwigo galleries directly inside WordPress through the official API, without copying images into the WordPress media library.

> French documentation: see `README.md`.

## Project status

**Current release candidate: 3.1.0-rc.1.**

The last stable public release before the V3 release-candidate line was **1.8.0**. The **2.0.0** development line was never distributed as a public release; its work was consolidated into V3.

The 3.1 line is now in real WordPress acceptance testing. Functional development is frozen during this RC: bug fixes are allowed, new features are deferred.

## Visual tools first

Piwigo Display provides several visual tools sharing the same rendering engine:

- **dynamic Gutenberg block** with album selection and visual settings;
- **administration composer** with preview;
- **Classic Editor / TinyMCE integration** with a dedicated button and preview;
- **visual, hierarchical and searchable album picker**;
- **functional parity** between Gutenberg, Classic Editor and the administration composer;
- **shortcodes** retained for automation, advanced workflows and backward compatibility.

## What's new in 3.1

### Justified Gallery

Rows are justified while preserving image ratios, with configurable target row height and spacing.

### Collage / Pêle-mêle

Deterministic photo layout with controlled rotation, displacement and overlap. The same seed and the same images produce the same composition.

### Shapes and custom SVG masks

The built-in shape library now includes cloud, heart, drop, triangle, pentagon, octagon and card-suit silhouettes.

Administrators can also import custom SVG masks. SVG content is sanitized before storage: scripts, event handlers, active styles, external references, `DOCTYPE`/`ENTITY` and unsafe content are rejected. Only the sanitized version is stored.

### Photo-filled text

A word, title or up to four lines can be used as a typographic mask filled with multiple Piwigo photos.

Available controls include:

- multiline text;
- size, maximum width, letter spacing and line height;
- left, center or right alignment;
- **grid**, **masonry** or **collage** fill mode;
- fill density and maximum photo count;
- collage rotation and spread;
- outline, color, width and background;
- deterministic seed;
- theme, system, serif or monospace fonts;
- bundled **Bebas Neue** and **Bungee** fonts;
- administrator-only local **WOFF2/WOFF** imports validated and stored in WordPress uploads.

No third-party remote font is loaded automatically.

## Main features

- official Piwigo API connection;
- public albums and authorized private albums through a server-side service account;
- responsive standard gallery;
- Splide slider/carousel with a native fallback when Splide loads late or fails;
- CSS-column Masonry;
- Justified Gallery;
- deterministic Collage / Pêle-mêle;
- Photo-filled text;
- lightbox;
- album selection by ID, name, path or tree;
- sub-albums and configurable depth;
- sorting, limits, orientation filters, tags, captions and styles;
- built-in shapes and sanitized custom SVG masks;
- slider transitions `slide`, `fade` and `none`;
- `ltr` and `rtl` direction;
- configurable width, height, ratio, speed and interval;
- WordPress cache separated by access context;
- diagnostics and cache purge;
- keyboard navigation, visible focus and `prefers-reduced-motion` support.

## API & cache health

The **Piwigo Display → Diagnostic → API & cache health** section reports actual API calls, cache HIT/MISS counts and rate, cumulative/average/slowest API time, latest Piwigo method, HTTP status, latest sanitized error and a compact health verdict.

Metrics are aggregated without storing credentials, passwords or request bodies.

## Installing the RC

1. Download the `piwigo-display-3.1.0-rc.1.zip` artifact produced by GitHub Actions.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Activate **Piwigo Display**.
4. Enter the Piwigo HTTPS URL in the plugin settings.
5. Test the connection.
6. Build a display using Gutenberg, the administration composer or Classic Editor.
7. Test the new 3.1 display modes before production use.

For private albums, use a dedicated Piwigo account restricted to albums intended for publication through WordPress.

## Shortcodes: advanced interface

```text
[piwigo album="154"]
[piwigo album="154" type="slider" width="72%" height="480px"]
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
[piwigo album="154" type="justified" justified_row_height="220" justified_gap="8"]
[piwigo album="154" type="collage" collage_seed="2026"]
[piwigo album="154" type="photo-text" photo_text="SUMMER 2026" photo_text_font="bundled-bebas-neue"]
```

## Compatibility

- WordPress 6.0 or later;
- PHP 8.1 through 8.4 validated by CI;
- HTTPS-accessible Piwigo instance for the service account;
- automated checks for syntax, security, accessibility, frontend rendering, PHP compatibility, WPCS, packaging and WordPress Plugin Check.

## Documentation

- Installation: `docs/installation.md`
- Configuration: `docs/configuration.md`
- Shortcodes: `docs/shortcodes.md`
- Service account: `docs/COMPTE-DE-SERVICE.md`
- Shapes: `docs/FORMES.md`
- Composer parity: `docs/PARITE-COMPOSEURS.md`
- 3.x acceptance tests: `docs/RECETTE-3X.md`
- Architecture: `docs/architecture.md`
- Roadmap: `ROADMAP.md`

## License

GNU GPL v3 or later.
