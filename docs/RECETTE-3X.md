# Recette de stabilisation V3

Cette checklist doit être remplie avant de présenter **3.0.0** comme version stable. Une case critique non vérifiée interdit de transformer la Release Candidate en release stable.

La Release Candidate courante est **3.0.0-rc.3**. La dernière stable publique avant la V3 est **1.8.0** ; la ligne 2.0.0 n’a jamais été publiée comme release publique.

## 1. Préparation et paquet

- [ ] récupérer le ZIP `piwigo-display-3.0.0-rc.3.zip` produit par GitHub Actions pour le commit à tester ;
- [ ] vérifier que le ZIP contient un seul dossier racine `wp-piwigo-display` ;
- [ ] vérifier que ce dossier contient `wp-piwigo-display.php` et non un fichier principal renommé ;
- [ ] installer le ZIP sur un WordPress de recette sans reprendre les fichiers du dépôt ;
- [ ] vérifier qu’une mise à jour depuis une installation historique conserve la même extension et ses réglages ;
- [ ] relever les versions WordPress, PHP, navigateur et Piwigo utilisées ;
- [ ] activer `WP_DEBUG` et conserver les erreurs éventuelles.

## 2. Installation et administration

- [ ] activation sans erreur fatale ni avertissement PHP ;
- [ ] présence du menu principal Piwigo Display ;
- [ ] présence des écrans Tableau de bord, Composer, Réglages et Diagnostic ;
- [ ] sauvegarde et relecture de l’URL Piwigo ;
- [ ] une installation neuve ne contient aucune URL Piwigo préconfigurée ;
- [ ] test de connexion concluant avec une instance valide ;
- [ ] message explicite avec une URL absente, incorrecte ou indisponible ;
- [ ] export du diagnostic exploitable ;
- [ ] purge du cache effective ;
- [ ] présence du bloc **Santé API & cache**.

## 3. Santé API & cache

- [ ] le compteur d’appels API augmente après un appel Piwigo réel ;
- [ ] un second chargement identique produit le comportement HIT/MISS attendu ;
- [ ] le taux de HIT est cohérent avec les compteurs ;
- [ ] les temps cumulé, moyen et maximal sont affichés ;
- [ ] la dernière méthode Piwigo et le statut HTTP sont plausibles ;
- [ ] une erreur API contrôlée apparaît comme dernière erreur sans exposer de secret ;
- [ ] aucun identifiant, mot de passe ni corps de requête n’est stocké ou affiché ;
- [ ] le verdict de santé reste compréhensible ;
- [ ] le compteur reste présent après mise à jour/réinstallation de la RC.

## 4. Sélection des albums

À contrôler dans le composeur d’administration, Classic Editor et Gutenberg :

- [ ] **sans compte de service configuré**, l’explorateur public charge bien les albums ;
- [ ] avec compte de service configuré, l’explorateur peut charger les albums autorisés à ce compte ;
- [ ] chargement de l’arborescence des albums ;
- [ ] respect des niveaux parent/enfant ;
- [ ] recherche par nom et chemin ;
- [ ] sélection au clic et report correct de la valeur ;
- [ ] saisie manuelle par identifiant ;
- [ ] saisie manuelle par nom ;
- [ ] saisie manuelle par chemin, par exemple `/ALSH/Été 2026/Séjour voile` ;
- [ ] deux albums de même nom restent distinguables par leur chemin ;
- [ ] saisie manuelle encore utilisable lorsque l’API échoue ;
- [ ] navigation clavier et focus visibles dans le sélecteur.

## 5. Parité des composeurs

Pour chaque interface, vérifier la génération et le rendu des options suivantes :

- [ ] galerie, diaporama et Masonry ;
- [ ] `recursive` avec `depth` ;
- [ ] `limit`, `max`, `latest` et `random` ;
- [ ] `sort` et `order` ;
- [ ] orientations ;
- [ ] tag unique, tags multiples et `tag_mode` ;
- [ ] légendes, style, formes, cadrage et hauteur ;
- [ ] lightbox et coins arrondis ;
- [ ] autoplay, intervalle et vitesse ;
- [ ] transition et direction ;
- [ ] ratio, navigation, largeur et alignement ;
- [ ] colonnes et espacement Masonry ;
- [ ] preset et URL Piwigo spécifique.

La matrice de référence est `docs/PARITE-COMPOSEURS.md`.

## 6. Compte de service et URL personnalisée

