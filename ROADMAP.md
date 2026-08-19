# Feuille de route

Cette feuille de route décrit l’état réel du développement. Les fonctions déjà fusionnées dans `3.x-dev` constituent un socle non régressable.

## Version stable distribuée — 2.0.0

La version 2.0.0 reste la référence publique tant qu’une préversion 3.x n’a pas terminé sa recette WordPress.

Elle fournit notamment :

- bloc Gutenberg dynamique ;
- éditeur classique avec aperçu TinyMCE ;
- composeur d’administration ;
- galerie responsive et diaporama Splide ;
- sélection d’album par identifiant, nom, chemin ou arborescence ;
- sous-albums et profondeur configurable ;
- tri, limites, orientations, tags, légendes et styles ;
- cache WordPress séparé par contexte d’accès ;
- diagnostic, purge du cache et compte de service Piwigo.

## Socle 3.x désormais acquis

Le développement 3.x ajoute sans supprimer le socle 2.x :

- transitions de diaporama `slide`, `fade` et `none` ;
- direction horizontale `ltr` ou `rtl` ;
- distinction entre durée d’affichage, vitesse, effet et direction ;
- redimensionnement visuel et adaptation mobile à 100 % ;
- mode Masonry natif en colonnes CSS ;
- réglage du nombre de colonnes et de l’espacement Masonry ;
- presets et URL Piwigo spécifique dans Gutenberg ;
- sélecteur visuel, hiérarchique et recherchable des albums dans Gutenberg ;
- saisie manuelle par identifiant, nom ou chemin conservée en secours ;
- parité fonctionnelle entre le composeur d’administration, Classic Editor et Gutenberg ;
- matrice de parité et checklist de non-régression documentées.

La matrice de référence est disponible dans `docs/PARITE-COMPOSEURS.md`.

## Prochaine étape — stabilisation et recette 3.x

Suivi : issue #46.

Avant toute préversion 3.x :

1. exécuter les tests PHP statiques et le lint sur toutes les versions PHP prises en charge ;
2. installer le ZIP construit par GitHub Actions sur un WordPress de recette ;
3. tester Administration, Classic Editor et Gutenberg avec une instance Piwigo réelle ;
4. vérifier les anciens shortcodes sans nouveaux attributs ;
5. tester les galeries, diaporamas et Masonry sur ordinateur, tablette et mobile ;
6. vérifier le clavier, les libellés et les états de chargement ou d’erreur ;
7. simuler une API Piwigo indisponible et confirmer que la saisie manuelle reste utilisable ;
8. contrôler le cache, sa séparation par contexte d’accès et sa purge ;
9. vérifier la cohérence des numéros de version, du README et du ZIP ;
10. consigner explicitement toute vérification manuelle non automatisable.

## Étapes ultérieures

Après stabilisation du socle 3.x :

- amélioration progressive de l’accessibilité ;
- meilleure adoption des variables CSS du thème WordPress ;
- optimisation mesurée du cache et des appels Piwigo ;
- réduction de la duplication entre les composeurs lorsque cela ne fragilise pas leur fonctionnement ;
- styles et effets supplémentaires uniquement lorsqu’un besoin concret les justifie ;
- préparation d’une documentation utilisateur orientée cas d’usage.

## Principe

WP Piwigo Display doit rester léger : Piwigo gère les photos, WordPress les affiche. Une évolution ne doit ni recopier inutilement les médias ni transformer le plugin en gestionnaire de photothèque.