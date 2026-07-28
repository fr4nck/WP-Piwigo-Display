=== WP Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Affiche des albums Piwigo publics ou privés dans WordPress via l’API officielle.

== Description ==

WP Piwigo Display conserve les images dans Piwigo et génère leur affichage dans WordPress.

Fonctionnalités principales :

* bloc Gutenberg dynamique ;
* éditeur classique avec aperçu TinyMCE ;
* composeur d’administration ;
* galerie responsive, diaporama et lightbox ;
* redimensionnement visuel des diaporamas ;
* sélection d’album par identifiant, nom, chemin ou arborescence ;
* sous-albums et profondeur configurable ;
* tri, limites, orientations, tags, légendes et styles ;
* cache WordPress et diagnostic ;
* compte de service Piwigo côté serveur pour les albums privés autorisés.

Le compte de service ne connecte pas les visiteurs à Piwigo. Un album privé publié sur une page publique WordPress devient toutefois visible sur cette page.

== Installation ==

1. Téléverser le ZIP depuis Extensions > Ajouter une extension.
2. Activer WP Piwigo Display.
3. Renseigner l’URL HTTPS de Piwigo dans les réglages.
4. Insérer le bloc WP Piwigo Display ou utiliser `[piwigo album="154"]`.
5. Pour les albums privés, créer un compte Piwigo dédié et configurer le compte de service dans les réglages ou dans wp-config.php.

== Shortcodes ==

`[piwigo album="154"]`

`[piwigo album="154" type="slider" width="72%" height="480px"]`

`[piwigo album="154" recursive="true" depth="2"]`

`[piwigo album="154" sort="date" order="desc" limit="20"]`

`[piwigo album="154" tags="nature,animaux" tag_mode="all"]`

== Frequently Asked Questions ==

= Les images sont-elles copiées dans WordPress ? =

Non. Elles restent dans Piwigo.

= Comment afficher un album privé ? =

Créez un compte Piwigo dédié, limitez ses droits aux albums à publier, puis activez le compte de service dans WordPress. HTTPS est obligatoire.

= Les visiteurs voient-ils les identifiants Piwigo ? =

Non. L’authentification et les cookies de session restent côté serveur.

== Changelog ==

= 2.0.0 =

* Ajout du compte de service Piwigo pour les albums privés autorisés.
* Authentification et cookies de session limités aux requêtes serveur.
* Séparation des caches anonyme et authentifié.
* Sélecteur d’albums avec recherche et arborescence.
* Redimensionnement visuel des diaporamas dans Gutenberg.
* Compatibilité Gutenberg, éditeur classique et composeur.
* CI PHP 8.1 à 8.4 et génération automatique du ZIP installable.
