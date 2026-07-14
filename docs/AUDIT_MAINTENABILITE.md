# Audit de maintenabilité — WP Piwigo Display

## Périmètre

Cet audit prépare la release `1.9.0` en identifiant uniquement des corrections ou simplifications de maintenabilité. Il ne propose ni nouvelle fonctionnalité, ni réécriture complète, ni changement de comportement utilisateur volontaire.

Fichiers examinés :

- `wp-piwigo-display.php` ;
- `includes/class-wpd-plugin.php` ;
- `includes/class-wpd-settings.php` ;
- `includes/class-wpd-api.php` ;
- `includes/class-wpd-cache.php` ;
- `includes/class-wpd-diagnostic.php` ;
- `includes/class-wpd-renderer.php` ;
- `includes/class-wpd-shortcode.php` ;
- `assets/js/wp-piwigo-display.js` ;
- `assets/js/wp-piwigo-display-slider.js` ;
- `assets/css/wp-piwigo-display.css` ;
- documentation existante du dépôt.

## Synthèse priorisée

| Priorité | Point | Fichier principal | Effort |
| --- | --- | --- | --- |
| P0 | Sécuriser la base de vérification avant 1.9.0 | dépôt / outillage | Moyen |
| P1 | Mutualiser la validation des dimensions, ratios, booléens et choix | `includes/class-wpd-settings.php`, `includes/class-wpd-shortcode.php`, `includes/class-wpd-renderer.php` | Moyen |
| P1 | Réduire la duplication de préparation des images rendues | `includes/class-wpd-renderer.php` | Moyen |
| P1 | Réutiliser l'abstraction API pour le test de connexion admin | `includes/class-wpd-plugin.php`, `includes/class-wpd-api.php` | Traité |
| P2 | Extraire la déduplication d'images API | `includes/class-wpd-api.php` | Traité |
| P2 | Clarifier la stratégie de cache autour de `max`, `limit`, `latest` et `random` | `includes/class-wpd-cache.php`, `includes/class-wpd-shortcode.php`, `includes/class-wpd-renderer.php` | Moyen |
| P2 | Fiabiliser le nettoyage des transients en environnement avec cache objet | `includes/class-wpd-cache.php` | Moyen |
| P2 | Simplifier les rendus admin très longs | `includes/class-wpd-settings.php`, `includes/class-wpd-diagnostic.php` | Moyen |
| P3 | Nettoyer les petits écarts de style et de lisibilité | `includes/class-wpd-api.php`, `includes/class-wpd-renderer.php` | Faible |
| P3 | Identifier le CSS potentiellement historique avant suppression | `assets/css/wp-piwigo-display.css` | Faible |
| P3 | Clarifier l'initialisation globale de la lightbox | `assets/js/wp-piwigo-display.js` | Faible |

---

## P0 — Sécuriser la base de vérification avant 1.9.0

### Fichier concerné

- Dépôt complet, avec priorité sur `wp-piwigo-display.php`, `includes/*.php`, `assets/js/*.js` et `assets/css/*.css`.

### Problème observé

Le dépôt ne présente pas encore de commande unique évidente pour valider automatiquement les non-régressions de maintenabilité avant une correction. Les contrôles minimaux restent donc manuels : syntaxe PHP, absence de modification involontaire des assets, cohérence du rendu HTML et comportement des shortcodes.

### Bénéfice attendu

- Réduire le risque de régression avant `1.9.0`.
- Rendre les petites corrections de duplication plus sûres.
- Donner un critère d'acceptation stable aux futures Pull Requests.

### Risque

- Faible risque fonctionnel si l'ajout se limite à documenter ou configurer des contrôles existants.
- Risque moyen si l'outillage impose des règles de style trop strictes et déclenche de gros changements mécaniques.

### Effort estimé

Moyen.

### Méthode de vérification

- Exécuter `php -l` sur chaque fichier PHP.
- Exécuter les linters JS/CSS seulement si l'outillage est déjà disponible dans le dépôt ou dans l'environnement CI.
- Vérifier que la Pull Request ne contient pas de modification de comportement applicatif.

---

## P1 — Mutualiser la validation des dimensions, ratios, booléens et choix

### Fichier concerné