- [ ] le compte de service fonctionne sur l’URL Piwigo configurée dans les réglages ;
- [ ] les albums privés autorisés s’affichent avec le compte dédié ;
- [ ] le HTML et le JavaScript publics ne contiennent jamais le nom d’utilisateur ni le mot de passe ;
- [ ] un shortcode utilisant `url="https://autre-galerie.example.org"` reste **anonyme**, même lorsqu’un compte de service est configuré ;
- [ ] les données mises en cache pour le compte de service et les données anonymes ne partagent pas le même contexte ;
- [ ] désactiver le compte de service rend à nouveau l’explorateur public normalement utilisable.

## 7. Compatibilité historique

- [ ] `[piwigo album="154"]` ;
- [ ] galerie historique sans nouveaux attributs ;
- [ ] diaporama historique sans transition explicite ;
- [ ] shortcode avec récursivité ;
- [ ] shortcode avec tri et limite ;
- [ ] contenu enregistré avec la dernière version publique 1.x utilisée en production ;
- [ ] absence de modification silencieuse du shortcode lors d’une réouverture dans un composeur ;
- [ ] les hooks historiques `wp_piwigo_display_*` restent disponibles.

## 8. Rendu, slider et responsive

Sur ordinateur, tablette et mobile :

- [ ] galerie responsive sans débordement horizontal ;
- [ ] diaporama stable et commandes utilisables ;
- [ ] miniatures du slider synchronisées avec la diapositive active ;
- [ ] transitions `slide`, `fade` et `none` ;
- [ ] autoplay et intervalle ;
- [ ] fonctionnement du fallback natif si Splide tarde ou ne se charge pas ;
- [ ] largeur et alignement respectés sur grand écran ;
- [ ] retour automatique à 100 % sur mobile ;
- [ ] Masonry réduit progressivement son nombre de colonnes ;
- [ ] formes d’encadrement sans découpe ou débordement inattendu ;
- [ ] légendes lisibles ;
- [ ] lightbox utilisable au clavier ;
- [ ] aucune image déformée de manière inattendue.

## 9. Redimensionnement visuel

- [ ] poignée visible lorsque le bloc ou le diaporama est sélectionné ;
- [ ] redimensionnement à la souris ;
- [ ] modification au clavier ;
- [ ] dimensions enregistrées puis restaurées après rechargement ;
- [ ] aucune largeur invalide ou supérieure au conteneur ;
- [ ] maintien du comportement mobile.

## 10. Cache, indisponibilité et réponses Piwigo polluées

- [ ] deux chargements identiques utilisent le cache attendu ;
- [ ] les contextes d’accès ne partagent pas indûment leurs données ;
- [ ] la purge force un nouvel appel Piwigo ;
- [ ] une indisponibilité Piwigo n’efface pas les réglages ni les shortcodes ;
- [ ] les erreurs côté administration sont compréhensibles ;
- [ ] le rendu public échoue proprement sans casser la page WordPress ;
- [ ] sur une instance affectée par un plugin Piwigo qui ajoute du HTML/JavaScript autour de `ws.php?format=json`, la réponse API reste récupérable ;
- [ ] cette compatibilité ne modifie pas les réponses HTTP étrangères à l’API Piwigo.

## 11. Contrôle technique

- [ ] PHP lint réussi sur toutes les versions annoncées ;
- [ ] tous les tests PHP de non-régression du dossier `tests/` sont réellement exécutés ;
- [ ] sécurité et accessibilité automatisées réussies ;
- [ ] WPCS contrôlé sur les fichiers normalisés ;
- [ ] le ZIP réellement installable est validé sous l’identité technique historique `wp-piwigo-display` sans renommer ses fichiers, hooks ou text-domain ;
- [ ] WordPress Plugin Check réussit sur une copie **byte-identique du code livré** placée sous le slug candidat `piwigo-display`, sans réécriture du contenu ; seules les exceptions de compatibilité documentées pour les hooks publics et le text-domain historiques sont ignorées ;
- [ ] intégrité du paquet frontend vérifiée ;
- [ ] cohérence entre l’en-tête du plugin, `WPD_VERSION`, README et `readme.txt` ;
- [ ] aucun secret ni identifiant Piwigo dans le HTML ou JavaScript public ;
- [ ] aucune erreur JavaScript dans les trois composeurs ;
- [ ] aucun avertissement PHP dans les scénarios nominaux.

## 12. Décision de release

Renseigner avant création d’un tag ou d’un ZIP présenté comme release stable :

- commit testé :
- ZIP testé :
- environnement WordPress/PHP/Piwigo :
- vérifications manuelles non réalisables :
- anomalies connues :
- décision : **GO / NO GO**
- responsable de la décision :
- date :

Un **GO** exige que toute anomalie restante soit documentée et explicitement acceptée.
