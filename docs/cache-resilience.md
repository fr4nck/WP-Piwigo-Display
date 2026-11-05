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
