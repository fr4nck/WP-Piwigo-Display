# WP Piwigo Display

Plugin WordPress pour afficher des albums Piwigo via l’API officielle, sans copier les images dans la médiathèque WordPress.

> **Version stable publiée : 1.8.0**
>
> **La version 2.0.0 n’a jamais été publiée.** Les travaux qui avaient été engagés pour la V2 ont ensuite été repris et prolongés dans la future V3.
>
> **Développement actuel : 3.0.0 RC**. La V3 est en phase de stabilisation et de recette avant publication.

## Ce que fait WP Piwigo Display

Piwigo reste la source des photos. WordPress se charge uniquement de leur affichage.

Le plugin permet notamment :

- d’afficher des albums Piwigo sans importer les images dans WordPress ;
- d’utiliser une galerie responsive ou un diaporama ;
- d’ouvrir les images dans une lightbox ;
- d’afficher un album et ses sous-albums avec une profondeur configurable ;
- de trier, limiter et filtrer les images ;
- de gérer les légendes et plusieurs styles d’intégration ;
- de sélectionner les albums par identifiant, nom, chemin ou arborescence ;
- d’utiliser un cache WordPress pour limiter les appels à Piwigo ;
- de diagnostiquer la connexion et le comportement du cache.

## État du développement V3

La branche V3 prépare une refonte importante du plugin avec notamment :

- Gutenberg, éditeur classique et composeur d’administration ;
- galerie, slider, Masonry et nouveaux moteurs de rendu ;
- orientations portrait / paysage / carré ;
- tags et récursivité ;
- compte de service Piwigo pour les albums privés autorisés ;
- cache renforcé et mécanismes de résilience ;
- page de diagnostic ;
- suivi de la santé API et du cache (appels API, HIT/MISS, temps de réponse), actuellement intégré dans le chantier RC avant publication ;
- tests automatisés de sécurité, accessibilité, compatibilité PHP et frontend.

La V3 n’est pas encore annoncée comme version stable tant que la recette RC n’est pas terminée.

## Installation de la version stable

La dernière version officiellement publiée est **WP Piwigo Display 1.8.0** et se trouve dans la section **Releases** de GitHub.

1. Télécharger le ZIP de la release stable.
2. Dans WordPress, ouvrir **Extensions > Ajouter une extension**.
3. Téléverser le ZIP puis activer **WP Piwigo Display**.
4. Renseigner l’URL de l’instance Piwigo.
5. Insérer le shortcode `[piwigo]` selon la configuration souhaitée.

## Exemples

```text
[piwigo album="154"]
[piwigo album="154" type="slider"]
[piwigo album="154" recursive="true" depth="2"]
[piwigo album="154" sort="date" order="desc" limit="20"]
```

## Affichage récursif

Le paramètre `recursive="true"` inclut les images de l’album indiqué et celles de ses sous-albums.

Le paramètre `depth` limite la profondeur :

- `depth="0"` : album indiqué uniquement ;
- `depth="1"` : album et enfants directs ;
- `depth="2"` : album, enfants et petits-enfants ;
- `depth="10"` : descendance prise en charge jusqu’à cette profondeur.

## Documentation

La documentation complète se trouve dans le dossier [`docs`](docs/).

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Shortcodes](docs/shortcodes.md)
- [Albums récursifs](docs/albums-recursifs.md)
- [Architecture](docs/architecture.md)
- [Feuille de route](ROADMAP.md)

La documentation V3 est mise à jour au fil de la stabilisation de la Release Candidate. Les documents historiques relatifs à la V2 ne constituent pas une release publiée.

## Licence

GNU GPL v3 ou version ultérieure.
