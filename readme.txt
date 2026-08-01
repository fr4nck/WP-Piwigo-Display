=== WP Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Display public or authorized private Piwigo albums in WordPress through the official API.

== Description ==

WP Piwigo Display keeps images in Piwigo and renders them inside WordPress without importing them into the media library.

Main features:

* dynamic Gutenberg block;
* Classic Editor integration with TinyMCE preview;
* administration gallery composer;
* responsive galleries, sliders, and lightbox;
* visual slider resizing;
* album selection by ID, name, path, or tree;
* sub-albums and configurable depth;
* sorting, limits, orientation filters, tags, captions, and styles;
* WordPress caching and diagnostics;
* server-side Piwigo service account for authorized private albums.

The service account does not sign visitors into Piwigo. However, a private album published on a public WordPress page becomes visible on that page.

== Installation ==

1. Upload the ZIP file from Plugins > Add New Plugin.
2. Activate WP Piwigo Display.
3. Enter the HTTPS Piwigo URL in the plugin settings.
4. Insert the WP Piwigo Display block or use `[piwigo album="154"]`.
5. For private albums, create a dedicated Piwigo account and configure the service account in the plugin settings or in wp-config.php.

== Shortcodes ==

`[piwigo album="154"]`

`[piwigo album="154" type="slider" width="72%" height="480px"]`

`[piwigo album="154" recursive="true" depth="2"]`

`[piwigo album="154" sort="date" order="desc" limit="20"]`

`[piwigo album="154" tags="nature,animals" tag_mode="all"]`

== Frequently Asked Questions ==

= Are images copied into WordPress? =

No. They remain stored in Piwigo.

= How can I display a private album? =

Create a dedicated Piwigo account, restrict it to the albums that may be published, and enable the service account in WordPress. HTTPS is required.

= Can visitors see the Piwigo credentials? =

No. Authentication and session cookies remain server-side.

== Changelog ==

= 2.0.0 =

* Added a Piwigo service account for authorized private albums.
* Kept authentication and session cookies server-side.
* Separated anonymous and authenticated caches.
* Added album search and tree selection.
* Added visual slider resizing in Gutenberg.
* Maintained parity across Gutenberg, Classic Editor, and the administration composer.
* Added CI coverage for PHP 8.1 through PHP 8.4 and automatic installable ZIP generation.
