# WP Piwigo Display

WP Piwigo Display est un plugin **WordPress** permettant d'afficher des albums Piwigo via l'API officielle, sans importer les images dans la médiathèque WordPress.

Piwigo reste la source des photos ; WordPress se charge uniquement de leur affichage.

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
- affichage d'un album et de ses sous-albums ;
- profondeur récursive configurable ;
- pagination automatique des résultats Piwigo ;
- suppression des doublons ;
- tri et limitation des images ;
- presets d'affichage ;
- sélection d'album par identifiant, nom ou chemin.

## Exemples

Album simple :

```text
[piwigo album="154"]
```

Diaporama :

```text
[piwigo album="154" type="slider"]
```

Album et tous ses sous-albums :

```text
[piwigo album="154" recursive="true"]
```

Album et deux niveaux de sous-albums :

```text
[piwigo album="154" recursive="true" depth="2"]
```

Dernières images d'une arborescence :

```text
[piwigo album="154" recursive="true" sort="date" order="desc" limit="20"]
```

Autre galerie Piwigo pour un affichage ponctuel :

```text
[piwigo url="https://autre-galerie.example.org" album="154"]
```

## Affichage récursif

Le paramètre `recursive="true"` inclut les images de l'album indiqué et celles de ses sous-albums.

Le paramètre `depth` limite la profondeur :

- `depth="0"` : album indiqué uniquement ;
- `depth="1"` : album et enfants directs ;
- `depth="2"` : album, enfants et petits-enfants ;
- `depth="10"` : toute la descendance prise en charge par le plugin.

Le mode récursif utilise un cache distinct selon l'album, l'URL Piwigo et la profondeur demandée.

## Documentation

La documentation complète se trouve dans le dossier [`docs`](docs/).

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Shortcodes](docs/shortcodes.md)
- [Albums récursifs](docs/albums-recursifs.md)
- [Architecture](docs/architecture.md)
- [Philosophie](docs/philosophie.md)
- [Feuille de route](ROADMAP.md)

## Licence

GNU GPL v3 ou version ultérieure.


## Gestion des légendes

Le paramètre `caption` contrôle les informations affichées sous les images ou sur le diaporama :

```text
[piwigo album="154" caption="none"]
[piwigo album="154" caption="title"]
[piwigo album="154" caption="description"]
[piwigo album="154" caption="title-description"]
```

La valeur `default` utilise le choix enregistré dans les réglages WordPress :

```text
[piwigo album="154" caption="default"]
```

Le réglage s'applique aux galeries, aux diaporamas et aux légendes de la lightbox.