- `includes/class-wpd-settings.php`
- `includes/class-wpd-shortcode.php`
- `includes/class-wpd-renderer.php`

### Problème observé

Les mêmes familles de validation sont présentes à plusieurs endroits : choix autorisés, booléens, ratio `16/9`, hauteur CSS, ordre de tri, mode d'affichage, navigation et style. Les règles sont proches, mais pas centralisées. Cette dispersion augmente le risque qu'une valeur soit acceptée dans les réglages puis traitée différemment dans le shortcode ou dans le rendu.

### Bénéfice attendu

- Réduire les divergences entre réglages, shortcode et rendu.
- Faciliter les corrections sans devoir modifier trois classes.
- Simplifier la lecture des classes métier.

### Risque

- Moyen : une mutualisation trop large peut modifier subtilement les valeurs par défaut ou les valeurs invalides acceptées.
- Le risque doit être limité en gardant les mêmes listes autorisées et les mêmes valeurs de repli.

### Effort estimé

Moyen.

### Méthode de vérification

- Comparer les sorties de `WPD_Settings::sanitize_options()` avant/après sur un jeu de valeurs valides et invalides.
- Comparer les attributs normalisés de `WPD_Shortcode::sanitize_atts()` avant/après sur les shortcodes documentés.
- Vérifier manuellement les rendus galerie et slider avec hauteur, ratio, fit, navigation, style, lightbox et caption.

---

## P1 — Réduire la duplication de préparation des images rendues

### Fichier concerné

- `includes/class-wpd-renderer.php`

### Problème observé

La galerie et le slider reconstruisent séparément les mêmes informations d'image : URL principale, URL large, titre, description, mode de légende et texte de lightbox. Les méthodes d'aide existent déjà, mais l'assemblage reste dupliqué dans les boucles de rendu.

### Bénéfice attendu

- Éviter qu'une correction de légende, d'URL ou d'attribut `alt` soit appliquée à un rendu mais oubliée dans l'autre.
- Raccourcir les méthodes `render_gallery()` et `render_slider()`.
- Faciliter les tests de rendu sur un format d'image préparé unique.

### Risque

- Moyen : le rendu HTML ne doit pas changer, notamment les classes CSS, attributs `data-wpd-title`, fallback d'URL et conditions d'affichage des légendes.

### Effort estimé

Moyen.

### Méthode de vérification

- Générer une galerie et un slider avec les mêmes données d'image avant/après, puis comparer le HTML significatif.
- Vérifier les cas image sans `medium`, sans `large`, sans titre et avec description.
- Vérifier que la lightbox reçoit toujours le même texte de légende.

---

## P1 — Réutiliser l'abstraction API pour le test de connexion admin

### Fichier concerné

- `includes/class-wpd-plugin.php`
- `includes/class-wpd-api.php`

### Problème observé

Le test de connexion de l'administration construit sa propre requête HTTP vers `pwg.session.getStatus`, avec timeout, redirections, user-agent, décodage JSON et interprétation du statut. `WPD_Api` possède déjà une méthode `test_connection()` qui passe par la méthode commune de requête.

### Bénéfice attendu

- Une seule logique d'appel API à maintenir.
- Des erreurs HTTP et JSON cohérentes entre le test admin et les autres appels Piwigo.
- Moins de duplication autour des paramètres réseau.

### Risque

- Faible à moyen : les codes de résultat affichés dans l'interface admin doivent rester compréhensibles et compatibles avec les notices actuelles.

### Effort estimé

Faible.

### Statut

Traité sur la branche `1.9.x` : l'action d'administration réutilise `WPD_Api::test_connection()` et conserve les mêmes paramètres de redirection ainsi que les mêmes messages utilisateur.

### Méthode de vérification

- Tester l'action `wpd_test_connection` avec URL manquante, URL invalide, galerie indisponible et galerie valide.
- Vérifier que la redirection admin conserve un paramètre `wpd_connection_test` attendu.
- Vérifier que les messages utilisateur restent non techniques.

---

## P2 — Extraire la déduplication d'images API

### Fichier concerné

- `includes/class-wpd-api.php`

### Problème observé

La récupération d'images simple et la récupération récursive limitée utilisent la même stratégie de déduplication : identifiant Piwigo si disponible, sinon hash du tableau image. Cette logique est répétée dans deux méthodes.

