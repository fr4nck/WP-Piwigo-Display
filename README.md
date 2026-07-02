# WP Piwigo Display

Plugin WordPress léger permettant d'afficher des albums Piwigo via l'API officielle.

## Objectif

WP Piwigo Display permet d'intégrer facilement une galerie Piwigo dans un site WordPress, sans importer les images dans la médiathèque.

Le plugin interroge directement l'API de Piwigo, met les résultats en cache grâce aux transients WordPress et génère automatiquement l'affichage.

## État du développement

Version actuelle : **0.4.0**

Fonctionnalités disponibles :

```text
[piwigo album="154"]
```

À ce stade :

* le shortcode est reconnu par WordPress ;
* une page de réglages est disponible dans **Réglages > WP Piwigo Display** ;
* l'URL de la galerie Piwigo peut être configurée ;
* le plugin interroge l'API Piwigo ;
* le rendu affiche temporairement le nombre d'images et leur nom.

## Philosophie

Le projet privilégie :

* la simplicité ;
* les performances ;
* une architecture modulaire ;
* un minimum de configuration ;
* les standards de développement WordPress.

Le développement est réalisé progressivement, chaque version correspondant à une étape fonctionnelle.

## Licence

Ce projet est distribué sous licence **GNU General Public License v3.0 (GPL-3.0)**.
