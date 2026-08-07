# Shortcodes

Piwigo Display utilise un shortcode unique :

```text
[piwigo]
```

Le paramètre `album` est obligatoire.

## Album

`album` accepte un identifiant numérique, un nom ou un chemin :

```text
[piwigo album="154"]
[piwigo album="Séjour voile"]
[piwigo album="/ALSH/Été 2026/Séjour voile"]
```

L’identifiant numérique reste le choix le plus robuste dans un shortcode écrit manuellement.

`url` permet ponctuellement d’utiliser une autre galerie :

```text
[piwigo url="https://autre-galerie.example.org" album="154"]
```

## Modes d’affichage

Le paramètre `type` détermine la façon dont les photos sont présentées. Les trois modes utilisent les mêmes sources Piwigo et peuvent partager les filtres, légendes et options générales ; le choix dépend surtout de l’usage éditorial recherché.

### Galerie — `gallery`

La galerie est le mode polyvalent. Les images sont présentées dans une grille responsive régulière qui s’adapte à la largeur disponible.

À choisir pour :

- une page d’album classique ;
- présenter un ensemble de photos de façon homogène ;
- une consultation rapide avec plusieurs images visibles simultanément ;
- le choix par défaut lorsqu’aucune mise en scène particulière n’est nécessaire.

```text
[piwigo album="154" type="gallery"]
```

La lightbox peut ensuite permettre d’ouvrir les images en grand sans quitter la page.

### Slider — `slider`

Le slider présente principalement une image à la fois sous forme de diaporama. Il convient davantage à une sélection éditoriale qu’à la consultation rapide d’un grand nombre de photos.

À choisir pour :

- mettre en avant quelques photos fortes ;
- une bannière ou une zone visuelle importante ;
- un diaporama d’actualité ou de séjour ;
- une présentation avec navigation par points ou miniatures ;
- un défilement automatique facultatif.

```text
[piwigo album="154" type="slider" navigation="thumbnails"]
```

Le slider dispose de réglages spécifiques : navigation, autoplay, intervalle, vitesse, transition, direction et dimensions. Les préférences système de réduction des mouvements sont respectées : l’autoplay et les animations sont réduits ou désactivés lorsque nécessaire.

### Masonry — `masonry`

Masonry organise les photos en colonnes en conservant davantage leurs proportions naturelles. Contrairement à une grille régulière, les lignes ne sont pas forcées à avoir la même hauteur.

À choisir pour :

- mélanger agréablement portraits et paysages ;
- obtenir une présentation de type photothèque ou portfolio ;
- afficher beaucoup d’images sans imposer un recadrage visuel uniforme ;
- privilégier la variété des formats originaux.

```text
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
```

Le nombre de colonnes diminue automatiquement sur les écrans plus étroits.

### Quel mode choisir ?

| Besoin | Mode conseillé |
| --- | --- |
| Album photo standard et lisible | `gallery` |
| Mettre quelques photos en avant | `slider` |
| Mélanger portraits et paysages avec leurs proportions | `masonry` |
| Afficher rapidement beaucoup de photos | `gallery` ou `masonry` |
| Diaporama automatique | `slider` |
| Présentation type portfolio / photothèque | `masonry` |

Le mode peut être changé sans modifier l’album Piwigo : seule sa présentation dans WordPress change.

## Masonry

`masonry_columns` définit de 2 à 6 colonnes sur grand écran.

`masonry_gap` définit l’espacement entre les éléments, de 0 à 64 pixels.

```text
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
```

## Sous-albums

`recursive="true"` inclut les sous-albums.

`depth` limite la profondeur :

- `0` : album seul ;
- `1` : enfants directs ;
- `2` : enfants et petits-enfants ;
- `10` : toute la descendance prise en charge.

```text
[piwigo album="154" recursive="true" depth="2"]
```

## Tri et limites

`sort` accepte :

- `manual` ;
- `date` ;
- `name` ;
- `id` ;
- `random`.

`order` accepte `asc` ou `desc`.

`limit` limite le nombre d’images affichées.

