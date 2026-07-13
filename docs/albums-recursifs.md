# Albums récursifs

## Objectif

Le mode récursif permet d'afficher les images d'un album Piwigo ainsi que celles de ses sous-albums, sans créer d'album de synthèse et sans dupliquer les photos.

## Utilisation

Pour inclure toute la descendance :

```text
[piwigo album="154" recursive="true"]
```

Pour limiter la profondeur :

```text
[piwigo album="154" recursive="true" depth="2"]
```

## Valeurs de `depth`

| Valeur | Résultat |
|---|---|
| `0` | Album indiqué uniquement |
| `1` | Album et enfants directs |
| `2` | Album, enfants et petits-enfants |
| `10` | Toute la descendance prise en charge |

Le plugin limite la valeur maximale à `10`.

## Tri et limitation

Les images fusionnées peuvent ensuite être triées et limitées comme une galerie ordinaire.

Exemple : afficher les 20 dernières images de toute une branche Piwigo.

```text
[piwigo album="154" recursive="true" sort="date" order="desc" limit="20"]
```

Exemple : afficher 12 images aléatoires.

```text
[piwigo album="154" recursive="true" sort="random" limit="12"]
```

## Pagination

L'API est interrogée par pages de 500 images afin de pouvoir traiter les albums volumineux.

## Doublons

Une même image peut appartenir à plusieurs albums Piwigo. Le plugin supprime les doublons avant l'affichage.

## Cache

Le cache tient compte :

- de l'URL Piwigo ;
- de l'identifiant de l'album ;
- du mode récursif ;
- de la profondeur ;
- de la limite API demandée.

Après l'ajout de nouvelles photos dans Piwigo, utilisez le bouton **Vider le cache** dans les réglages WordPress si vous souhaitez les voir immédiatement.

## Bonnes pratiques

- utilisez l'identifiant numérique de l'album pour une correspondance sans ambiguïté ;
- appliquez une limite pour les très grandes arborescences sur une page d'accueil ;
- conservez une durée de cache raisonnable afin d'éviter des appels API répétés ;
- utilisez `depth="1"` ou `depth="2"` lorsque toute la descendance n'est pas nécessaire.
