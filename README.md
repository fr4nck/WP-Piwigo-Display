# WP Piwigo Display

Plugin WordPress léger permettant d'afficher des albums Piwigo via l'API officielle.

## État du développement

Version actuelle : **0.6.0**

Fonctionnalités disponibles :

```text
[piwigo album="154"]
[piwigo album="154" type="gallery"]
[piwigo album="154" max="30"]
[piwigo album="154" fit="contain" height="220px"]
```

À ce stade :

* le shortcode est reconnu par WordPress ;
* une page de réglages est disponible dans **Réglages > WP Piwigo Display** ;
* l'URL de la galerie Piwigo peut être configurée ;
* le plugin interroge l'API Piwigo ;
* les résultats sont mis en cache avec les transients WordPress ;
* les images sont affichées dans une galerie responsive ;
* le moteur de rendu prépare les futurs modes `slider`, `carousel` et `masonry`.

## Licence

Ce projet est distribué sous licence **GNU General Public License v3.0 (GPL-3.0)**.