```text
[piwigo album="154" sort="date" order="desc" limit="20"]
```

Les anciens paramètres `max`, `latest` et `random` restent pris en charge pour compatibilité, mais `limit`, `sort` et `order` sont préférables.

## Tags

`tag` et `tags` filtrent les images par tags Piwigo.

```text
[piwigo album="154" tag="nature"]
[piwigo album="154" tags="nature,animaux"]
```

`tag_mode` accepte :

- `any` : au moins un tag demandé ;
- `all` : tous les tags demandés.

```text
[piwigo album="154" tags="nature,animaux" tag_mode="all"]
```

## Orientation

`orientation` accepte :

- `all` ;
- `portrait` ;
- `paysage` ;
- `carré`.

Les alias `landscape`, `square` et `carre` restent acceptés.

```text
[piwigo album="154" orientation="portrait"]
[piwigo album="154" orientation="paysage,carré"]
```

## Légendes

`caption` accepte :

- `default` ;
- `none` ;
- `title` ;
- `description` ;
- `title-description`.

```text
[piwigo album="154" caption="title-description"]
```

## Style

`style` accepte :

- `default` ;
- `theme` ;
- `minimal` ;
- `none`.

```text
[piwigo album="154" style="theme"]
```

## Lightbox et forme

`lightbox="false"` désactive la lightbox.

`rounded="true"` active les angles arrondis lorsque le style le permet.

```text
[piwigo album="154" lightbox="false" rounded="true"]
```

## Ajustement des images

`fit` accepte :

- `contain` : photo entière sans recadrage ;
- `cover` : cadre rempli avec recadrage possible ;
- `auto` : choix automatique selon l’orientation ;
- `raw` : respect maximal des dimensions naturelles.

```text
[piwigo album="154" fit="contain"]
```

## Slider

### Navigation

`navigation` accepte :

- `thumbnails` ;
- `dots` ;
- `none`.

```text
[piwigo album="154" type="slider" navigation="thumbnails"]
```

### Autoplay, intervalle et vitesse

`autoplay` active ou désactive le défilement automatique.

`interval` est le temps entre deux images, en millisecondes.

`speed` est la durée de transition, en millisecondes.

```text
[piwigo album="154" type="slider" autoplay="true" interval="5000" speed="500"]
```

`interval` et `speed` sont indépendants.

### Transition

`transition` accepte :

- `slide` : déplacement horizontal classique ;
- `fade` : fondu entre les images ;
- `none` : changement immédiat, sans animation de transition.

```text
[piwigo album="154" type="slider" transition="fade" speed="700"]
```

### Direction

`direction` accepte :

- `ltr` : progression de gauche à droite ;
- `rtl` : progression de droite à gauche.

```text
[piwigo album="154" type="slider" transition="slide" direction="rtl"]
```

### Dimensions

`width`, `height`, `ratio` et `align` règlent la mise en page.

```text
[piwigo album="154" type="slider" width="72%" height="480px" ratio="16/9" align="center"]
```

`align` accepte `left`, `center` ou `right`.

La largeur publique est plafonnée à 100 % et s’adapte aux petits écrans.

## Accessibilité du slider

Lorsque l’utilisateur a activé `prefers-reduced-motion: reduce` dans son système, Piwigo Display désactive l’autoplay et réduit ou supprime les transitions animées.

## Presets

`preset` accepte notamment :

- `galerie` ;
- `slider` ;
- `actualites`.

```text
[piwigo album="154" preset="actualites"]
```

## Exemples complets

Galerie de 20 dernières images dans une arborescence :

```text
[piwigo album="154" type="gallery" recursive="true" depth="2" sort="date" order="desc" limit="20"]
```

Slider avec fondu, miniatures et légende :

```text
[piwigo album="154" type="slider" transition="fade" navigation="thumbnails" caption="title"]
```

Masonry filtré par orientation :

```text
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16" orientation="paysage"]
```

Album filtré par tags :

```text
[piwigo album="154" tags="nature,animaux" tag_mode="all" style="theme"]
```
