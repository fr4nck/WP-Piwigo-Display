# WP Piwigo Display

Plugin WordPress pour afficher des albums Piwigo via l’API officielle, sans copier les images dans la médiathèque WordPress.

## Version 2.0.0

- bloc Gutenberg dynamique ;
- éditeur classique avec aperçu TinyMCE ;
- composeur d’administration ;
- galerie responsive et diaporama Splide ;
- redimensionnement visuel des diaporamas dans Gutenberg ;
- sélection d’album par identifiant, nom, chemin ou arborescence ;
- sous-albums et profondeur configurable ;
- tri, limites, orientations, tags, légendes et styles ;
- cache WordPress séparé par contexte d’accès ;
- diagnostic et purge du cache ;
- compte de service Piwigo pour publier, côté WordPress, des albums privés autorisés.

## Installation

1. Installer le ZIP depuis **Extensions > Ajouter une extension**.
2. Activer **WP Piwigo Display**.
3. Renseigner l’URL HTTPS de Piwigo dans les réglages du plugin.
4. Insérer le bloc Gutenberg ou utiliser `[piwigo album="154"]`.

## Compte de service Piwigo

Le compte de service est un compte Piwigo dédié à WordPress. Il permet au serveur WordPress de récupérer les albums privés auxquels ce compte a accès. Les visiteurs ne se connectent pas à Piwigo.

Les photos d’un album privé affiché sur une page publique WordPress deviennent publiquement consultables sur cette page. Le compte doit donc être limité aux seuls albums destinés à cette diffusion.

Configuration recommandée dans `wp-config.php` :

```php
define('WPD_PIWIGO_SERVICE_ENABLED', true);
define('WPD_PIWIGO_SERVICE_USERNAME', 'wordpress-publication');
define('WPD_PIWIGO_SERVICE_PASSWORD', 'mot-de-passe-fort');
```

Les identifiants restent côté serveur. Ils ne sont pas insérés dans le HTML, JavaScript, les blocs ou les shortcodes.

## Exemples

```text
[piwigo album="154"]
[piwigo album="154" type="slider" width="72%" height="480px"]
[piwigo album="154" recursive="true" depth="2"]
[piwigo album="154" sort="date" order="desc" limit="20"]
[piwigo album="154" tags="nature,animaux" tag_mode="all"]
```

## Compatibilité

- WordPress 6.0 ou supérieur ;
- PHP 8.1 à 8.4 validé par CI ;
- Piwigo accessible en HTTPS pour le compte de service.

## Documentation

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Shortcodes](docs/shortcodes.md)
- [Compte de service](docs/COMPTE-DE-SERVICE.md)
- [Recette V2](docs/RECETTE-V2.md)
- [Architecture](docs/architecture.md)
- [Feuille de route](ROADMAP.md)

## Licence

GNU GPL v3 ou version ultérieure.
