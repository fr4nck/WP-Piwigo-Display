# Matrice de parité des composeurs

Cette matrice décrit le socle fonctionnel non régressable du shortcode `[piwigo]` pour la RC 3.1.

| Fonction | Moteur | Administration | Classic Editor | Gutenberg |
|---|---:|---:|---:|---:|
| Album par identifiant, nom ou chemin | Oui | Oui | Oui | Oui |
| Sélecteur visuel d’albums | Oui | Oui | Oui | Oui |
| Sous-albums et profondeur | Oui | Oui | Oui | Oui |
| Galerie | Oui | Oui | Oui | Oui |
| Diaporama | Oui | Oui | Oui | Oui |
| Masonry | Oui | Oui | Oui | Oui |
| Justified Gallery | Oui | Oui | Oui | Oui |
| Collage / Pêle-mêle | Oui | Oui | Oui | Oui |
| Texte rempli de photos | Oui | Oui | Oui | Oui |
| Presets | Oui | Oui | Oui | Oui |
| Limite, maximum, dernières, aléatoires | Oui | Oui | Oui | Oui |
| Tri et ordre | Oui | Oui | Oui | Oui |
| Orientation | Oui | Oui | Oui | Oui |
| Tags et mode de correspondance | Oui | Oui | Oui | Oui |
| Légendes, style, cadrage, hauteur | Oui | Oui | Oui | Oui |
| Lightbox et coins arrondis | Oui | Oui | Oui | Oui |
| Formes intégrées | Oui | Oui | Oui | Oui |
| Masques SVG personnalisés | Oui | Oui | Oui | Oui |
| Autoplay, intervalle, vitesse | Oui | Oui | Oui | Oui |
| Transition et direction | Oui | Oui | Oui | Oui |
| Ratio, navigation, largeur, alignement | Oui | Oui | Oui | Oui |
| Colonnes et espacement Masonry | Oui | Oui | Oui | Oui |
| Hauteur de ligne et espacement Justified | Oui | Oui | Oui | Oui |
| Graine, rotation, dispersion et chevauchement Collage | Oui | Oui | Oui | Oui |
| Typographie Texte-photo | Oui | Oui | Oui | Oui |
| Remplissage grille/masonry/pêle-mêle Texte-photo | Oui | Oui | Oui | Oui |
| Polices incluses Texte-photo | Oui | Oui | Oui | Oui |
| Polices utilisateur locales Texte-photo | Oui | Oui | Oui | Oui |
| URL Piwigo spécifique | Oui | Oui | Oui | Oui |

## Parité atteinte

Les trois composeurs utilisent le même socle fonctionnel. Gutenberg, Classic Editor et le composeur d’administration exposent les modes 3.1 et leurs réglages principaux sans imposer l’écriture manuelle du shortcode.

Les shortcodes restent la représentation portable et l’interface avancée de compatibilité.

## Checklist de non-régression 3.1

Avant une version :

1. vérifier la présence des écrans Tableau de bord, Composer, Réglages, Diagnostic, Masques SVG et Polices locales ;
2. exécuter la CI complète ;
3. vérifier la sélection visuelle et la saisie manuelle d’album dans les trois interfaces ;
4. tester galerie, slider et Masonry historiques ;
5. tester Justified avec plusieurs ratios d’images ;
6. tester Collage avec deux graines différentes puis la stabilité d’une même graine ;
7. tester une forme intégrée puis un masque SVG personnalisé sanitizé ;
8. tester Texte-photo en grille, masonry et pêle-mêle ;
9. tester Texte-photo multiligne, contour, alignement et responsive ;
10. tester Bebas Neue, Bungee puis une police WOFF2 utilisateur ;
11. vérifier que le slider reste utilisable si Splide arrive en retard ;
12. vérifier que la saisie manuelle d’album reste disponible lorsque l’API échoue ;
13. vérifier Santé API & cache après plusieurs affichages ;
14. signaler explicitement toute validation WordPress non réalisée.
