# Formes et masques des galeries

Piwigo Display peut appliquer une silhouette au cadre visuel des images sans modifier le cadrage `object-fit`, la lightbox ni les transitions.

## Attribut `shape`

Formes intégrées disponibles :

- `rectangle` : rendu sans découpe ;
- `rounded` : rectangle avec arrondi réglable ;
- `circle` : cercle ;
- `oval` : ovale ;
- `pill` : pilule ;
- `star` : étoile ;
- `hexagon` : hexagone ;
- `diamond` : losange ;
- `cloud` : nuage ;
- `heart` : cœur ;
- `drop` : goutte ;
- `triangle` : triangle ;
- `pentagon` : pentagone ;
- `octagon` : octogone ;
- `card-spade` : pique ;
- `card-heart` : cœur de carte ;
- `card-diamond` : carreau ;
- `card-club` : trèfle.

Exemples :

```text
[piwigo album="154" shape="circle"]
[piwigo album="154" type="slider" shape="cloud"]
[piwigo album="154" type="collage" shape="card-spade"]
```

Les trois interfaces visuelles proposent un sélecteur avec aperçu.

## Attribut `radius`

`radius` s’applique à `shape="rounded"`. La valeur est bornée entre 0 et 50 et exprimée en pourcentage.

```text
[piwigo album="154" shape="rounded" radius="18"]
```

L’ancien attribut `rounded="true"` reste pris en charge.

## Masques SVG personnalisés

Un administrateur peut importer un SVG depuis **Piwigo Display → Masques SVG**. Après validation et sanitation, le masque apparaît dans Gutenberg, Classic Editor et le composeur.

Le shortcode utilise une valeur interne de la forme :

```text
[piwigo album="154" shape="custom-<identifiant>"]
```

L’identifiant est généré par le plugin ; il est préférable de sélectionner le masque depuis une interface visuelle plutôt que de l’écrire manuellement.

## Sécurité des SVG

Le fichier utilisateur n’est jamais rendu tel quel. Le pipeline :

- limite la taille ;
- parse le XML sans accès réseau ;
- refuse `DOCTYPE`, `ENTITY` et `xml-stylesheet` ;
- refuse `script`, les attributs événementiels `on*`, `style`, `javascript:`, `data:`, `url()` et `@import` ;
- refuse les références externes ;
- n’autorise qu’un sous-ensemble de primitives SVG utiles ;
- normalise le `viewBox` ;
- stocke uniquement la version sanitizée ;
- exige capacité administrateur et nonce pour import et suppression.

## Rendu et fallback

Les formes simples s’appuient sur `border-radius` ou `clip-path`. Certains masques utilisent `mask-image` / `-webkit-mask-image` avec une ressource produite uniquement à partir du SVG sanitizé.

Si la technique n’est pas prise en charge par le navigateur, le plugin conserve un repli rectangulaire propre plutôt que de casser l’affichage.

Les légendes, miniatures, commandes du diaporama et la lightbox restent indépendantes de la découpe appliquée à l’image.
