# Recette de stabilisation 3.x

Cette checklist doit être remplie avant toute préversion 3.x. Une case non vérifiée interdit de présenter la branche comme stable.

## 1. Préparation

- [ ] récupérer le ZIP produit par GitHub Actions pour le commit à tester ;
- [ ] vérifier que le ZIP contient un seul dossier racine `wp-piwigo-display` ;
- [ ] installer le ZIP sur un WordPress de recette sans reprendre les fichiers du dépôt ;
- [ ] relever les versions WordPress, PHP, navigateur et Piwigo utilisées ;
- [ ] activer `WP_DEBUG` et conserver les erreurs éventuelles.

## 2. Installation et administration

- [ ] activation sans erreur fatale ni avertissement PHP ;
- [ ] présence du menu principal WP Piwigo ;
- [ ] présence des écrans Tableau de bord, Composer, Réglages et Diagnostic ;
- [ ] sauvegarde et relecture de l’URL Piwigo ;
- [ ] test de connexion concluant avec une instance valide ;
- [ ] message explicite avec une URL absente, incorrecte ou indisponible ;
- [ ] export du diagnostic exploitable ;
- [ ] purge du cache effective.

## 3. Sélection des albums

À contrôler dans le composeur d’administration, Classic Editor et Gutenberg :

- [ ] chargement de l’arborescence des albums ;
- [ ] respect des niveaux parent/enfant ;
- [ ] recherche par nom et chemin ;
- [ ] sélection au clic et report correct de la valeur ;
- [ ] saisie manuelle par identifiant ;
- [ ] saisie manuelle par nom ;
- [ ] saisie manuelle par chemin ;
- [ ] saisie manuelle encore utilisable lorsque l’API échoue ;
- [ ] navigation clavier et focus visibles dans le sélecteur.

## 4. Parité des composeurs

Pour chaque interface, vérifier la génération et le rendu des options suivantes :

- [ ] galerie, diaporama et Masonry ;
- [ ] `recursive` avec `depth` ;
- [ ] `limit`, `max`, `latest` et `random` ;
- [ ] `sort` et `order` ;
- [ ] orientations ;
- [ ] tag unique, tags multiples et `tag_mode` ;
- [ ] légendes, style, cadrage et hauteur ;
- [ ] lightbox et coins arrondis ;
- [ ] autoplay, intervalle et vitesse ;
- [ ] transition et direction ;
- [ ] ratio, navigation, largeur et alignement ;
- [ ] colonnes et espacement Masonry ;
- [ ] preset et URL Piwigo spécifique.

La matrice de référence est `docs/PARITE-COMPOSEURS.md`.

## 5. Compatibilité historique

- [ ] `[piwigo album="154"]` ;
- [ ] galerie historique sans nouveaux attributs ;
- [ ] diaporama historique sans transition explicite ;
- [ ] shortcode avec récursivité ;
- [ ] shortcode avec tri, limite et tags ;
- [ ] contenu enregistré avec la version stable 2.0.0 ;
- [ ] absence de modification silencieuse du shortcode lors d’une réouverture dans un composeur.

## 6. Rendu et responsive

Sur ordinateur, tablette et mobile :

- [ ] galerie responsive sans débordement horizontal ;
- [ ] diaporama stable et commandes utilisables ;
- [ ] largeur et alignement respectés sur grand écran ;
- [ ] retour automatique à 100 % sur mobile ;
- [ ] Masonry réduit progressivement son nombre de colonnes ;
- [ ] légendes lisibles ;
- [ ] lightbox utilisable au clavier ;
- [ ] aucune image déformée de manière inattendue.

## 7. Redimensionnement visuel

- [ ] poignée visible lorsque le bloc ou le diaporama est sélectionné ;
- [ ] redimensionnement à la souris ;
- [ ] modification au clavier ;
- [ ] dimensions enregistrées puis restaurées après rechargement ;
- [ ] aucune largeur invalide ou supérieure au conteneur ;
- [ ] maintien du comportement mobile.

## 8. Cache et indisponibilité

- [ ] deux chargements identiques utilisent le cache attendu ;
- [ ] les contextes d’accès ne partagent pas indûment leurs données ;
- [ ] la purge force un nouvel appel Piwigo ;
- [ ] une indisponibilité Piwigo n’efface pas les réglages ni les shortcodes ;
- [ ] les erreurs côté administration sont compréhensibles ;
- [ ] le rendu public échoue proprement sans casser la page WordPress.

## 9. Contrôle technique

- [ ] PHP lint réussi sur toutes les versions annoncées ;
- [ ] tests statiques réussis ;
- [ ] cohérence entre l’en-tête du plugin et `WPD_VERSION` ;
- [ ] aucun secret ni identifiant Piwigo dans le HTML ou JavaScript public ;
- [ ] aucune erreur JavaScript dans les trois composeurs ;
- [ ] aucun avertissement PHP dans les scénarios nominaux.

## 10. Décision de préversion

Renseigner avant création d’un tag ou d’un ZIP présenté comme préversion :

- commit testé :
- ZIP testé :
- environnement WordPress/PHP/Piwigo :
- vérifications manuelles non réalisables :
- anomalies connues :
- décision : **GO / NO GO**
- responsable de la décision :
- date :

Un **GO** exige que toute anomalie restante soit documentée et explicitement acceptée.
