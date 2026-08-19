=== Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 3.0.0-rc.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Display public or authorized private Piwigo albums in WordPress through the official API without importing images into the media library.

== Description ==

Piwigo Display keeps images in Piwigo and renders them inside WordPress without copying them into the WordPress media library.

The last public stable release before the V3 release-candidate train was 1.8.0. The 2.0.0 development line was never published as a public release; its work was consolidated into V3.

Version 3.0.0-rc.3 includes:

* dynamic Gutenberg block;
* Classic Editor integration with TinyMCE preview;
* administration gallery composer;
* functional parity between Gutenberg, Classic Editor, and the administration composer;
* responsive galleries, Splide sliders, lightbox, and CSS-column Masonry;
* visual, hierarchical, searchable album picker;
* album selection by ID, name, path, or tree;
* sub-albums and configurable depth;
* sorting, limits, orientation filters, tags, captions, styles, and frame shapes;
* slider transitions (`slide`, `fade`, `none`) and `ltr` / `rtl` direction;
* independent interval and transition-speed controls;
* WordPress caching separated by access context;
* diagnostics and cache purge;
* persistent API/cache health metrics with API call count, HIT/MISS rate, timings, latest method/status/error, and health verdict;
* server-side Piwigo service account for authorized private albums;
* keyboard navigation, visible focus, and reduced-motion support.

API health metrics do not store credentials, passwords, or HTTP request bodies.

The service account does not sign visitors into Piwigo. A private album rendered on a public WordPress page becomes visible on that page, so the dedicated account must only have access to albums intended for publication.

== Installation ==

1. Upload the ZIP file from Plugins > Add New Plugin > Upload Plugin.
2. Activate Piwigo Display.
3. Open the plugin settings and enter the Piwigo HTTPS URL.
4. Test the connection.
5. Insert the Piwigo Display block, use the administration composer, or use a shortcode such as `[piwigo album="154"]`.
6. For private albums, configure a dedicated Piwigo service account restricted to the albums that may be published.

== Shortcodes ==

Basic gallery:

`[piwigo album="154"]`

Slider:

`[piwigo album="154" type="slider" width="72%" height="480px"]`

Slider with fade transition:

`[piwigo album="154" type="slider" transition="fade" speed="700"]`

Right-to-left slider:

`[piwigo album="154" type="slider" transition="slide" direction="rtl"]`

Masonry:

`[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]`

Recursive albums:

`[piwigo album="154" recursive="true" depth="2"]`

Sorting and limits:

`[piwigo album="154" sort="date" order="desc" limit="20"]`

Tags:

`[piwigo album="154" tags="nature,animals" tag_mode="all"]`

== Frequently Asked Questions ==

= Are images copied into WordPress? =

No. They remain stored in Piwigo.

= How can I display a private album? =

Create a dedicated Piwigo account, restrict it to the albums that may be published, and enable the service account in WordPress. HTTPS is required.

= Can visitors see the Piwigo credentials? =

No. Authentication and session cookies remain server-side.

= What does API & cache health show? =

It shows aggregated API calls, cache HIT/MISS counts and rate, cumulative/average/slowest API time, the latest API method and HTTP status, the latest sanitized error, and a compact health verdict.

= Does the slider respect reduced-motion preferences? =

Yes. When the operating system requests reduced motion, autoplay is disabled and transitions are removed or reduced.

== Changelog ==

= 3.0.0-rc.3 =

* Restored persistent API and cache health diagnostics.
* Added API call count, cache HIT/MISS statistics, hit rate, cumulative/average/slowest timings, latest API method, HTTP status, latest error, and health verdict.
* Added a regression test protecting the diagnostic counter from accidental removal.
* Kept metrics credential-free and request-body-free.
* Consolidated the public version history: 1.8.0 was the last stable public release before V3; 2.0.0 was never published as a public release.
* Aligned the public product name to Piwigo Display.

= 3.0.0-rc.2 =

* Fixed the PHP 8.1 fatal error caused when the Masonry render filter received the renderer's initial null value, including on sliders.
* Added a regression test reproducing the exact nullable filter call found in the WordPress debug log.
* Fixed the release ZIP process so registered CSS and JavaScript paths keep matching their packaged files.
* Added an automated packaged-asset integrity check.
* Added an independent transparent slider background option that preserves the selected visual style.
* Fixed Classic Editor shortcode previews leaking HTML attributes into the article during double-click editing.

= 3.0.0-rc.1 =

* First 3.x Release Candidate.
* Refactored the plugin architecture for the 3.x code base.
* Added CSS-column Masonry with configurable columns and gaps.
* Added `slide`, `fade`, and `none` slider transitions.
* Added `ltr` and `rtl` slider direction.
* Improved functional parity between Gutenberg, Classic Editor, and the administration composer.
* Improved the visual hierarchical album picker and keyboard navigation.
* Added reduced-motion handling for sliders.
* Hardened privileged actions, HTTP requests, service-account URL validation, and other security invariants.
* Added accessibility and security regression checks to the single CI workflow.
* Kept PHP 8.1 through PHP 8.4 compatibility and Plugin Check validation in CI.

= 2.0.0 (development milestone, never published as a public release) =

* Added a Piwigo service account for authorized private albums.
* Kept authentication and session cookies server-side.
* Separated anonymous and authenticated caches.
* Added album search and tree selection.
* Added visual slider resizing in Gutenberg.
* Maintained parity across Gutenberg, Classic Editor, and the administration composer.
* Added CI coverage for PHP 8.1 through PHP 8.4 and automatic installable ZIP generation.
