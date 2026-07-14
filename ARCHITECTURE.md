# Architecture mainteneur — WP Piwigo Display 1.9.x

## Objectif du plugin

WP Piwigo Display affiche dans WordPress des images hébergées par Piwigo, sans les importer dans la médiathèque WordPress. Le plugin reste volontairement léger : Piwigo conserve la responsabilité de stockage, classement et génération des dérivés d'images ; WordPress se limite à la configuration, au cache et au rendu front.

## Flux principal

```text
Shortcode [piwigo]
    │
    ▼
Fusion des valeurs par défaut et presets
    │
    ▼
Validation des attributs
    │
    ▼
Résolution de l'album Piwigo
    │
    ▼
Lecture cache transient
    │          │
    │          └── miss → appels API Piwigo → écriture cache
    ▼
Préparation des images : tri, ordre, limite
    │
    ▼
Rendu HTML galerie ou slider
    │
    ▼
Chargement conditionnel CSS/JS
    │
    ▼
Lightbox locale et/ou Splide côté navigateur
```

## Organisation des fichiers

```text
wp-piwigo-display.php              Point d'entrée WordPress du plugin
includes/class-wpd-plugin.php      Hooks, assets, actions admin
includes/class-wpd-settings.php    Options, page réglages, sanitization admin
includes/class-wpd-shortcode.php   Shortcode, presets, validation, orchestration
includes/class-wpd-api.php         Client API Piwigo
includes/class-wpd-cache.php       Cache transients WordPress
includes/class-wpd-renderer.php    Préparation images et rendu HTML
assets/css/wp-piwigo-display.css   Styles galerie, slider, lightbox, modes visuels
assets/js/wp-piwigo-display.js     Initialisation Splide et lightbox locale
docs/                             Documentation utilisateur existante
AUDIT.md                          Audit technique de consolidation 1.9.x
ARCHITECTURE.md                   Référence mainteneur de l'architecture actuelle
```

## Responsabilités par composant

### `wp-piwigo-display.php`

- Déclare les métadonnées WordPress du plugin.
- Définit `WPD_VERSION`, `WPD_PLUGIN_FILE`, `WPD_PLUGIN_DIR` et `WPD_PLUGIN_URL`.
- Charge les classes PHP internes.
- Initialise le plugin sur `plugins_loaded`.

### `WPD_Plugin`

Responsabilité : connecter les classes internes à WordPress.

Hooks principaux :

- `init` pour les traductions et le shortcode ;
- `wp_enqueue_scripts` pour enregistrer les assets front ;
- `admin_init` et `admin_menu` pour les réglages ;
- `admin_post_wpd_clear_cache` pour vider le cache ;
- `admin_post_wpd_test_connection` pour tester l'accès à Piwigo.

Les assets sont enregistrés globalement, mais enqueued par le renderer seulement lorsqu'un shortcode est effectivement rendu.

### `WPD_Settings`

Responsabilité : gérer la configuration persistée.

Options couvertes :

- URL Piwigo ;
- identifiant et mot de passe éventuels ;
- durée de cache ;
- paramètres d'affichage par défaut ;
- options de tri, ordre, limite ;
- mode debug ;
- légendes ;
- style visuel.

La classe expose également des accesseurs statiques utilisés par le shortcode, le cache et le renderer.

### `WPD_Shortcode`

Responsabilité : transformer un shortcode utilisateur en rendu HTML.

Étapes :

1. construire les valeurs par défaut avec `WPD_Settings::get_shortcode_defaults()` ;
2. laisser le filtre `wp_piwigo_display_shortcode_defaults` modifier ces valeurs ;
3. appliquer les presets `galerie`, `slider`, `actualites` ;
4. valider les attributs ;
5. vérifier l'album et l'URL Piwigo ;
6. résoudre l'album en identifiant numérique ;
7. récupérer les images via `WPD_Cache` ;
8. déléguer à `WPD_Renderer` ;
9. ajouter un bloc debug pour les administrateurs si activé.

### `WPD_Api`

Responsabilité : isoler les échanges avec l'API Piwigo.

Méthodes principales :

