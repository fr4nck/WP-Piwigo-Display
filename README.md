# WP Piwigo Display

Plugin WordPress permettant d'afficher des albums Piwigo via l'API officielle, sans importer les images dans la médiathèque WordPress.

## Objectif

WP Piwigo Display sert à afficher dynamiquement des photos gérées dans Piwigo depuis un site WordPress.

Piwigo reste la source des photos. WordPress affiche le contenu.

## Fonctionnalités

- shortcode unique `[piwigo]` ;
- galerie responsive ;
- diaporama avec Splide ;
- lightbox ;
- cache WordPress avec transients ;
- vidage manuel du cache ;
- test de connexion Piwigo ;
- réglages d'affichage par défaut ;
- navigation du diaporama par miniatures, points ou aucune ;
- URL Piwigo ponctuelle dans un shortcode ;
- affichage récursif des sous-albums ;
- tri et limitation des images ;
- presets d'affichage ;
- sélection d'album par identifiant, nom ou chemin.

## Exemples

```text
[piwigo album="154"]
[piwigo album="154" type="slider"]
[piwigo album="154" preset="galerie"]
[piwigo album="154" preset="slider"]
[piwigo album="154" preset="actualites"]
[piwigo album="Séjour voile"]
[piwigo album="/ALSH/Été 2026/Séjour voile"]
[piwigo album="154" sort="date" order="desc" limit="20"]
[piwigo album="154" recursive="true" depth="2"]
[piwigo url="https://autre-galerie.example.org" album="154"]
```

## Documentation

La documentation est disponible dans le dossier `docs`.

## Licence

GNU GPL v3 ou version ultérieure.
