=== WP Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Affiche des albums Piwigo dans WordPress via l'API officielle, sans importer les images dans la médiathèque.

== Description ==

WP Piwigo Display est un plugin WordPress. Il s'installe dans WordPress et utilise l'API officielle de Piwigo.

Les images restent dans Piwigo. WordPress interroge l'API, met les résultats en cache et génère l'affichage.

Fonctionnalités principales :

* galerie responsive ;
* diaporama ;
* lightbox ;
* cache WordPress ;
* réglages d'affichage par défaut ;
* tri et limitation des images ;
* affichage d'un album et de ses sous-albums ;
* profondeur récursive configurable ;
* pagination des résultats Piwigo ;
* suppression des doublons ;
* presets ;
* URL Piwigo ponctuelle ;
* album par identifiant, nom ou chemin.

== Installation ==

1. Téléverser le fichier ZIP depuis Extensions > Ajouter une extension.
2. Activer WP Piwigo Display.
3. Configurer l'URL de la galerie dans Réglages > WP Piwigo Display.
4. Utiliser un shortcode comme `[piwigo album="154"]`.

== Shortcodes ==

Album simple :

`[piwigo album="154"]`

Diaporama :

`[piwigo album="154" type="slider"]`

Album et tous ses sous-albums :

`[piwigo album="154" recursive="true"]`

Profondeur limitée :

`[piwigo album="154" recursive="true" depth="2"]`

Dernières images d'une arborescence :

`[piwigo album="154" recursive="true" sort="date" order="desc" limit="20"]`

== Frequently Asked Questions ==

= Où le plugin doit-il être installé ? =

WP Piwigo Display est un plugin WordPress. Il ne s'installe pas dans Piwigo.

= Les images sont-elles copiées dans WordPress ? =

Non. Les images restent dans Piwigo et sont affichées via l'API officielle.

= Comment inclure les sous-albums ? =

Ajoutez `recursive="true"` au shortcode. Utilisez `depth` pour limiter le nombre de niveaux parcourus.

== Changelog ==

= 1.7.0 =
* Ajout du paramètre `caption`.
* Choix entre aucune légende, titre, description ou titre et description.
* Ajout d'un réglage global des légendes.
* Prise en charge des légendes dans la galerie, le diaporama et la lightbox.


= 1.6.1 =
* Documentation complète de l'affichage récursif.
* Ajout d'exemples avec `recursive` et `depth`.
* Mise à jour du README et du readme.txt.
* Ajout d'une documentation dédiée et d'une feuille de route.

= 1.6.0 =
* Affichage d'un album avec l'ensemble de ses sous-albums.
* Profondeur configurable avec `depth`.
* Pagination automatique au-delà de 500 images.
* Suppression des doublons.
* Cache distinct pour les galeries récursives.

= 1.5.5 =
* Correction d'une erreur fatale lors de l'appel à l'API Piwigo.
