# WP Piwigo Display

Plugin WordPress pour afficher des albums Piwigo via l’API officielle, sans copier les images dans la médiathèque WordPress.

## État du projet

La branche `3.x-dev` prépare **3.0.0-rc.2**, version corrective de la Release Candidate V3. Elle corrige l’incompatibilité PHP 8.1 entre le filtre de rendu Masonry et la valeur initiale `null` utilisée par le moteur de rendu, ainsi que les chemins CSS et JavaScript incorrectement réécrits dans le premier paquet de test.

La version 2.0.0 reste la dernière version stable distribuée tant que cette RC n’a pas terminé sa recette fonctionnelle réelle.

## Fonctionnalités principales

- bloc Gutenberg dynamique ;
- intégration Classic Editor avec aperçu TinyMCE ;
- composeur d’administration ;
- parité fonctionnelle entre Gutenberg, Classic Editor et le composeur ;
- galerie responsive ;
- diaporama Splide ;
- Masonry natif basé sur les colonnes CSS ;
- lightbox ;
- sélection d’album par identifiant, nom, chemin ou arborescence ;
- sélecteur visuel hiérarchique et recherchable ;
- sous-albums et profondeur configurable ;
- tri, limites, orientations, tags, légendes et styles ;
- transitions de slider `slide`, `fade` et `none` ;
- direction `ltr` ou `rtl` ;
- largeur, hauteur, ratio, vitesse et intervalle configurables ;
- cache WordPress séparé par contexte d’accès ;
- diagnostic et purge du cache ;
- compte de service Piwigo pour publier côté WordPress des albums privés autorisés ;
- navigation clavier renforcée, focus visible et réduction des animations lorsque `prefers-reduced-motion` est activé.

## Installation

1. Installer le ZIP depuis **Extensions > Ajouter une extension > Téléverser une extension**.
2. Activer **WP Piwigo Display**.
3. Ouvrir les réglages du plugin et renseigner l’URL HTTPS de Piwigo.
4. Tester la connexion.
5. Insérer le bloc Gutenberg, utiliser le composeur ou saisir un shortcode tel que `[piwigo album="154"]`.

Pour les albums privés, configurer un compte de service Piwigo dédié et limité aux seuls albums destinés à être publiés sur WordPress.

## Compte de service Piwigo

Le compte de service est un compte Piwigo dédié à WordPress. Il permet au serveur WordPress de récupérer les albums privés auxquels ce compte a accès. Les visiteurs ne se connectent pas à Piwigo.

Une photo privée affichée sur une page publique WordPress devient publiquement consultable via cette page. Le compte doit donc être limité aux seuls albums destinés à cette diffusion.

Configuration recommandée dans `wp-config.php` :

```php
define('WPD_PIWIGO_SERVICE_ENABLED', true);
define('WPD_PIWIGO_SERVICE_USERNAME', 'wordpress-publication');
define('WPD_PIWIGO_SERVICE_PASSWORD', 'mot-de-passe-fort');
```

Les identifiants restent côté serveur et ne sont pas insérés dans le HTML, JavaScript, les blocs ou les shortcodes.

## Exemples

```text
[piwigo album="154"]
[piwigo album="154" type="slider" width="72%" height="480px"]
[piwigo album="154" type="slider" transition="fade" speed="700"]
[piwigo album="154" type="slider" transition="slide" direction="rtl"]
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
[piwigo album="154" recursive="true" depth="2"]
[piwigo album="154" sort="date" order="desc" limit="20"]
[piwigo album="154" tags="nature,animaux" tag_mode="all"]
```

## Masonry

Le mode Masonry utilise les colonnes CSS natives du navigateur :

- `type="masonry"` active la disposition ;
- `masonry_columns="4"` définit de 2 à 6 colonnes sur grand écran ;
- `masonry_gap="16"` définit un espacement de 0 à 64 pixels ;
- le nombre de colonnes diminue automatiquement sur tablette et mobile ;
- lightbox, légendes, styles et albums privés restent compatibles.

## Slider

La durée d’affichage (`interval`) et la vitesse de transition (`speed`) sont deux réglages indépendants.

Les transitions disponibles sont `slide`, `fade` et `none`. La direction peut être `ltr` ou `rtl`.

Lorsque l’utilisateur demande une réduction des animations via son système (`prefers-reduced-motion`), l’autoplay est neutralisé et les transitions sont supprimées ou réduites.

## Compatibilité

- WordPress 6.0 ou supérieur ;
- PHP 8.1 à 8.4 validé par CI ;
- Piwigo accessible en HTTPS pour le compte de service.

## Documentation

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Shortcodes](docs/shortcodes.md)
- [Compte de service](docs/COMPTE-DE-SERVICE.md)
- [Parité des composeurs](docs/PARITE-COMPOSEURS.md)
- [Architecture](docs/architecture.md)
- [Feuille de route](ROADMAP.md)

## Licence

GNU GPL v3 ou version ultérieure.
