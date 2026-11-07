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
