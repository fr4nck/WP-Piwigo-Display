=== Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, gutenberg, slider
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 3.1.0-rc.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Build and display Piwigo galleries visually in WordPress through the official API without importing images into the media library.

== Description ==

Piwigo Display keeps images in Piwigo and lets users build their presentation directly inside WordPress without copying files into the WordPress media library.

Piwigo Display provides a dynamic Gutenberg block, an administration gallery composer, Classic Editor / TinyMCE integration, and a visual hierarchical album picker. These interfaces share the same rendering engine and settings. Shortcodes remain available as an advanced and backward-compatible interface.

The last public stable release before the V3 release-candidate train was 1.8.0. The 2.0.0 development line was never published as a public release; its work was consolidated into V3.

3.1.0-rc.1 is the first release candidate of the 3.1 line. It builds on the V3 RC3 foundation and adds Justified Gallery, Collage / Pêle-mêle, extended shapes, sanitized custom SVG masks, and Photo-filled text.

Current features include:

* dynamic Gutenberg block with visual controls;
* administration gallery composer with preview;
* Classic Editor integration with TinyMCE preview;
* functional parity between Gutenberg, Classic Editor, and the administration composer;
* visual, hierarchical, searchable album picker;
* responsive standard gallery, Splide slider, CSS-column Masonry and Justified Gallery;
* deterministic Collage / Pêle-mêle layout;
* lightbox, captions, filters and album recursion;
* extended frame shapes and sanitized custom SVG masks;
* Photo-filled text using multiple Piwigo photos inside glyphs;
* Photo-filled text grid, masonry and collage fill modes;
* multiline text, alignment, spacing, density, outline and deterministic seed;
* local theme/system fonts, bundled Bebas Neue and Bungee, and secured user WOFF2/WOFF imports;
* no automatic third-party font loading;
* slider fallback when Splide loads late or fails;
* WordPress caching separated by access context;
* diagnostics and cache purge;
* persistent API/cache health metrics with API call count, HIT/MISS rate, timings, latest method/status/error, and health verdict;
* server-side Piwigo service account for authorized private albums;
* keyboard navigation, visible focus, semantic Photo-filled text and reduced-motion support.

API health metrics do not store credentials, passwords, or HTTP request bodies.

== Installation ==

1. Upload the ZIP file from Plugins > Add New Plugin > Upload Plugin.
2. Activate Piwigo Display.
3. Open the plugin settings and enter the Piwigo HTTPS URL.
4. Test the connection.
5. Build the gallery with the Gutenberg block, administration composer, or Classic Editor button.
6. Use a shortcode directly only when an advanced, automated, or legacy workflow benefits from it.
7. For private albums, configure a dedicated Piwigo service account restricted to the albums that may be published.

== Visual editing ==

The preferred workflow is visual: choose the album with the searchable hierarchy, select a display mode, configure its presentation, preview it, and insert it from the editor or composer.

== Shortcodes ==

Shortcodes remain fully supported for advanced integrations, generated content, templates, and backward compatibility.

`[piwigo album="154"]`

`[piwigo album="154" type="slider" width="72%" height="480px"]`

`[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]`

`[piwigo album="154" type="justified" justified_row_height="220" justified_gap="8"]`

`[piwigo album="154" type="collage" collage_seed="2026"]`

`[piwigo album="154" type="photo-text" photo_text="SUMMER 2026" photo_text_font="bundled-bebas-neue"]`

== Frequently Asked Questions ==

= Do I need to write shortcodes? =

No. Piwigo Display provides visual interfaces through Gutenberg, the administration composer, and Classic Editor integration. But if writing shortcodes is your own form of therapy, you can. 🙂

= Are images copied into WordPress? =

No. They remain stored in Piwigo.

= How can I display a private album? =

Create a dedicated Piwigo account, restrict it to the albums that may be published, and enable the service account in WordPress. HTTPS is required.

= Can visitors see the Piwigo credentials? =

No. Authentication and session cookies remain server-side.

= Can I import my own SVG mask? =

Yes. Custom SVG masks are sanitized before storage and rendering. Active content, external references and unsafe constructs are rejected.

= Can I use a custom font with Photo-filled text? =

Yes. WOFF2 and WOFF files can be imported locally by administrators. The plugin validates the upload and stores it in a dedicated WordPress uploads directory. Imported user fonts are never added to the plugin distribution package.

== Changelog ==

= 3.1.0-rc.1 =

* Added Justified Gallery.
* Added deterministic Collage / Pêle-mêle.
* Extended the built-in frame-shape library.
* Added secured custom SVG mask import, library, preview and deletion.
* Added Photo-filled text with multiple Piwigo images inside glyphs.
* Added advanced Photo-filled text typography, multiline rendering and grid/masonry/collage fill modes.
* Added secured local WOFF2/WOFF user-font imports.
* Added bundled Bebas Neue and Bungee fonts under SIL OFL 1.1.
* Carried forward the RC3 API/cache health diagnostics and slider fallback/debug fixes.
* Kept editor parity across Gutenberg, Classic Editor and the administration composer.
* Validated syntax, security, accessibility, frontend tests, WPCS, PHP 8.1/8.4 and WordPress Plugin Check before RC preparation.

= 3.0.0-rc.3 =

* Restored persistent API and cache health diagnostics.
* Added API call count, cache HIT/MISS statistics, hit rate, timings, latest API method, HTTP status, latest error, and health verdict.
* Added a regression test protecting the diagnostic counter from accidental removal.
* Added a native slider fallback and delayed Splide initialization resilience.
* Consolidated the public version history: 1.8.0 was the last stable public release before V3; 2.0.0 was never published as a public release.
* Aligned the public product name to Piwigo Display.

= 3.0.0-rc.2 =

* Fixed the PHP 8.1 fatal error caused when the Masonry render filter received the renderer's initial null value.
* Fixed packaged frontend assets, transparent slider background, and Classic Editor preview encoding.

= 3.0.0-rc.1 =

* First 3.x Release Candidate.
* Refactored the plugin architecture for the 3.x code base.
* Added Masonry, slider transitions, editor parity, accessibility, security and CI hardening.

= 2.0.0 (development milestone, never published as a public release) =

* Development work later consolidated into V3.
