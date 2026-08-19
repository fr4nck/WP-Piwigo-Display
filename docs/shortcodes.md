# Shortcodes

Piwigo Display utilise un shortcode unique :

```text
[piwigo]
```

Le paramètre `album` est obligatoire. Les interfaces Gutenberg, Classic Editor et le composeur d’administration sont recommandées pour construire visuellement les réglages ; le shortcode reste l’interface avancée et portable.

## Album

`album` accepte un identifiant numérique, un nom ou un chemin :

```text
[piwigo album="154"]
[piwigo album="Séjour voile"]
[piwigo album="/ALSH/Été 2026/Séjour voile"]
```

`url` permet ponctuellement d’utiliser une autre galerie Piwigo :

```text
[piwigo url="https://autre-galerie.example.org" album="154"]
```

## Modes d’affichage

`type` accepte notamment :

- `gallery` : grille responsive classique ;
- `slider` : diaporama Splide ;
- `masonry` : colonnes CSS ;
- `justified` : lignes justifiées ;
- `collage` : Pêle-mêle déterministe ;
- `photo-text` : texte rempli de photos.

### Galerie

```text
[piwigo album="154" type="gallery"]
```

### Slider

```text
[piwigo album="154" type="slider" navigation="thumbnails" autoplay="true" interval="5000" speed="500" transition="fade"]
```

Réglages principaux :

- `navigation` : `thumbnails`, `dots`, `none` ;
- `autoplay` : `true` / `false` ;
- `interval` : temps entre deux images en millisecondes ;
- `speed` : durée de transition en millisecondes ;
- `transition` : `slide`, `fade`, `none` ;
- `direction` : `ltr`, `rtl` ;
- `width`, `height`, `ratio`, `align`.

Le slider possède un fallback natif si Splide tarde à se charger ou n’est pas disponible.

### Masonry

```text
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
```

- `masonry_columns` : 2 à 6 ;
- `masonry_gap` : 0 à 64 pixels.

### Justified Gallery

```text
[piwigo album="154" type="justified" justified_row_height="220" justified_gap="8"]
```

- `justified_row_height` : hauteur cible des lignes ;
- `justified_gap` : espacement entre images.

Le moteur conserve les proportions d’origine et prévoit un repli lorsque les dimensions Piwigo manquent.

### Collage / Pêle-mêle

```text
[piwigo album="154" type="collage" collage_seed="2026" collage_rotation="6" collage_spread="18" collage_overlap="12"]
```

Réglages :

- `collage_seed` : graine déterministe ;
- `collage_rotation` : rotation maximale ;
- `collage_spread` : dispersion ;
- `collage_overlap` : chevauchement ;
- `collage_size` : taille moyenne ;
- `collage_variation` : variation de taille.

Même graine + mêmes images = même composition.

### Texte rempli de photos

```text
[piwigo album="154" type="photo-text" photo_text="ÉTÉ 2026" photo_text_font="bundled-bebas-neue" photo_text_fill_mode="collage"]
```

Réglages principaux :

- `photo_text` : texte ; `\n` permet plusieurs lignes ;
- `photo_text_seed` : graine déterministe ;
- `photo_text_font` : police ;
- `photo_text_weight` : graisse ;
- `photo_text_size` : taille ;
- `photo_text_letter_spacing` : interlettrage ;
- `photo_text_line_height` : hauteur de ligne ;
- `photo_text_max_width` : largeur maximale ;
- `photo_text_align` : `left`, `center`, `right` ;
- `photo_text_fill_mode` : `grid`, `masonry`, `collage` ;
- `photo_text_density` : densité du remplissage ;
- `photo_text_rotation` : rotation du remplissage pêle-mêle ;
- `photo_text_spread` : dispersion du remplissage pêle-mêle ;
- `photo_text_max_images` : nombre maximal de photos source ;
- `photo_text_outline` : contour activé/désactivé ;
- `photo_text_outline_width` : épaisseur du contour ;
- `photo_text_outline_color` : couleur hexadécimale ;
- `photo_text_background` : `transparent` ou couleur hexadécimale.

Polices disponibles sans réseau :

- `inherit` : police du thème ;
- `system` ;
- `serif` ;
- `mono` ;
- `bundled-bebas-neue` ;
- `bundled-bungee` ;
- `user-<identifiant>` pour une police WOFF2/WOFF importée depuis la bibliothèque Piwigo Display.

Aucune police tierce distante n’est chargée automatiquement.

## Sous-albums

`recursive="true"` inclut les sous-albums. `depth` limite la profondeur.

```text
[piwigo album="154" recursive="true" depth="2"]
```

## Tri et limites

`sort` accepte `manual`, `date`, `name`, `id`, `random`.

`order` accepte `asc` ou `desc`.

`limit` limite le nombre d’images affichées.

```text
[piwigo album="154" sort="date" order="desc" limit="20"]
```

Les anciens paramètres `max`, `latest` et `random` restent pris en charge pour compatibilité.

## Tags

```text
[piwigo album="154" tag="nature"]
[piwigo album="154" tags="nature,animaux" tag_mode="all"]
```

`tag_mode` accepte `any` ou `all`.

## Orientation

`orientation` accepte `all`, `portrait`, `paysage`, `carré`. Les alias `landscape`, `square` et `carre` restent acceptés.

```text
[piwigo album="154" orientation="portrait"]
```

## Légendes

`caption` accepte `default`, `none`, `title`, `description`, `title-description`.

```text
[piwigo album="154" caption="title-description"]
```

## Style et cadrage

`style` accepte `default`, `theme`, `minimal`, `none`.

`fit` accepte `contain`, `cover`, `auto`, `raw`.

```text
[piwigo album="154" style="theme" fit="contain"]
```

## Lightbox et formes

`lightbox="false"` désactive la lightbox.

`shape` accepte les formes intégrées documentées dans `docs/FORMES.md`, par exemple :

```text
[piwigo album="154" shape="circle"]
[piwigo album="154" type="collage" shape="cloud"]
```

Un masque utilisateur sanitizé est représenté par `shape="custom-<identifiant>"` ; il est préférable de le choisir dans l’interface visuelle.

`radius` règle l’arrondi pour `shape="rounded"`. L’ancien `rounded="true"` reste compatible.

## Accessibilité

`prefers-reduced-motion` réduit ou désactive l’autoplay et les animations concernées.

Le mode Texte rempli de photos conserve le texte sous forme sémantique pour les technologies d’assistance ; son SVG décoratif n’est pas exposé au lecteur d’écran.

## Exemples complets

Galerie récursive récente :

```text
[piwigo album="154" type="gallery" recursive="true" depth="2" sort="date" order="desc" limit="20"]
```

Justified avec légende :

```text
[piwigo album="154" type="justified" justified_row_height="220" justified_gap="8" caption="title"]
```

Collage stable :

```text
[piwigo album="154" type="collage" collage_seed="ete-2026" collage_rotation="7" collage_spread="20"]
```

Texte-photo multiligne avec Bungee :

```text
[piwigo album="154" type="photo-text" photo_text="PÊLE-MÊLE\nÉTÉ 2026" photo_text_font="bundled-bungee" photo_text_fill_mode="grid" photo_text_outline="true"]
```
