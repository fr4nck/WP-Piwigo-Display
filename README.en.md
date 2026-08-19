# Piwigo Display

WordPress plugin for building and displaying Piwigo galleries directly inside WordPress through the official API, without copying images into the WordPress media library.

> French documentation: see `README.md`.

## Project status

**Current release candidate: 3.0.0-rc.3.**

The last stable public release before the V3 release-candidate line was **1.8.0**. The **2.0.0** development line was never distributed as a public release; its work was consolidated into V3.

V3 is currently a Release Candidate and should still be considered a test version before the final 3.0.0 release.

## Visual tools first

Piwigo Display V3 is no longer just a set of shortcodes. It provides several visual tools sharing the same settings and rendering engine:

- **dynamic Gutenberg block** with album selection and visual settings inside the editor;
- **administration composer** to prepare and preview a gallery before insertion;
- **Classic Editor / TinyMCE integration** with a dedicated button and preview;
- **visual, hierarchical and searchable album picker**;
- **functional parity** between Gutenberg, Classic Editor and the administration composer;
- **shortcodes** retained as an advanced interface, portable format and compatibility layer.

The goal is to let users build galleries without writing code while preserving shortcodes for automation, advanced workflows and backward compatibility.

## Main V3 features

- connection to Piwigo through the official API;
- public albums and authorized private albums through a server-side service account;
- responsive standard gallery;
- Splide slider/carousel;
- native CSS-column Masonry;
- lightbox;
- album selection by ID, name, path or tree;
- sub-albums and configurable depth;
- sorting, limits, orientation filters, tags, captions and styles;
- frame shapes;
- slider transitions: `slide`, `fade` and `none`;
- `ltr` and `rtl` direction;
- transparent slider background independently from the selected visual style;
- configurable width, height, ratio, speed and interval;
- WordPress cache separated by access context;
- diagnostics and cache purge;
- improved keyboard navigation, visible focus and `prefers-reduced-motion` support.

## API & cache health

V3 RC3 restores and protects the diagnostic counter available under **Piwigo Display → Diagnostic**.

The **API & cache health** section reports:

- actual Piwigo API call count;
- cache HIT and MISS counts;
- HIT rate;
- cumulative, average and slowest API time;
- latest observed Piwigo method;
- latest HTTP status;
- latest detected error;
- compact health verdict.

Metrics are aggregated without storing credentials, passwords or request bodies. A regression test protects this diagnostic feature from accidental removal during future refactoring.

## Installing the RC

1. Download the Release Candidate ZIP from the GitHub artifacts/releases provided for V3.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Activate **Piwigo Display**.
4. Enter the Piwigo HTTPS URL in the plugin settings.
5. Test the connection.
6. Build the display using the Gutenberg block, administration composer or Classic Editor button.
7. When needed, use a shortcode directly for advanced or automated workflows.

For private albums, use a dedicated Piwigo account restricted to albums intended for publication through WordPress.

## Display modes

### Standard gallery

Responsive grid compatible with captions, lightbox, frame shapes and filters.

### Slider / carousel

Splide slideshow with configurable dimensions, autoplay, transition speed, direction and `slide`, `fade` or `none` transitions.

### Masonry

CSS-column layout with configurable column count and spacing, automatically adapting to tablets and mobile devices.

## Shortcodes: advanced interface

Shortcodes remain available for manual integrations, templates, generated content and compatibility with earlier versions. They are no longer the plugin's only interface.

```text
[piwigo album="154"]
[piwigo album="154" type="slider" width="72%" height="480px"]
[piwigo album="154" type="slider" transition="fade" speed="700"]
[piwigo album="154" type="slider" transition="slide" direction="rtl"]
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
[piwigo album="154" recursive="true" depth="2"]
[piwigo album="154" sort="date" order="desc" limit="20"]
[piwigo album="154" tags="nature,animals" tag_mode="all"]
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
- V3 acceptance tests: `docs/RECETTE-3X.md`
- Architecture: `docs/architecture.md`
- Roadmap: `ROADMAP.md`

## License

GNU GPL v3 or later.
