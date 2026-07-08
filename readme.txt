=== WP Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.5.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Affiche des albums Piwigo dans WordPress via l'API officielle, sans importer les images dans la médiathèque.

== Description ==

WP Piwigo Display permet d'afficher des albums Piwigo dans WordPress avec un shortcode simple.

Les images restent dans Piwigo. WordPress interroge l'API, met les résultats en cache et génère l'affichage.

Fonctionnalités principales :

* galerie responsive ;
* diaporama ;
* lightbox ;
* cache WordPress ;
* réglages d'affichage par défaut ;
* tri et limitation des images ;
* affichage récursif des sous-albums ;
* presets ;
* URL Piwigo ponctuelle ;
* album par identifiant, nom ou chemin.

== Installation ==

1. Envoyer le plugin dans `wp-content/plugins`.
2. Activer le plugin dans WordPress.
3. Configurer l'URL de la galerie dans Réglages > WP Piwigo Display.
4. Utiliser un shortcode comme `[piwigo album="154"]`.

== Shortcodes ==

Exemples :

`[piwigo album="154"]`

`[piwigo album="154" type="slider"]`

`[piwigo album="154" preset="actualites"]`

`[piwigo album="154" recursive="true"]`

== Changelog ==

= 1.5.0 =
* Ajout des presets.
* Ajout des albums par nom ou chemin.
* Amélioration des messages d'erreur.
* Ajout du readme.txt WordPress.
