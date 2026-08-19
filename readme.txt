=== WP Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Affiche des albums Piwigo dans WordPress via l’API officielle, sans importer les images dans la médiathèque.

== Description ==

WP Piwigo Display conserve les images dans Piwigo et génère leur affichage dans WordPress.

La dernière version officiellement publiée est la 1.8.0.

La version 2.0.0 n’a jamais été publiée. Les travaux qui avaient été engagés pour cette version ont ensuite été repris dans le développement de la future V3, actuellement en phase Release Candidate.

Fonctionnalités de la version stable :

* shortcode `[piwigo]` ;
* galerie responsive ;
* diaporama ;
* lightbox ;
* cache WordPress avec transients ;
* vidage manuel du cache ;
* test de connexion Piwigo ;
* réglages d’affichage ;
* albums et sous-albums ;
* profondeur récursive configurable ;
* tri et limitation des images ;
* sélection d’album par identifiant, nom ou chemin.

Le développement V3 ajoute notamment Gutenberg, l’éditeur classique, le composeur d’administration, Masonry, les orientations, les tags, un compte de service Piwigo, un cache renforcé et des diagnostics plus complets, dont le suivi Santé API & cache. Ces fonctions ne doivent pas être considérées comme une release stable tant que la V3 n’est pas publiée.

== Installation ==

1. Télécharger le ZIP de la release stable 1.8.0 depuis GitHub Releases.
2. Téléverser le ZIP depuis Extensions > Ajouter une extension.
3. Activer WP Piwigo Display.
4. Renseigner l’URL de Piwigo dans les réglages.
5. Utiliser le shortcode `[piwigo album="154"]`.

== Shortcodes ==

`[piwigo album="154"]`

`[piwigo album="154" type="slider"]`

`[piwigo album="154" recursive="true" depth="2"]`

`[piwigo album="154" sort="date" order="desc" limit="20"]`

== Frequently Asked Questions ==

= Les images sont-elles copiées dans WordPress ? =

Non. Elles restent dans Piwigo.

= Quelle est la dernière version publiée ? =

La version 1.8.0. La 2.0.0 n’a jamais été publiée.

= Où en est la V3 ? =

Elle est en phase Release Candidate et n’est pas encore annoncée comme version stable.

== Changelog ==

= 1.8.0 =

* Albums récursifs et profondeur configurable.
* Amélioration de l’intégration graphique.
* Évolutions du rendu et du cache.

= Développement non publié =

Les travaux postérieurs à 1.8.0, y compris ceux initialement regroupés sous le numéro 2.0.0, appartiennent au développement en cours et ne correspondent pas à une release stable publiée.