- récupération paginée des images d'un album ;
- récupération récursive native lorsque la profondeur équivaut à toute la descendance ;
- calcul des descendants pour une profondeur limitée ;
- récupération des catégories ;
- résolution d'un album par identifiant, nom, chemin ou permalink ;
- test de connexion ;
- validation de l'URL de base.

Les requêtes HTTP utilisent `wp_remote_post()` vers `/ws.php?format=json`, avec timeout, redirections limitées et user-agent du plugin.

### `WPD_Cache`

Responsabilité : éviter des appels API répétitifs.

La clé de cache album inclut :

- URL Piwigo normalisée ;
- identifiant album ;
- limite API `max` ;
- mode récursif ;
- profondeur.

Le cache stocke les images brutes récupérées depuis Piwigo. Le tri final, l'ordre et la limite d'affichage restent appliqués après lecture cache par `WPD_Renderer`.

### `WPD_Renderer`

Responsabilité : préparer les images et produire le HTML.

Fonctions :

- tri `manual`, `date`, `name`, `id`, `random` ;
- compatibilité `latest`, `random`, `max`, `limit` ;
- rendu galerie ;
- rendu slider Splide ;
- légendes selon réglage global ou attribut shortcode ;
- choix des dérivés Piwigo medium/small/thumb/large ;
- classes d'orientation portrait/paysage ;
- attributs de lightbox ;
- enqueue des assets nécessaires.

Le renderer ne doit pas appeler directement l'API Piwigo.

### Assets front

#### CSS

Le fichier CSS unique contient :

- grille galerie ;
- styles slider ;
- styles lightbox ;
- miniatures ;
- responsive mobile ;
- mode `raw` ;
- modes de style `theme`, `minimal`, `none`.

#### JavaScript

Le fichier JS réalise deux initialisations au `DOMContentLoaded` :

- initialisation de tous les sliders `.wp-piwigo-display-slider.splide` si `Splide` est disponible ;
- construction d'une lightbox globale pour les liens opt-in via `data-wpd-lightbox="true"` dans les conteneurs activés.

## Modèle de données attendu depuis Piwigo

Le renderer s'appuie principalement sur :

- `id` pour la déduplication ;
- `name` ou `file` pour le titre ;
- `comment` ou `description` pour la légende ;
- `width` et `height` pour l'orientation ;
- `date_available` ou `date_creation` pour le tri date ;
- `derivatives.medium.url`, `small.url`, `thumb.url`, `large.url` ;
- `element_url` en fallback.

## Extension points

Deux filtres structurants existent :

- `wp_piwigo_display_shortcode_defaults` pour ajuster les valeurs par défaut ;
- `wp_piwigo_display_render` pour remplacer le rendu HTML final.

Ces points d'extension doivent être préservés dans toute refactorisation 1.9.x.

## Contraintes de compatibilité

La branche 1.9.x est une branche de consolidation. Jusqu'à validation explicite :

- ne pas changer les shortcodes publics ;
- ne pas changer les valeurs par défaut ;
- ne pas changer le HTML/CSS attendu sans test visuel ;
- ne pas supprimer de paramètres historiques (`max`, `latest`, `random`) ;
- ne pas modifier les messages utilisateur sauf correction documentée ;
- ne pas rendre le CDN Splide obligatoire d'une manière différente du comportement actuel.

## Zones de dette technique

- Duplications dans le renderer galerie/slider.
- Duplications entre validation admin, shortcode et renderer.
- Cache non appliqué à la liste des catégories et à la résolution d'albums textuels.
- Couplage du script principal à Splide même quand seule une galerie est affichée.
- CSS potentiellement hérité de l'ancien slider interne.
- Absence d'outillage de test automatisé dans le dépôt.

## Règles pour les prochains commits 1.9.x

1. Un commit doit traiter un sujet de consolidation clairement borné.
2. Tout changement PHP doit conserver les signatures publiques existantes sauf décision mainteneur.
3. Tout changement de rendu doit être accompagné d'une justification et, si possible, d'une capture ou d'un test de sortie HTML.
4. Les optimisations de cache/API/assets doivent être introduites derrière un comportement utilisateur identique.
5. La documentation doit être mise à jour avec chaque décision d'architecture durable.
