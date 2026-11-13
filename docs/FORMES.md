# Formes des galeries et diaporamas

La branche 3.x permet d’appliquer une forme au cadre visuel des images sans modifier le cadrage `object-fit`, la lightbox ni les transitions.

## Attribut `shape`

Valeurs acceptées :

- `rectangle` : rendu historique, sans découpe ;
- `rounded` : rectangle avec arrondi réglable ;
- `circle` : cercle ;
- `oval` : ovale ;
- `pill` : forme pilule ;
- `star` : étoile ;
- `hexagon` : hexagone ;
- `diamond` : losange ;
- `cloud` : nuage ;
- `heart` : cœur ;
- `drop` : goutte ;
- `triangle`, `pentagon`, `octagon` : polygones ;
- `card-spade`, `card-heart`, `card-diamond`, `card-club` : symboles de cartes.

Exemples :

```text
[piwigo album="154" shape="circle"]
[piwigo album="154" type="slider" shape="oval"]
[piwigo album="154" shape="star"]
```

## Attribut `radius`

`radius` s’applique à `shape="rounded"`. La valeur est bornée entre 0 et 50 et exprimée en pourcentage.

```text
[piwigo album="154" shape="rounded" radius="18"]
```

L’ancien attribut `rounded="true"` reste pris en charge. Lorsqu’aucun attribut de forme n’est indiqué, le rendu reste strictement identique aux shortcodes existants.

## Compatibilité

Les formes simples utilisent `border-radius`. Les étoiles, polygones, symboles et silhouettes supplémentaires utilisent `clip-path` et, lorsque disponible, les masques CSS natifs. Lorsqu’un navigateur ne prend pas en charge `clip-path`, un rectangle propre est affiché.

La 3.1 candidate étend ces formes aux galeries Justified et Collage. Les anciens shortcodes sans attribut `shape` restent inchangés. Les trois éditeurs proposent une sélection visuelle accessible au clavier. Les masques SVG personnalisés constituent un chantier distinct, à qualifier séparément.

Les réglages sont disponibles dans :

- le composeur d’administration ;
- Classic Editor ;
- Gutenberg.

Les légendes, miniatures, commandes du diaporama et la lightbox ne sont pas découpées avec l’image. Les formes ne changent pas les destinations `click_action`.

**Statut :** développement candidat, non publié dans la 3.0.1.
