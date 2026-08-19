# Recette de stabilisation 3.1

Cette checklist doit être remplie avant de présenter 3.1.0 comme stable. La CI automatise les invariants techniques ; les points visuels et l’intégration avec un vrai WordPress/Piwigo restent à vérifier manuellement.

## 1. Préparation

- [ ] récupérer le ZIP `piwigo-display-3.1.0-rc.1.zip` produit par GitHub Actions ;
- [ ] vérifier que le ZIP contient un seul dossier racine `piwigo-display` ;
- [ ] installer le ZIP sur un WordPress de recette sans reprendre les fichiers du dépôt ;
- [ ] relever les versions WordPress, PHP, navigateur et Piwigo utilisées ;
- [ ] activer `WP_DEBUG` et conserver les erreurs éventuelles.

## 2. Installation et administration

- [ ] activation sans erreur fatale ni avertissement PHP ;
- [ ] présence du menu Piwigo Display ;
- [ ] présence des écrans Tableau de bord, Composer, Réglages et Diagnostic ;
- [ ] présence des bibliothèques Masques SVG et Polices locales ;
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

## 4. Modes historiques

Dans les trois interfaces :

- [ ] galerie classique ;
- [ ] slider avec miniatures ;
- [ ] slider avec points ;
- [ ] autoplay, intervalle et vitesse ;
- [ ] transitions `slide`, `fade`, `none` ;
- [ ] directions `ltr` et `rtl` ;
- [ ] fallback natif du slider si Splide est volontairement retardé ou indisponible ;
- [ ] Masonry, nombre de colonnes et espacement ;
- [ ] lightbox ;
- [ ] légendes ;
- [ ] filtres orientation et tags.

## 5. Justified Gallery

- [ ] mode Justified proposé dans Gutenberg ;
- [ ] mode Justified proposé dans Classic Editor ;
- [ ] mode Justified proposé dans le composeur ;
- [ ] mélange portraits/paysages/carrés sans déformation ;
- [ ] hauteur cible modifiable ;
- [ ] espacement modifiable ;
- [ ] dernière ligne propre ;
- [ ] une seule image ;
- [ ] dimensions manquantes avec repli propre ;
- [ ] responsive sans débordement ;
- [ ] lightbox et légendes conservées.

## 6. Collage / Pêle-mêle

- [ ] mode Collage proposé dans les trois interfaces ;
- [ ] rotation visible mais bornée ;
- [ ] dispersion modifiable ;
- [ ] chevauchement modifiable ;
- [ ] taille moyenne et variation modifiables ;
- [ ] même graine + mêmes photos = même composition après rechargement ;
- [ ] graine différente = composition différente ;
- [ ] ordre clavier cohérent malgré l’ordre visuel ;
- [ ] pas de débordement gênant sur mobile ;
- [ ] lightbox utilisable ;
- [ ] formes compatibles.

## 7. Formes et masques SVG personnalisés

- [ ] formes historiques rectangle/arrondi/cercle/ovale/pilule/étoile/hexagone/losange ;
- [ ] nuage, cœur, goutte, triangle, pentagone et octogone ;
- [ ] pique, cœur de carte, carreau et trèfle ;
- [ ] miniatures visuelles cohérentes dans les sélecteurs ;
- [ ] import d’un SVG simple autorisé ;
- [ ] aperçu du masque importé ;
- [ ] sélection du masque dans les trois interfaces ;
- [ ] rendu public du masque ;
- [ ] suppression du masque ;
- [ ] refus d’un SVG contenant `script` ou événement `on*` ;
- [ ] refus d’une référence externe ;
- [ ] refus d’un `DOCTYPE` / `ENTITY` ;
- [ ] repli propre si `mask-image` n’est pas pris en charge.

## 8. Texte rempli de photos

Dans Gutenberg, Classic Editor et composeur :

- [ ] mode Texte rempli de photos disponible ;
- [ ] texte simple ;
- [ ] texte avec accents ;
- [ ] deux à quatre lignes ;
- [ ] taille du texte ;
- [ ] interlettrage négatif et positif ;
- [ ] hauteur de ligne ;
- [ ] largeur maximale ;
- [ ] alignements gauche, centre et droite ;
- [ ] mode grille ;
- [ ] mode masonry ;
- [ ] mode pêle-mêle ;
- [ ] densité ;
- [ ] rotation/dispersion du pêle-mêle ;
- [ ] nombre maximal de photos ;
- [ ] contour activé/désactivé ;
- [ ] épaisseur et couleur du contour ;
- [ ] fond transparent et fond coloré ;
- [ ] même graine = même remplissage ;
- [ ] texte sémantique toujours présent pour les technologies d’assistance ;
- [ ] SVG décoratif non exposé au lecteur d’écran ;
- [ ] responsive mobile sans débordement.

