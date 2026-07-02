# WP Piwigo Display

Plugin WordPress léger permettant d'afficher des albums Piwigo via l'API officielle.

## État du développement

Version actuelle : **0.7.0**

Fonctionnalités disponibles :

```text
[piwigo album="154"]
[piwigo album="154" type="gallery"]
[piwigo album="154" type="slider"]
[piwigo album="154" type="slider" autoplay="true" interval="5000" height="420px" fit="cover"]
[piwigo album="154" type="gallery" max="30" fit="contain" height="220px"]
```

À ce stade :

* le shortcode est reconnu par WordPress ;
* une page de réglages est disponible dans **Réglages > WP Piwigo Display** ;
* le plugin interroge l'API Piwigo ;
* les résultats sont mis en cache avec les transients WordPress ;
* les images peuvent être affichées en galerie responsive ;
* les images peuvent être affichées en diaporama Splide.

## Licence

Ce projet est distribué sous licence **GNU General Public License v3.0 (GPL-3.0)**.
