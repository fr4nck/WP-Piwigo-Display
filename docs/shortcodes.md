# Shortcodes

Le plugin utilise un shortcode unique :

```text
[piwigo]
```

Le paramètre `album` est obligatoire.

## Paramètres principaux

### `album`

Accepte un identifiant numérique, un nom ou un chemin.

```text
[piwigo album="154"]
[piwigo album="Séjour voile"]
[piwigo album="/ALSH/Été 2026/Séjour voile"]
```

L'identifiant numérique reste le choix le plus fiable.

### `type`

- `gallery` : galerie responsive ;
- `slider` : diaporama.

```text
[piwigo album="154" type="slider"]
```

### `preset`

- `galerie` ;
- `slider` ;
- `actualites`.

```text
[piwigo album="154" preset="actualites"]
```

### `recursive`

Inclut les images des sous-albums.

```text
[piwigo album="154" recursive="true"]
```

### `depth`

Limite le nombre de niveaux parcourus.

```text
[piwigo album="154" recursive="true" depth="2"]
```

- `0` : album seul ;
- `1` : enfants directs ;
- `2` : enfants et petits-enfants ;
- `10` : toute la descendance prise en charge.

### `sort`

- `manual` ;
- `date` ;
- `name` ;
- `id` ;
- `random`.

```text
[piwigo album="154" sort="date"]
```

### `order`

- `asc` ;
- `desc`.

```text
[piwigo album="154" sort="date" order="desc"]
```

### `limit`

Limite le nombre d'images affichées.

```text
[piwigo album="154" limit="20"]
```

### `navigation`

Navigation du diaporama :

- `thumbnails` ;
- `dots` ;
- `none`.

```text
[piwigo album="154" type="slider" navigation="thumbnails"]
```

### `autoplay`

Active ou désactive le défilement automatique.

```text
[piwigo album="154" type="slider" autoplay="false"]
```

### `interval`

Temps entre deux images, en millisecondes.

```text
[piwigo album="154" interval="5000"]
```

### `speed`

Durée de la transition, en millisecondes.

```text
[piwigo album="154" speed="400"]
```

### `fit`

- `contain` : photo entière sans recadrage ;
- `cover` : cadre rempli avec recadrage possible ;
- `auto` : choix selon l'orientation ;
- `raw` : respect maximal des dimensions naturelles.

```text
[piwigo album="154" fit="contain"]
```

### `height`

```text
[piwigo album="154" type="slider" height="450px"]
```

### `ratio`

```text
[piwigo album="154" type="slider" ratio="16/9"]
```

### `rounded`

```text
[piwigo album="154" rounded="true"]
```

### `lightbox`

```text
[piwigo album="154" lightbox="false"]
```

### `url`

Utilise ponctuellement une autre galerie Piwigo.

```text
[piwigo url="https://autre-galerie.example.org" album="154"]
```

## Compatibilité conservée

Les anciens paramètres `max`, `latest` et `random` restent pris en charge, mais `limit`, `sort` et `order` sont préférables pour les nouveaux shortcodes.

## Exemples complets

20 dernières images de toute une arborescence :

```text
[piwigo album="154" recursive="true" sort="date" order="desc" limit="20"]
```

Diaporama récursif avec miniatures :

```text
[piwigo album="154" recursive="true" type="slider" navigation="thumbnails"]
```


### `caption`

Contrôle l'affichage des légendes :

- `default` : réglage global ;
- `none` : aucune légende ;
- `title` : titre Piwigo ;
- `description` : description Piwigo ;
- `title-description` : titre et description.

```text
[piwigo album="154" caption="none"]
[piwigo album="154" type="slider" caption="title"]
[piwigo album="154" caption="title-description"]
```


### `style`

Contrôle l'intégration visuelle :

- `default` : réglage global ;
- `theme` : variables CSS du thème WordPress ;
- `minimal` : style léger ;
- `none` : sans habillage graphique.

```text
[piwigo album="154" style="theme"]
[piwigo album="154" style="none"]
```
