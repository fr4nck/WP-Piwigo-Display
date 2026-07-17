=== WP Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.11.0
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
* tri, limitation et filtrage par orientation des images ;
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

Images portrait, paysage ou carrées :

`[piwigo album="154" orientation="portrait"]`
`[piwigo album="154" orientation="paysage"]`
`[piwigo album="154" orientation="carré"]`

Le paramètre `orientation` accepte `all` (toutes les images) par défaut, `portrait`, `paysage` et `carré`. Les alias historiques `landscape` et `square`, ainsi que `carre` sans accent, restent acceptés. Plusieurs orientations peuvent être séparées par des virgules, par exemple `orientation="portrait,carré"`. Les images sans dimensions sont conservées uniquement avec `orientation="all"`.

Filtrage par tags Piwigo :

`[piwigo album="154" tag="nature"]`
`[piwigo album="154" tags="nature,animaux"]`
`[piwigo album="154" tags="nature,animaux" tag_mode="any"]`
`[piwigo album="154" tags="nature,animaux" tag_mode="all"]`

`tag` et `tags` acceptent un ou plusieurs tags séparés par des virgules. Les espaces sont supprimés, les doublons sont ignorés et la comparaison ne tient pas compte de la casse. `tag_mode` vaut `any` par défaut ou `all` pour exiger tous les tags demandés.

== Frequently Asked Questions ==

= Où le plugin doit-il être installé ? =

WP Piwigo Display est un plugin WordPress. Il ne s'installe pas dans Piwigo.

= Les images sont-elles copiées dans WordPress ? =

Non. Les images restent dans Piwigo et sont affichées via l'API officielle.

= Comment inclure les sous-albums ? =

Ajoutez `recursive="true"` au shortcode. Utilisez `depth` pour limiter le nombre de niveaux parcourus.

== Changelog ==

= 1.11.0 =
* Ajout du filtrage des images par tags Piwigo avec `tag`, `tags` et `tag_mode`.
* Application des tags avant orientation, tri, latest, random, limit et max.
* Ajout des exemples de tags au générateur de shortcodes.
* Préparation des métadonnées de version 1.11.0.

= 1.10.0 =
* Ajout du paramètre `orientation` pour filtrer les images en portrait, paysage ou carré après récupération et avant rendu.
* Conservation des images sans dimensions uniquement avec `orientation="all"`.
* Ajout des exemples d’orientation au générateur de shortcodes.
* Préparation des métadonnées de version 1.10.0.

= 1.9.1 =
* Mise à jour des métadonnées de version pour la publication corrective 1.9.1.
* Conservation de la protection contre le double chargement du plugin.
* Complément du générateur de shortcodes dans l’administration avec des exemples récents et utiles.
* Aucun changement dans le moteur de rendu ni dans l’API Piwigo.

= 1.9.0 =
* Chargement conditionnel des ressources JavaScript selon le type d'affichage.
* Cache mémoire pendant une requête PHP afin d'éviter les appels API identiques.
* Ajout d'une page de diagnostic réservée aux administrateurs avec export TXT.
* Correction de la correspondance entre diapositives et miniatures du diaporama.
* Mutualisation du test de connexion Piwigo et de la déduplication des images.
* Consolidation de la documentation et de l'architecture interne.

= 1.8.0 =
* Ajout du paramètre `style`.
* Nouveau mode d'intégration avec les variables CSS du thème WordPress.
* Ajout des styles `theme`, `default`, `minimal` et `none`.
* Ajout d'un réglage global d'intégration graphique.

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