### Bénéfice attendu

- Une correction unique si la stratégie de clé doit évoluer.
- Des méthodes API plus courtes.
- Un comportement identique entre album simple et album récursif.

### Risque

- Faible : le changement peut rester interne si la méthode extraite conserve exactement la même clé.

### Effort estimé

Faible.

### Statut

Traité sur la branche `1.9.x` : la clé de déduplication historique, basée sur l'identifiant Piwigo puis sur `md5(wp_json_encode($image))`, est maintenant centralisée dans une méthode interne commune.

### Méthode de vérification

- Tester un album contenant des images avec identifiants distincts.
- Tester une réponse simulée avec image sans identifiant.
- Vérifier que le nombre d'images retournées et l'ordre restent identiques.

---

## P2 — Clarifier la stratégie de cache autour de `max`, `limit`, `latest` et `random`

### Fichier concerné

- `includes/class-wpd-cache.php`
- `includes/class-wpd-shortcode.php`
- `includes/class-wpd-renderer.php`

### Problème observé

Le paramètre `max` intervient dans la récupération et donc dans la clé de cache, tandis que `limit`, `latest`, `random`, `sort` et `order` sont appliqués au moment de préparer le rendu. Cette séparation est pertinente, mais elle peut être mal comprise et créer des entrées de cache distinctes pour des jeux d'images partiellement redondants.

### Bénéfice attendu

- Rendre le comportement plus prévisible pour les mainteneurs.
- Éviter des modifications futures qui mélangeraient limite API et limite d'affichage.
- Limiter la duplication de données en cache si une règle commune est documentée ou testée.

### Risque

- Moyen si une correction change le volume d'images récupérées ou l'ordre d'application des limites.
- Faible si la première étape se limite à documenter et tester le comportement existant.

### Effort estimé

Moyen.

### Méthode de vérification

- Comparer les clés de cache générées pour deux shortcodes identiques sauf `limit`.
- Comparer les clés de cache générées pour deux shortcodes identiques sauf `max`.
- Vérifier que `latest`, `random` et `limit` n'entraînent pas d'appel API supplémentaire lorsque `max` ne change pas.

---

## P2 — Fiabiliser le nettoyage des transients en environnement avec cache objet

### Fichier concerné

- `includes/class-wpd-cache.php`

### Problème observé

Le vidage du cache recherche les transients `wpd_album_*` dans la table des options WordPress. Cette méthode convient aux transients stockés en base, mais elle peut être moins fiable lorsque WordPress s'appuie sur un cache objet persistant qui ne matérialise pas toutes les clés de transient de la même façon.

### Bénéfice attendu

- Purge plus fiable en production.
- Meilleure prévisibilité du bouton « Vider le cache ».
- Moins de support lié à des images Piwigo qui semblent rester en cache.

### Risque

- Moyen : maintenir un index applicatif de clés ajoute une donnée à synchroniser.
- Il faut éviter de supprimer des transients externes au plugin.

### Effort estimé

Moyen.

### Méthode de vérification

- Créer plusieurs caches d'albums, vider le cache, puis vérifier que les clés indexées disparaissent.
- Vérifier que le compteur affiché reste cohérent.
- Tester avec transients expirés et non expirés.

---

## P2 — Simplifier les rendus admin très longs

### Fichier concerné

- `includes/class-wpd-settings.php`
- `includes/class-wpd-diagnostic.php`

### Problème observé

Les pages d'administration mélangent la logique de contrôle, les formulaires, les notices, les champs et de grands blocs HTML/PHP. Le fichier des réglages est particulièrement dense car il contient à la fois l'enregistrement des options, leur sanitization et tout le rendu de page.

### Bénéfice attendu

- Faciliter les corrections sur un champ de réglage sans parcourir toute la page.
- Réduire le risque d'erreur d'échappement lors d'une modification future.
- Garder une séparation plus claire entre données, validation et rendu.

### Risque

- Moyen : déplacer du rendu peut casser un nom de champ, une nonce ou une action admin.
- Ne pas transformer cette simplification en refonte d'architecture.

### Effort estimé

Moyen.

### Méthode de vérification

- Sauvegarder chaque réglage et vérifier sa valeur persistée.
- Tester les notices de cache vidé et de test de connexion.
- Exporter le diagnostic et comparer les rubriques présentes.