## 9. Polices Texte-photo

- [ ] police du thème ;
- [ ] police système ;
- [ ] serif ;
- [ ] monospace ;
- [ ] Bebas Neue incluse ;
- [ ] Bungee incluse ;
- [ ] aucune requête vers Google Fonts, Typekit ou autre service tiers ;
- [ ] import administrateur d’un WOFF2 valide ;
- [ ] aperçu de la police importée ;
- [ ] police importée disponible dans les trois interfaces ;
- [ ] suppression de la police importée ;
- [ ] refus d’une fausse police renommée `.woff2` ;
- [ ] fichier utilisateur stocké dans `uploads/piwigo-display-fonts` et absent du paquet plugin.

## 10. Parité générale des composeurs

Pour chaque interface, vérifier également :

- [ ] `recursive` avec `depth` ;
- [ ] `limit`, `max`, `latest` et `random` ;
- [ ] `sort` et `order` ;
- [ ] orientations ;
- [ ] tag unique, tags multiples et `tag_mode` ;
- [ ] légendes, style, cadrage et hauteur ;
- [ ] preset et URL Piwigo spécifique.

La matrice de référence est `docs/PARITE-COMPOSEURS.md`.

## 11. Compatibilité historique

- [ ] `[piwigo album="154"]` ;
- [ ] galerie historique sans nouveaux attributs ;
- [ ] diaporama historique sans transition explicite ;
- [ ] shortcode avec récursivité ;
- [ ] shortcode avec tri, limite et tags ;
- [ ] contenu enregistré avec une version publique 1.x ;
- [ ] absence de modification silencieuse du shortcode lors d’une réouverture dans un composeur.

La ligne 2.0.0 n’ayant jamais été publiée comme release publique, elle n’est pas une référence de migration utilisateur.

## 12. Cache, diagnostic et indisponibilité

- [ ] deux chargements identiques utilisent le cache attendu ;
- [ ] les contextes d’accès ne partagent pas indûment leurs données ;
- [ ] la purge force un nouvel appel Piwigo ;
- [ ] Santé API & cache incrémente les appels réels ;
- [ ] HIT/MISS et taux de HIT évoluent de façon cohérente ;
- [ ] dernier statut/méthode/erreur restent exploitables ;
- [ ] une indisponibilité Piwigo n’efface pas les réglages ni les shortcodes ;
- [ ] les erreurs côté administration sont compréhensibles ;
- [ ] le rendu public échoue proprement sans casser la page WordPress.

## 13. Responsive et accessibilité

Sur ordinateur, tablette et mobile :

- [ ] aucune disposition ne provoque de débordement horizontal inattendu ;
- [ ] commandes du slider utilisables ;
- [ ] légendes lisibles ;
- [ ] lightbox utilisable au clavier ;
- [ ] focus visible ;
- [ ] ordre de tabulation cohérent ;
- [ ] `prefers-reduced-motion` respecté ;
- [ ] aucune image déformée de manière inattendue.

## 14. Contrôle technique

- [ ] CI de la RC entièrement verte ;
- [ ] PHP 8.1 et PHP 8.4 passent le lint ;
- [ ] WPCS contrôlé sans erreur bloquante ;
- [ ] WordPress Plugin Check vert ;
- [ ] cohérence entre l’en-tête du plugin, `WPD_VERSION` et `readme.txt` ;
- [ ] aucun secret ni identifiant Piwigo dans le HTML ou JavaScript public ;
- [ ] aucune erreur JavaScript dans les trois composeurs ;
- [ ] aucun avertissement PHP dans les scénarios nominaux.

## 15. Décision de préversion / stable

- commit testé :
- ZIP testé :
- environnement WordPress/PHP/Piwigo :
- vérifications manuelles non réalisables :
- anomalies connues :
- décision : **GO / NO GO**
- responsable de la décision :
- date :

Un **GO stable** exige que toute anomalie restante soit documentée et explicitement acceptée.
