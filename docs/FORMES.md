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

## Masques SVG personnalisés (candidate 3.1)

Les administrateurs WordPress disposant de la capacité `manage_options` peuvent importer des silhouettes géométriques SVG depuis **Piwigo Display → Masques SVG**. L’import impose un nonce, une extension SVG et une taille maximale de 256 Kio ; seuls les éléments géométriques autorisés sont conservés. Scripts, événements, styles actifs, entités XML, références externes et espaces de noms étrangers sont refusés ou supprimés. Le stockage reste local au site et n'est pas accessible à la modification depuis une page publique.

La bibliothèque affiche un identifiant à douze caractères hexadécimaux. Le shortcode peut utiliser la sélection visuelle dans les éditeurs ou l'identifiant :

```text
[piwigo album="154" shape="custom-a1b2c3d4e5f6" click_action="piwigo_album"]
```

Cet identifiant est un exemple : il faut utiliser celui créé par le site. Le rendu revalide le masque stocké avant de l'appliquer. Un masque absent, supprimé ou invalide ne modifie pas les photos ni les liens Piwigo. Les formes intégrées continuent à fonctionner sans configuration.

**Attention :** la sauvegarde de la bibliothèque de masques et son effacement lors d'une désinstallation définitive feront l'objet d'une qualification de migration dédiée avant toute sortie publique.

## Compatibilité

Les formes simples utilisent `border-radius`. Les étoiles, polygones, symboles et silhouettes supplémentaires utilisent `clip-path` et, lorsque disponible, les masques CSS natifs. Lorsqu’un navigateur ne prend pas en charge `clip-path`, un rectangle propre est affiché.

La 3.1 candidate étend ces formes aux galeries Justified et Collage. Les anciens shortcodes sans attribut `shape` restent inchangés. Les trois éditeurs proposent une sélection visuelle accessible au clavier. Les masques SVG personnalisés sont inclus dans un chantier de qualification distinct des formes intégrées.

Les réglages sont disponibles dans :

- le composeur d’administration ;
- Classic Editor ;
- Gutenberg.

Les légendes, miniatures, commandes du diaporama et la lightbox ne sont pas découpées avec l’image. Les formes ne changent pas les destinations `click_action`.

**Statut :** développement candidat, non publié dans la 3.0.1.
