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
- `diamond` : losange.

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

Les formes simples utilisent `border-radius`. Les formes étoile, hexagone et losange utilisent `clip-path`. Lorsqu’un navigateur ne prend pas en charge `clip-path`, un rectangle propre est affiché.

Les réglages sont disponibles dans :

- le composeur d’administration ;
- Classic Editor ;
- Gutenberg.

Les légendes, miniatures, commandes du diaporama et la lightbox ne sont pas découpées avec l’image.
