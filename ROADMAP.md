# Feuille de route

Cette feuille de route décrit l’état réel du développement de Piwigo Display.

## Historique public

La dernière version stable effectivement publiée avant la V3 est **1.8.0**.

La ligne **2.0.0** a été un jalon de développement mais **n’a jamais été publiée comme release publique**. Son travail a été repris dans la V3.

## V3 RC — socle acquis

Le socle 3.0 apporte notamment :

- Gutenberg dynamique, Classic Editor / TinyMCE et composeur d’administration ;
- sélecteur d’albums visuel, hiérarchique et recherchable ;
- galerie responsive, slider Splide et Masonry ;
- lightbox ;
- compte de service Piwigo ;
- cache séparé par contexte d’accès ;
- diagnostic et purge ;
- transitions de slider `slide`, `fade`, `none` et direction `ltr` / `rtl` ;
- fallback natif du slider si Splide tarde ou échoue ;
- métriques persistantes Santé API & cache ;
- contrôles automatisés de sécurité, accessibilité, compatibilité PHP, WPCS et Plugin Check.

## 3.1.0-rc.1 — gel fonctionnel

La 3.1 est désormais en Release Candidate. Les fonctionnalités prévues pour cette version sont fusionnées dans `3.1.x-dev`.

### Justified Gallery

- lignes justifiées conservant les proportions ;
- hauteur cible et espacement configurables ;
- responsive et lightbox ;
- parité Gutenberg / Classic / composeur.

### Collage / Pêle-mêle

- placement déterministe par graine ;
- rotation, dispersion, chevauchement, taille et variation ;
- ordre DOM et clavier préservés ;
- compatibilité avec les formes.

### Formes et masques SVG

- bibliothèque intégrée étendue : nuage, cœur, goutte, triangle, pentagone, octogone et enseignes de cartes ;
- sélecteurs visuels ;
- import de masques SVG personnalisés ;
- sanitation stricte sans ressource externe ni contenu actif ;
- bibliothèque locale, aperçu et suppression sécurisée ;
- parité des trois interfaces.

### Texte rempli de photos

- masque typographique SVG rendu côté serveur ;
- plusieurs photos Piwigo dans les glyphes ;
- texte sémantique conservé pour l’accessibilité ;
- multiligne ;
- taille, largeur, interlettrage, hauteur de ligne et alignement ;
- remplissage grille, masonry ou pêle-mêle ;
- densité, rotation, dispersion, contour, fond et graine ;
- polices thème/système ;
- polices libres embarquées Bebas Neue et Bungee ;
- import administrateur WOFF2/WOFF avec validation stricte et stockage dédié ;
- aucune police tierce distante chargée automatiquement.

## Ce qui reste avant 3.1 stable

1. produire et installer le ZIP `3.1.0-rc.1` ;
2. effectuer la recette manuelle WordPress avec Piwigo réel ;
3. vérifier les nouveaux modes sur Gutenberg, Classic Editor et composeur ;
4. vérifier desktop, tablette et mobile ;
5. corriger uniquement les régressions et bugs observés ;
6. refaire une RC si une correction fonctionnelle l’exige ;
7. publier `3.1.0` stable après GO de recette.

La checklist de référence est `docs/RECETTE-3X.md`.

## Maintenance non bloquante

L’issue #102 suit le nettoyage progressif de la dette WPCS historique des tests. Cette dette n’est pas bloquante pour la RC tant que les contrôles `standards:checked` et la CI de livraison restent verts.

## Après 3.1

Le chantier d’architecture multi-plateformes de l’issue #104 commence seulement après stabilisation 3.1. L’objectif sera d’extraire progressivement un Core portable sans transformer le plugin WordPress en package multi-CMS.

## Principe

Piwigo Display doit rester léger : **Piwigo gère les photos, WordPress les affiche**. Une évolution ne doit ni recopier inutilement les médias ni introduire une dépendance externe sans nécessité réelle.
