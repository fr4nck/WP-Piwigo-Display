# WP Piwigo Display

Plugin WordPress léger permettant d'afficher des albums Piwigo via l'API officielle.

## Utilisation

```text
[piwigo album="154"]
[piwigo album="154" type="gallery"]
[piwigo album="154" type="slider"]
[piwigo album="154" type="slider" thumbnails="true"]
[piwigo album="154" type="slider" thumbnails="false"]
[piwigo album="154" type="slider" autoplay="true" interval="5000" ratio="16/9" fit="auto"]
[piwigo album="154" type="slider" height="520px" fit="contain"]
[piwigo album="154" type="gallery" max="30" fit="contain" height="220px"]
[piwigo album="154" random="12"]
[piwigo album="154" latest="20"]
[piwigo album="154" lightbox="false"]
[piwigo album="154" rounded="true"]
```

## Fonctionnalités

* Shortcode unique.
* Page de réglages dans **Réglages > WP Piwigo Display**.
* Connexion à l'API Piwigo.
* Cache avec les transients WordPress.
* Galerie responsive.
* Diaporama local sans CDN.
* Miniatures optionnelles dans le diaporama.
* Lightbox maison sans dépendance externe.
* Options `max`, `latest`, `random`, `fit`, `height`, `ratio`, `rounded`, `lightbox`, `thumbnails`.
* Mode `fit="auto"` par défaut : les portraits ne sont pas coupés.
* Images non importées dans la médiathèque WordPress.

## Licence

Ce projet est distribué sous licence **GNU General Public License v3.0 (GPL-3.0)**.