---

## P3 — Nettoyer les petits écarts de style et de lisibilité

### Fichier concerné

- `includes/class-wpd-api.php`
- `includes/class-wpd-renderer.php`

### Problème observé

Quelques écarts mineurs de style compliquent inutilement la lecture : lignes vides doubles, accolade finale précédée d'une ligne vide, et méthodes utilitaires très condensées ailleurs dans le dépôt. Ces points ne sont pas bloquants, mais ils créent du bruit lors des revues.

### Bénéfice attendu

- Diffs plus propres.
- Lecture plus homogène.
- Réduction du bruit dans les futures corrections.

### Risque

- Faible si les changements restent purement cosmétiques.
- À traiter séparément des corrections fonctionnelles pour ne pas brouiller les revues.

### Effort estimé

Faible.

### Méthode de vérification

- Exécuter `php -l` sur les fichiers PHP touchés.
- Vérifier que les diffs ne changent aucune instruction métier.

---

## P3 — Identifier le CSS potentiellement historique avant suppression

### Fichier concerné

- `assets/css/wp-piwigo-display.css`

### Problème observé

Le CSS couvre plusieurs modes : galerie, slider Splide, lightbox, miniatures et variantes visuelles. Certaines règles peuvent provenir d'un ancien rendu ou d'une compatibilité implicite. Les supprimer sans inventaire visuel pourrait provoquer une régression discrète.

### Bénéfice attendu

- Feuille de style plus courte.
- Moins de conflits avec les thèmes WordPress.
- Meilleure lisibilité des styles réellement utilisés par le HTML actuel.

### Risque

- Moyen : une classe apparemment inutilisée peut être consommée par un filtre, un thème enfant ou une intégration existante.

### Effort estimé

Faible pour l'inventaire, moyen pour une suppression validée visuellement.

### Méthode de vérification

- Rechercher chaque sélecteur dans les rendus PHP et JS.
- Capturer des pages de test galerie, slider avec miniatures, slider avec points, lightbox, styles `theme`, `minimal` et `none`.
- Supprimer uniquement les règles prouvées inutilisées ou les isoler dans une section de compatibilité commentée.

---

## P3 — Clarifier l'initialisation globale de la lightbox

### Fichier concerné

- `assets/js/wp-piwigo-display.js`

### Problème observé

La lightbox crée un overlay global dès le chargement du DOM lorsqu'au moins un lien compatible est présent. Cette approche reste simple, mais le comportement global doit rester clair si plusieurs galeries coexistent sur une même page.

### Bénéfice attendu

- Maintenir une logique JS facile à raisonner.
- Prévenir les régressions si le rendu galerie/slider évolue.
- Faciliter un éventuel test manuel multi-galeries sans changer le comportement actuel.

### Risque

- Faible si l'on se limite à documenter ou tester le comportement.
- Moyen si l'on rend l'overlay paresseux ou instancié par galerie, car la navigation entre images pourrait changer.

### Effort estimé

Faible.

### Méthode de vérification

- Tester une page avec une galerie seule.
- Tester une page avec deux galeries.
- Tester une page avec un slider et une galerie, lightbox activée.
- Vérifier fermeture par bouton, clic overlay, touche Échap et navigation clavier.

---

## Ordre recommandé avant la release 1.9.0

1. Mettre en place ou documenter les vérifications minimales de syntaxe et de rendu.
2. Corriger les duplications à faible risque : test de connexion admin, déduplication API, petites incohérences de style.
3. Mutualiser progressivement les validateurs sans changer les valeurs acceptées.
4. Réduire la duplication dans `WPD_Renderer` avec comparaison stricte du HTML produit.
5. Clarifier la stratégie de cache avant toute optimisation plus invasive.
6. Reporter toute suppression CSS ou modification JS non indispensable après validation visuelle.

## Conclusion

La base de code est globalement lisible et modulaire. Les prochaines corrections utiles avant `1.9.0` doivent rester incrémentales : réduire les duplications, consolider les validations, clarifier le cache et sécuriser les vérifications. Les points ci-dessus privilégient des interventions limitées et vérifiables, sans nouvelle fonctionnalité ni réécriture complète.
