# WP Piwigo Display

Plugin WordPress permettant d'afficher simplement une galerie Piwigo grâce à l'API officielle.

WP Piwigo Display permet d'intégrer facilement une photothèque Piwigo dans un site WordPress, sans importer les images dans la médiathèque.

Le plugin interroge directement l'API de Piwigo, met les résultats en cache grâce aux transients WordPress et génère automatiquement l'affichage.

---

## Objectifs

Le projet privilégie :

- la simplicité ;
- les performances ;
- une architecture modulaire ;
- le respect des standards WordPress ;
- le respect des photographies ;
- un minimum de configuration.

Le développement est réalisé progressivement afin de conserver un code lisible et facilement maintenable.

---

## Fonctionnalités actuelles

- Connexion à l'API officielle de Piwigo.
- Affichage d'un album via un shortcode.
- Galerie responsive.
- Diaporama.
- Lightbox.
- Mise en cache avec les transients WordPress.
- Chargement différé des images.
- Interface de configuration simple.

---

## Exemple

Afficher un album :

```text
[piwigo album="154"]
```

Afficher une galerie :

```text
[piwigo album="154" type="gallery"]
```

Afficher un diaporama :

```text
[piwigo album="154" type="slider"]
```

---

## Philosophie

WP Piwigo Display ne cherche pas à remplacer Piwigo.

Piwigo reste le gestionnaire de photothèque.

WordPress devient simplement un moyen élégant de présenter son contenu.

Une attention particulière est portée au respect des photographies. Le plugin ne doit jamais modifier ou dénaturer une image sans que le webmaster l'ait explicitement demandé.

---

## Documentation

La documentation complète est disponible dans le dossier **docs**.

- Installation
- Configuration
- Utilisation des shortcodes
- Architecture du plugin
- Philosophie du projet
- Feuille de route

---

## Feuille de route

Les prochaines évolutions prévues sont notamment :

- intégration complète de Splide ;
- affichage récursif des sous-albums ;
- affichage par tags ;
- mode Masonry ;
- optimisation du cache ;
- bloc Gutenberg.

---

## Licence

Ce projet est distribué sous licence **GNU General Public License v3.0 (GPL-3.0)**.
