# Résilience du cache

La branche 3.x vise à limiter les appels Piwigo sans affaiblir les règles d'accès.

## Fonctionnement

- Le cache principal conserve la durée configurée dans WordPress.
- **Albums publics uniquement :** une copie de secours est conservée plus longtemps et peut être utilisée quand Piwigo est momentanément indisponible.
- **Albums accessibles via le compte de service :** aucune copie de secours prolongée n'est créée ni servie ; les réponses privées ne doivent pas survivre à l'expiration du cache principal par cette voie.
- Le contexte de cache public est distinct de celui du compte de service.
- Un verrou court limite les requêtes simultanées.
- La purge du cache supprime les copies de secours et les verrous.

**Limite :** le retrait d'un droit Piwigo nécessite toujours une purge explicite du cache WordPress pour révoquer immédiatement des images déjà présentes dans le cache principal. Cette étape doit être incluse dans la recette de sécurité.

## Bornes de charge et droits éditoriaux (candidate RC4)

La résolution d’un nom ou chemin d’album utilise désormais le cache de catégories, isolé par URL et empreinte du compte de service. Les catégories ne disposent pas de copie stale. La purge et la désinstallation couvrent ces entrées sous le préfixe existant `wpd_album_`.

Une panne sans copie publique de secours est mémorisée 15 secondes avec un message générique, sans conserver l’erreur technique. Après ce délai, une nouvelle requête peut rétablir la galerie. Un client authentifié est réutilisé uniquement dans la même requête PHP ; les cookies de session ne sont pas persistés entre les requêtes.

Les requêtes servies uniquement depuis le cache produisent au plus un échantillon de métriques par minute. Les totaux HIT/MISS sont donc indicatifs. Les véritables appels API continuent d’être enregistrés immédiatement. Le test `metrics-write-budget.php` compare 100 vues en cache : une écriture au lieu de 100 dans ce scénario séquentiel. Un trafic concurrent peut encore produire plusieurs écritures ; il ne s’agit pas d’un verrou global.

Le sélecteur privé nécessite `publish_posts`, `publish_pages` ou `manage_options`. Un contributeur conserve le sélecteur public, limité aux albums visibles sans authentification. Les prévisualisations éditoriales et REST d’un contributeur utilisent le contexte anonyme : elles ne peuvent pas lire les résultats privés mis en cache. Les galeries volontairement publiées conservent leur rendu public.

## Contre-revue RC3 : quotas locaux et grandes réponses

Le budget API est partagé par endpoint pendant une requête PHP. Son épuisement
renvoie `wpd_api_budget_exceeded` : cette erreur reste dans le cache de la
requête, sans transient de 15 secondes. La requête WordPress suivante dispose
de son propre budget. Le login du compte de service et le repli public GET
après HTTP 403 conservent cette origine locale. Les vraies pannes amont gardent
leur cache négatif court ; le repli stale reste réservé au contexte public.
Les clés et les règles d'autorisation public/compte de service ne changent pas.

Pour la récursion de profondeur 1 à 9, les clients partagent la collection des
images uniques et s'arrêtent à `max`. Chaque page contient au plus
`min(500, max)` images lorsque `max > 0`. La taille de page reste fixe dans
chaque album : utiliser uniquement le nombre restant ferait consommer trop
d'appels lorsqu'un descendant commence par beaucoup d'images déjà rencontrées.
Les bornes HTTP, temps et lignes, doublons compris, restent applicables à
`max=0` ; une liste incomplète n'est pas présentée comme un succès.

Les réponses HTTP sont limitées à 8 Mio. Le récupérateur de JSON contaminé
par du HTML/JavaScript reste volontairement limité à 4 Mio, corps entier
compris. Un JSON propre de 4,1 ou 7,9 Mio peut fonctionner, tandis que sa version
contaminée est refusée. Cette asymétrie conserve le budget CPU/mémoire du
scanner ; le plafond n'a pas été augmenté par la contre-revue. Les fixtures
`tests/bench/response-volume.php` et `tests/e2e/f04-response-volume.php`
qualifient explicitement cette limite, y compris avec Piwigo/OSM actifs.

Le plafond HTTP de 8 Mio ne garantit pas que toutes les catégories décodées
puissent être conservées puis sérialisées en transient sous 128 Mio de mémoire
PHP. Les mesures F-03 doivent distinguer décodage, résolution et écriture du
cache WordPress. Aucun plafond arbitraire de nombre de catégories n'est ajouté.
La saturation observée dans le banc WordPress à 90 000 petites catégories
reste une réserve de qualification, et ne doit pas disparaître derrière une
CI verte. Les mesures et le verdict de la campagne sont suivis dans la PR #162.
