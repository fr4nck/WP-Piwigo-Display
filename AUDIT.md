# Audit technique — WP Piwigo Display 1.9.x

## Périmètre

Cet audit consolide l'état du dépôt avant toute évolution fonctionnelle de la branche `1.9.x`. Il ne modifie aucun comportement utilisateur : les constats ci-dessous servent de base aux corrections, refactorisations et optimisations futures.

Fichiers analysés : point d'entrée du plugin, classes PHP dans `includes/`, assets CSS/JS, documentation existante, README, readme WordPress.org, changelog, feuille de route et sécurité.

## Synthèse exécutive

WP Piwigo Display est un plugin WordPress léger qui expose un shortcode `[piwigo]`, interroge l'API Piwigo, met en cache les images via les transients WordPress, puis rend une galerie ou un diaporama Splide avec une lightbox locale.

L'architecture actuelle est lisible et compacte. La priorité de consolidation 1.9 doit être :

1. corriger les duplications évidentes sans changer le rendu ;
2. réduire les appels API redondants ;
3. rendre le cache plus granulaire et plus pilotable ;
4. charger les assets uniquement quand ils sont nécessaires ;
5. introduire des tests automatiques de non-régression avant toute évolution.

## État du dépôt

### Point d'entrée

- `wp-piwigo-display.php` déclare les métadonnées du plugin, les constantes de version, les chemins, charge toutes les classes et initialise `WPD_Plugin` sur `plugins_loaded`.
- La version déclarée est `1.8.0`, ce qui confirme que la branche `1.9.x` doit rester une branche de consolidation préparatoire.

### Classes PHP

- `WPD_Plugin` enregistre les hooks WordPress : traduction, shortcode, assets, réglages, page d'administration, actions de cache et test de connexion.
- `WPD_Settings` centralise les options, la page de réglages et la validation des valeurs persistées.
- `WPD_Shortcode` fusionne les valeurs par défaut, applique les presets, valide les attributs, résout l'album, récupère les images depuis le cache et délègue le rendu.
- `WPD_Api` encapsule les appels HTTP vers `ws.php?format=json`, la résolution d'albums et la récupération paginée d'images.
- `WPD_Cache` met en cache les tableaux d'images par album, URL, limite API, mode récursif et profondeur.
- `WPD_Renderer` prépare les images, applique tri/limite, construit le HTML de galerie/slider, les légendes et les attributs de lightbox.

### Assets

- `assets/js/wp-piwigo-display.js` initialise Splide quand la dépendance existe et construit une lightbox DOM locale.
- `assets/css/wp-piwigo-display.css` contient les styles galerie, slider, lightbox, miniatures, modes raw/minimal/theme/none.
- Splide est chargé depuis jsDelivr, enregistré globalement côté front.

## Duplications de code identifiées

### Duplications critiques ou évidentes

1. Dans `WPD_Renderer::render_gallery()`, `$raw_class` est calculé deux fois à l'identique.
2. Dans `WPD_Renderer::render_gallery()`, `$style_class` est calculé deux fois à l'identique.
3. La construction des informations image (`image_url`, `large_url`, `title`, `description`, `caption_mode`, `lightbox_caption`) est répétée entre galerie et slider.
4. La logique de déduplication d'images par identifiant ou hash est répétée dans `WPD_Api::get_images_from_album()` et `WPD_Api::get_images_from_album_recursive()`.
5. Les validateurs de choix, booléens, ratio et hauteur existent à la fois dans `WPD_Settings`, `WPD_Shortcode` et `WPD_Renderer`, avec des variantes proches.
6. Les appels de test de connexion existent dans `WPD_Plugin::test_connection()` et `WPD_Api::test_connection()`, mais le hook admin utilise une requête HTTP directe au lieu de réutiliser l'abstraction API.
7. Plusieurs règles CSS semblent héritées d'un ancien slider maison (`wp-piwigo-display-slider-track`, `wp-piwigo-display-slide`, flèches et pagination internes) alors que le rendu actuel utilise la structure Splide (`splide__track`, `splide__list`, `splide__slide`).

### Risque associé

Ces duplications ne changent pas forcément le comportement aujourd'hui, mais elles augmentent le risque de divergence : une correction appliquée à la galerie peut être oubliée dans le slider, une validation peut accepter des valeurs différentes selon la couche, et des styles morts peuvent masquer des régressions visuelles.

### Recommandations sans changement fonctionnel

