# Résilience du cache

La branche 3.x améliore le cache sans modifier le rendu des galeries.

## Principes

- le cache principal conserve la durée configurée dans WordPress ;
- une copie de secours est conservée plus longtemps ;
- si Piwigo est temporairement indisponible, le plugin peut servir la dernière réponse valide ;
- un verrou court évite plusieurs appels API simultanés pour une même galerie ;
- les caches restent séparés entre accès anonyme et compte de service.

La purge du cache supprime aussi les copies de secours et les verrous.
