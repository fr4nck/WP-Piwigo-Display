=== Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, galerie, photos, shortcode, diaporama
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 3.0.0-rc.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Affiche vos albums Piwigo dans votre site sans copier les photos dans la médiathèque.

== Description ==

Piwigo Display est l’extension WordPress de la famille Piwigo Display. Elle conserve les images dans Piwigo et les affiche dans votre site sans les copier dans la médiathèque.

Le nom complet du projet reste **Piwigo Display pour WordPress**. Pour satisfaire les contraintes du répertoire WordPress.org, le nom déclaré dans les métadonnées distribuées est simplement **Piwigo Display**. Il s’agit toujours du même plugin, historiquement nommé **WP Piwigo Display** ; son fonctionnement, son historique, son slug `wp-piwigo-display`, son domaine de traduction et le dépôt `WP-Piwigo-Display` restent inchangés.

La version 3.0.0-rc.1 est la première Release Candidate de la branche 3.x. Elle comprend notamment :

* un bloc Gutenberg dynamique ;
* une intégration à l’éditeur classique avec aperçu TinyMCE ;
* un composeur de galerie dans l’administration ;
* la parité fonctionnelle entre Gutenberg, l’éditeur classique et le composeur d’administration ;
* des galeries responsives, diaporamas Splide, lightbox et affichage Masonry en colonnes CSS ;
* un sélecteur visuel, hiérarchique et recherchable des albums ;
* la sélection d’un album par identifiant, nom, chemin ou arborescence ;
* les sous-albums et une profondeur configurable ;
* tris, limites, filtres d’orientation, tags, légendes et styles visuels ;
* les transitions de diaporama `slide`, `fade` et `none`, avec direction `ltr` / `rtl` ;
* des réglages distincts pour la durée d’affichage et la vitesse de transition ;
* le dimensionnement visuel des diaporamas ;
* un cache WordPress séparé selon le contexte d’accès ;
* diagnostic et purge du cache ;
* un compte de service Piwigo côté serveur pour les albums privés autorisés ;
* navigation clavier, focus visible et prise en charge de la préférence de réduction des animations.

Le compte de service ne connecte pas les visiteurs à Piwigo. Un album privé affiché sur une page publique devient visible sur cette page : le compte dédié doit donc être limité aux seuls albums destinés à être publiés.

== Installation ==

1. Dans l’administration, ouvrez Extensions > Ajouter une extension > Téléverser une extension.
2. Sélectionnez le fichier ZIP de Piwigo Display pour WordPress puis activez le plugin.
3. Ouvrez les réglages du plugin et renseignez l’adresse HTTPS de votre installation Piwigo.
4. Testez la connexion.
5. Insérez le bloc Piwigo Display, utilisez le composeur d’administration ou un shortcode tel que `[piwigo album="154"]`.
6. Pour les albums privés, configurez un compte Piwigo dédié et limité aux albums pouvant être publiés.

== Shortcodes ==

Galerie simple :

`[piwigo album="154"]`

Diaporama :

`[piwigo album="154" type="slider" width="72%" height="480px"]`

Diaporama avec fondu :

`[piwigo album="154" type="slider" transition="fade" speed="700"]`

Diaporama de droite à gauche :

`[piwigo album="154" type="slider" transition="slide" direction="rtl"]`

Masonry :

`[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]`

Sous-albums récursifs :

`[piwigo album="154" recursive="true" depth="2"]`

Tri et limite :

`[piwigo album="154" sort="date" order="desc" limit="20"]`

Tags :

`[piwigo album="154" tags="nature,animaux" tag_mode="all"]`

== Questions fréquentes ==

= Les images sont-elles copiées dans la médiathèque ? =

Non. Elles restent stockées dans Piwigo.

= Comment afficher un album privé ? =

Créez un compte Piwigo dédié, limitez-le aux albums pouvant être publiés puis activez le compte de service dans les réglages. HTTPS est requis.

= Les visiteurs peuvent-ils voir les identifiants Piwigo ? =

Non. L’authentification et les cookies de session restent côté serveur.

= Le diaporama respecte-t-il la préférence de réduction des animations ? =

Oui. Lorsque le système demande une réduction des animations, la lecture automatique est désactivée et les transitions sont supprimées ou réduites.

== Changelog ==

= 3.0.0-rc.1 =

* Première Release Candidate de la branche 3.x.
* Refonte de l’architecture du plugin pour la branche 3.x.
* Ajout du Masonry CSS avec colonnes et espacements configurables.
* Ajout des transitions `slide`, `fade` et `none`.
* Ajout des directions `ltr` et `rtl` pour les diaporamas.
* Amélioration de la parité entre Gutenberg, l’éditeur classique et le composeur d’administration.
* Amélioration du sélecteur hiérarchique d’albums et de la navigation clavier.
* Prise en charge de la réduction des animations.
* Renforcement des actions privilégiées, requêtes HTTP, validation des URL du compte de service et autres invariants de sécurité.
* Ajout de contrôles de régression d’accessibilité et de sécurité au workflow CI unique.
* Compatibilité PHP 8.1 à PHP 8.4 et validation Plugin Check conservées dans la CI.

= 2.0.0 =

* Ajout d’un compte de service Piwigo pour les albums privés autorisés.
* Authentification et cookies de session conservés côté serveur.
* Séparation des caches anonymes et authentifiés.
* Ajout de la recherche et de la sélection arborescente des albums.
* Ajout du redimensionnement visuel des diaporamas dans Gutenberg.
* Maintien de la parité entre Gutenberg, l’éditeur classique et le composeur d’administration.
* Ajout de la CI PHP 8.1 à PHP 8.4 et de la génération automatique d’un ZIP installable.