- Extraire une méthode interne de normalisation d'image dans `WPD_Renderer`.
- Extraire une méthode de fusion/déduplication dans `WPD_Api`.
- Centraliser les validateurs partagés dans une classe utilitaire interne ou dans les classes existantes avec une convention unique.
- Supprimer uniquement après tests les déclarations CSS mortes ou les déplacer dans une section explicitement marquée compatibilité.

## Optimisations de cache

### Situation actuelle

Le cache stocke le résultat d'une récupération d'images avec une clé basée sur : URL Piwigo, album, `max`, récursivité et profondeur. La durée est configurable dans les réglages. Le vidage supprime les transients préfixés `wpd_album_`. Un cache mémoire limité à la requête PHP courante réutilise aussi les réponses d'images déjà obtenues avant de relire les transients ou de rappeler l'API. Un second cache mémoire, également limité à la requête courante, mémorise les réponses API Piwigo réussies par URL et paramètres afin d'éviter les appels HTTP strictement identiques pendant le rendu d'une page.

### Points faibles

1. La résolution d'un album non numérique appelle `get_all_categories()` et n'est pas mise en cache directement.
2. `get_child_categories()` et `get_all_categories()` ne bénéficient pas d'un cache dédié.
3. Deux shortcodes identiques sauf `limit`, `latest`, `random`, `sort` ou `order` peuvent réutiliser le même cache si `max=0`, mais un shortcode avec `max` crée une entrée séparée qui peut dupliquer une partie des données.
4. Les caches mémoire par requête ne stockent pas les erreurs API, afin de ne pas propager un échec temporaire comme une donnée valide.
5. Le vidage du cache parcourt les options SQL des transients standards ; il ne couvre pas explicitement les environnements avec object cache persistant si les transients ne sont pas stockés de la même manière.
6. Il n'y a pas d'index applicatif des clés générées, ce qui limite les possibilités de purge ciblée par URL ou album.

### Recommandations

- Ajouter un cache court pour la liste complète des catégories par URL Piwigo.
- Ajouter un cache court pour la résolution `album` texte/chemin vers identifiant.
- Conserver la récupération brute la plus réutilisable possible, puis appliquer tri/limite côté rendu comme aujourd'hui.
- Étudier une stratégie `max` : garder `max` seulement pour limiter les très grands albums côté API, mais documenter son impact sur la granularité de cache.
- Enregistrer un index des clés `wpd_album_*` dans une option dédiée afin de fiabiliser la purge même avec object cache persistant.
- Ne pas ajouter de cache négatif pour les erreurs réseau tant que le besoin n'est pas démontré, afin de respecter la règle actuelle de non-cache des erreurs.


### Mesure sur shortcodes identiques

Cas mesuré : une page contient plusieurs shortcodes qui ciblent le même album Piwigo par nom ou chemin, avec la même URL Piwigo, pendant une seule requête PHP. Sans cache mémoire API, chaque shortcode relançait la même requête `pwg.categories.getList` nécessaire à la résolution de l'album avant d'atteindre le cache d'images. Avec le cache mémoire API, la première réponse réussie est réutilisée jusqu'à la fin de la requête PHP.

- Avant : 3 appels API identiques `pwg.categories.getList` pour 3 shortcodes identiques.
- Après : 1 appel API `pwg.categories.getList`, puis 2 lectures du cache mémoire de requête.
- Réduction : 66,7 % d'appels API identiques sur ce scénario.

## Optimisations API

### Situation actuelle

- Les images sont récupérées par pages de 500 jusqu'à épuisement ou limite.
- Le mode récursif avec profondeur `>= 10` utilise directement `recursive=true` de Piwigo.
- Le mode récursif avec profondeur limitée récupère toutes les catégories, calcule les descendants, puis appelle les images album par album.

### Points d'attention

1. Pour un album textuel, la résolution appelle `get_all_categories()` avant la récupération d'images : sans cache catégories, chaque rendu peut faire au moins un appel API supplémentaire.
2. Pour profondeur limitée, chaque album descendant déclenche une série de requêtes images. Une branche large peut générer beaucoup d'appels.
3. Le tri et la limite sont appliqués après récupération. C'est robuste, mais potentiellement coûteux si l'utilisateur n'affiche que quelques images d'une très grande arborescence.
4. Les identifiants numériques évitent la résolution par catégories et restent la voie la plus performante.
5. La méthode de test de connexion dans `WPD_Plugin` duplique les paramètres HTTP de `WPD_Api::request()`.

