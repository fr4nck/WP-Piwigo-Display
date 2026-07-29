# Matrice de parité des composeurs

Cette matrice décrit le socle fonctionnel non régressable du shortcode `[piwigo]`.

| Fonction | Moteur | Administration | Classic Editor | Gutenberg |
|---|---:|---:|---:|---:|
| Album par identifiant, nom ou chemin | Oui | Oui | Oui | Identifiant |
| Sélecteur visuel d’albums | Oui | Oui | Oui | À compléter |
| Sous-albums et profondeur | Oui | Oui | Oui | Oui |
| Galerie | Oui | Oui | Oui | Oui |
| Diaporama | Oui | Oui | Oui | Oui |
| Masonry | Oui | Oui | Oui | Oui |
| Presets | Oui | Oui | Oui | Non exposé |
| Limite, maximum, dernières, aléatoires | Oui | Oui | Oui | Oui |
| Tri et ordre | Oui | Oui | Oui | Oui |
| Orientation | Oui | Oui | Oui | Oui |
| Tags et mode de correspondance | Oui | Oui | Oui | Oui |
| Légendes, style, cadrage, hauteur | Oui | Oui | Oui | Oui |
| Lightbox et coins arrondis | Oui | Oui | Oui | Oui |
| Autoplay, intervalle, vitesse | Oui | Oui | Oui | Oui |
| Transition et direction | Oui | Oui | Oui | Oui |
| Ratio, navigation, largeur, alignement | Oui | Oui | Oui | Oui |
| Colonnes et espacement Masonry | Oui | Oui | Oui | Oui |
| URL Piwigo spécifique | Oui | Oui | Oui | Non exposé |

## Écarts documentés

Gutenberg reste volontairement plus guidé que les deux composeurs complets. Deux écarts sont encore identifiés :

- le sélecteur visuel d’albums n’est pas encore intégré au bloc ;
- `preset` et `url` ne sont pas exposés dans l’inspecteur Gutenberg.

Ces écarts ne suppriment pas les fonctions du moteur et doivent être traités comme des améliorations distinctes.

## Checklist de non-régression

Avant une version :

1. vérifier la présence des menus Tableau de bord, Composer, Réglages et Diagnostic ;
2. exécuter les tests PHP statiques ;
3. vérifier la génération de `recursive` avec `depth` ;
4. vérifier `width` avec `align` ;
5. vérifier `transition` avec `direction` ;
6. vérifier `masonry_columns` avec `masonry_gap` ;
7. tester la sélection visuelle d’un album dans l’administration et Classic Editor ;
8. signaler explicitement toute validation WordPress non réalisée.
