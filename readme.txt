=== Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, gutenberg, slider
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 3.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Build and display Piwigo galleries visually in WordPress through the official API without importing images into the media library.

== Description ==

Piwigo Display keeps images in Piwigo and lets users build their presentation directly inside WordPress without copying files into the WordPress media library.

Version 3 is no longer only a shortcode-based integration. It provides a dynamic Gutenberg block, an administration gallery composer, Classic Editor / TinyMCE integration, and a visual hierarchical album picker. These interfaces share the same rendering engine and settings. Shortcodes remain available as an advanced and backward-compatible interface.

Piwigo Display 3.0.0 was released on GitHub on November 10, 2026. Version 3.0.1 is being prepared as a maintenance update; it has not yet been released. WordPress.org directory distribution is being prepared and is not yet available.

Version 3.0.0 includes:

* dynamic Gutenberg block with visual controls;
* administration gallery composer with preview;
* Classic Editor integration with TinyMCE preview;
* functional parity between Gutenberg, Classic Editor, and the administration composer;
* visual, hierarchical, searchable album picker;
* responsive galleries, Splide sliders, lightbox, and CSS-column Masonry;
* album selection by ID, name, path, or tree;
* sub-albums and configurable depth;
* sorting, limits, orientation filters, tags, captions, styles, and frame shapes;
* slider transitions (`slide`, `fade`, `none`) and `ltr` / `rtl` direction;
* independent interval and transition-speed controls;
* shortcode support for advanced, automated, and legacy workflows;
* WordPress caching separated by access context;
* diagnostics and cache purge;
* persistent API/cache health metrics with API call count, HIT/MISS rate, timings, latest method/status/error, and health verdict;
* server-side Piwigo service account for authorized private albums;
* keyboard navigation, visible focus, and reduced-motion support.

API health metrics do not store credentials, passwords, or HTTP request bodies.

The service account does not sign visitors into Piwigo. A private album rendered on a public WordPress page becomes visible on that page, so the dedicated account must only have access to albums intended for publication.

Piwigo Display connects to the Piwigo server configured by the site administrator through its web-service API. The plugin needs that external Piwigo server to load album and image metadata; visitors' browsers also load displayed images from Piwigo. No unrelated third-party gallery service is required.

Compatibility with the Piwigo OpenStreetMap extension is **not yet confirmed**. V3 includes a workaround for additional HTML/JavaScript mixed into Piwigo JSON responses, but the reported issue still needs testing on an affected installation (GitHub issue #97). Piwigo Display does not provide its own map or GPS view.

== Installation ==

1. Upload the ZIP file from Plugins > Add New Plugin > Upload Plugin.
2. Activate Piwigo Display.
3. Open the plugin settings and enter the Piwigo HTTPS URL.
4. Test the connection.
5. Build the gallery with the Gutenberg block, administration composer, or Classic Editor button.
6. Use a shortcode directly only when an advanced, automated, or legacy workflow benefits from it.
7. For private albums, configure a dedicated Piwigo service account restricted to the albums that may be published.

== Visual editing ==

The preferred V3 workflow is visual:

* select an album using the searchable hierarchical picker;
* choose gallery, slider, or Masonry display;
* configure dimensions, transitions, filtering, captions, styles, and shapes;
* preview the result from the available editor/composer interface;
* insert the configured display into the page or post.

== Shortcodes ==

Shortcodes remain fully supported for advanced integrations, generated content, templates, and backward compatibility.

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

= Do I need to write shortcodes? =

No. V3 provides visual interfaces through Gutenberg, the administration composer, and Classic Editor. Shortcodes remain available for advanced use and backward compatibility.

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

= Does it support the Piwigo OpenStreetMap plugin? =

Compatibility is not confirmed yet. A workaround is included for malformed API responses, but real-world validation is still pending. See https://github.com/fr4nck/WP-Piwigo-Display/issues/97 . This plugin does not display maps.

== Changelog ==

= 3.0.1 (maintenance update, pending release) =

* Refresh the Piwigo album list directly in the Classic Editor/admin composer and Gutenberg picker.
* Keep newly created albums selectable and ignore stale asynchronous responses.
* Fix administration composer shortcodes not updating after confirming an album.
* Keep the Gutenberg selector usable after closing during a refresh.
* Preserve literal album names beginning with # (for example, #2024). Numeric album IDs must be entered without #, preventing unintended substitution of an album after a rename.
* Add regression tests for refresh, concurrency, event dispatch and album name compatibility.

= 3.0.0 =

* First stable V3 release. Includes all changes of the unpublished 3.0.0-rc.4 candidate.
* Splide now measures slides without the fallback layout: the first photo is shown on load and looping no longer skips it.
* Fallback photos fit the slider height; thumbnails no longer drive a slider whose mounting failed.
* Slider width percentages apply within the theme content column, including block themes, without overflow.
* Gutenberg resize handles work with the mouse and the keyboard inside the editor canvas and keep focus.
* The editor canvas loads the public styles so slider previews match the front end.
* The API health block shows the Piwigo method of GET requests, such as the diagnostic probe.

= 3.0.0-rc.4 (candidate, never published; included in 3.0.0) =

* Prevents release replacement by branch workflows.
* Fixes shortcode attribute handling and preset precedence.
* Keeps upstream errors private and cleans service credentials at uninstall.
* Protects private galleries from stale-cache fallback and improves album selectors.
* Repairs Gutenberg shortcode transformation and TinyMCE literal text handling.
* Keeps images visible and provides native navigation when Splide is unavailable.
* Constrains slider width to the theme container and improves Gutenberg resizing.
* Restores the Classic Editor insert button for contributors without upload permission.
* Corrects API health status, method detection and stale errors.
* Keeps the public album picker for contributors when a service account is configured.
* Excludes looping slider clones from lightbox navigation.

= 3.0.0-rc.3 =

* Restored persistent API and cache health diagnostics.
* Added API call count, cache HIT/MISS statistics, hit rate, cumulative/average/slowest timings, latest API method, HTTP status, latest error, and health verdict.
* Added a regression test protecting the diagnostic counter from accidental removal.
* Kept metrics credential-free and request-body-free.
* Historical note from rc.3: previously claimed that 1.8.0 was the last stable release; GitHub also contains published 1.9.0 and 1.10.0 stable releases. Version 2.0.0 was never publicly released.
* Aligned the public product name to Piwigo Display.
* Reframed V3 documentation around its visual editing tools, with shortcodes documented as an advanced interface rather than the primary workflow.

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