### Recommandations

- Mettre en cache les catégories pour réduire les appels liés à la résolution et à la profondeur limitée.
- Réutiliser `WPD_Api::test_connection()` dans l'administration, en conservant les mêmes messages utilisateur.
- Ajouter une instrumentation optionnelle en mode debug : nombre d'appels API, cache hit/miss, durée approximative.
- Étudier les paramètres Piwigo disponibles pour demander seulement les champs nécessaires aux rendus actuels, si l'API et la compatibilité le permettent.
- Pour `sort=date` + `limit`, vérifier si Piwigo peut fournir un ordre serveur fiable avant d'envisager une limitation amont ; ne pas changer avant preuve de compatibilité.

## Optimisations de chargement des assets

### Situation actuelle

- Les assets sont enregistrés sur `wp_enqueue_scripts`.
- Les styles du plugin sont enqueued au moment du rendu galerie ou slider.
- Le script lightbox local (`wp-piwigo-display`) n'a plus de dépendance Splide et n'est enqueued que lorsque `lightbox=true`.
- Le script slider (`wp-piwigo-display-slider`) dépend de `wpd-splide` et n'est enqueued que pour les rendus `type="slider"`.
- Une galerie simple avec `lightbox="false"` ne charge aucun JavaScript côté plugin ; seul le CSS commun reste chargé pour préserver le rendu visuel.

### Points faibles restants

1. Le CSS unique contient galerie, slider, lightbox et modes visuels ; il est simple mais non segmenté.
2. Splide est fourni depuis un CDN externe, ce qui dépend du réseau client, de la politique CSP et de la disponibilité jsDelivr.
3. La lightbox locale construit un overlay global au chargement DOM dès qu'une lightbox est activée ; elle n'est pas encore initialisée paresseusement au premier clic.

### Recommandations

- Conserver le CSS unique à court terme pour limiter le risque, puis envisager une séparation `base`, `slider`, `lightbox` si les gains sont mesurés.
- Évaluer l'embarquement local de Splide ou un réglage/filtre de désactivation CDN, sans modifier le comportement par défaut avant décision mainteneur.
- Initialiser la lightbox de manière paresseuse au premier clic si l'impact JS devient mesurable.

## Sécurité et robustesse

### Points solides

- Sortie directe bloquée si `ABSPATH` n'est pas défini.
- Capacités administrateur vérifiées pour actions sensibles.
- Nonces utilisés pour vidage du cache et test de connexion.
- Attributs shortcode et options globales validés.
- Sorties HTML généralement échappées.
- HTTP limité à `http`/`https`, timeout et redirections bornés.

### Points à surveiller

- Vérifier systématiquement les échappements lors des futures extractions de méthodes de rendu.
- Conserver l'interdiction de `innerHTML` côté JS.
- Ne pas affaiblir la validation URL lors d'une éventuelle mutualisation des appels API.
- Ajouter des tests de lint PHP/JS/CSS dès que l'outillage est choisi.

## Documentation

La documentation utilisateur est déjà présente et cohérente : installation, configuration, shortcodes, récursivité, philosophie, feuille de route. L'ancien `docs/architecture.md` décrit une architecture générale, mais il ne reflète pas tous les détails 1.8/1.9 comme le cache, les presets, Splide, la lightbox, les réglages et les points d'optimisation. Le nouveau `ARCHITECTURE.md` racine sert de référence mainteneur pour la branche 1.9.x.

## Plan de consolidation recommandé

1. Mettre en place des contrôles minimaux : `php -l` sur les fichiers PHP, lint JS/CSS si outillage disponible.
2. Supprimer les duplications triviales dans `WPD_Renderer` sans changer le HTML généré.
3. Ajouter des tests de comparaison de rendu pour galerie et slider.
4. Introduire un cache catégories/résolution album derrière les mêmes sorties utilisateur.
5. Découpler les assets Splide/lightbox avec validation visuelle.
6. Nettoyer les styles morts après preuve de non-utilisation.

## Conclusion

Le dépôt est sain pour une consolidation 1.9.x : le cœur fonctionnel est court, les responsabilités sont séparées et les risques sont principalement liés à la duplication, au cache incomplet et au chargement d'assets plus large que nécessaire. Les prochaines interventions doivent rester incrémentales, testées et strictement sans changement utilisateur tant que l'audit n'a pas été validé.
