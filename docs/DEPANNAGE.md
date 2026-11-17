# Dépannage — Piwigo Display V3

Ce guide couvre la maintenance 3.0.1 de Piwigo Display. Certaines fonctions expérimentales de la branche 3.1 ne sont pas disponibles en 3.0.1.

## Avant de chercher plus loin

1. Vérifier que WordPress et Piwigo sont accessibles normalement.
2. Vérifier l’URL Piwigo enregistrée dans **Piwigo Display → Réglages**.
3. Lancer le test de connexion.
4. Ouvrir **Piwigo Display → Diagnostic** et contrôler le bloc **Santé API & cache**.
5. Si le comportement paraît ancien ou incohérent, purger le cache Piwigo Display puis retester une seule fois.

## Le test de connexion échoue

- vérifier que l’URL pointe vers la racine de Piwigo, pas vers une page d’album ;
- pour le compte de service, utiliser une URL **HTTPS** valide ;
- vérifier que le serveur WordPress peut joindre la galerie Piwigo ;
- vérifier que `ws.php?format=json` n’est pas bloqué par un pare-feu, une protection anti-bot ou une règle d’hébergement ;
- si un compte de service est utilisé, vérifier son identifiant, son mot de passe et ses droits Piwigo.

Un shortcode utilisant une URL Piwigo différente de celle configurée reste volontairement anonyme : le compte de service n’est jamais envoyé vers une autre galerie.

## L’explorateur d’albums est vide ou affiche une erreur

- tester d’abord la connexion dans les réglages ;
- vérifier que le compte utilisé peut réellement voir les albums attendus ;
- essayer la saisie manuelle par identifiant, nom ou chemin : elle reste disponible même si l’explorateur ne peut pas charger l’arborescence ;
- purger le cache puis refaire un seul essai ;
- consulter le diagnostic pour distinguer erreur HTTP, réponse Piwigo invalide et erreur API.

Pour une grosse photothèque, la recherche par nom ou chemin est préférable à l’ouverture de nombreuses branches de l’arborescence.

## « La galerie Piwigo a renvoyé une réponse illisible »

Piwigo répond normalement en JSON sur son API `ws.php`. Certains plugins Piwigo peuvent toutefois ajouter accidentellement du HTML ou du JavaScript autour du JSON. Le cas a notamment été observé avec un affichage utilisant OpenStreetMap.

Piwigo Display inclut une couche de compatibilité qui tente de récupérer **uniquement un objet JSON Piwigo complet contenant le champ `stat`**. Cette récupération est strictement limitée :

- aux requêtes HTTP émises par Piwigo Display ;
- à l’endpoint Piwigo `ws.php` demandé en `format=json` ;
- les autres requêtes HTTP de WordPress sont rendues inchangées.

Si la réponse ne contient pas de JSON Piwigo complet et valide, Piwigo Display ne tente pas de reconstruire ou d’inventer les données : l’erreur « réponse illisible » reste affichée.

En cas de réponse altérée par une extension Piwigo, une vérification sur la photothèque réellement affectée reste nécessaire avant de considérer l'incident résolu.

## Une galerie ne se met pas à jour immédiatement

Piwigo Display utilise un cache WordPress pour éviter des appels répétés à Piwigo.

Dans **Diagnostic** :

- **Appels API** indique les appels réellement effectués vers Piwigo ;
- **HIT** indique une réponse servie depuis le cache ;
- **MISS** indique qu’un nouvel accès aux données a été nécessaire ;
- **Purger le cache** force la récupération lors du prochain affichage.

Une purge est utile après une modification importante de la photothèque ou pour isoler un problème, mais elle ne doit pas devenir une opération normale à chaque affichage.

## Le slider ne démarre pas comme prévu

Le slider utilise Splide lorsqu’il est disponible. Piwigo Display fournit également un fallback natif : si Splide tarde à se charger ou échoue, les images doivent rester consultables avec des commandes de navigation de base.

À vérifier :

- absence d’erreur JavaScript dans la console ;
- présence des fichiers CSS/JS du plugin dans le ZIP installé ;
- comportement avec et sans cache navigateur ;
- option `prefers-reduced-motion` du système : elle désactive volontairement l’autoplay et réduit ou supprime les transitions.

## Que fournir dans un rapport de bug

Indiquer si possible :

- version exacte de Piwigo Display ;
- version WordPress ;
- version PHP ;
- version Piwigo ;
- navigateur concerné ;
- mode utilisé : Gutenberg, Classic Editor, composeur d’administration ou shortcode ;
- type d’affichage : galerie, slider, Masonry ou autre disposition disponible dans la version installée ;
- message d’erreur exact ;
- étapes minimales pour reproduire ;
- export du diagnostic lorsque cela aide à comprendre le problème.

Avec `WP_DEBUG`, relever également les avertissements ou erreurs correspondant au moment du problème.

## Informations à ne pas publier

Ne jamais joindre dans un ticket, un forum ou une capture publique :

- mot de passe du compte de service ;
- cookie de session Piwigo ;
- secret ou identifiant privé ;
- corps de requête contenant des données sensibles.

Les métriques **Santé API & cache** sont conçues pour rester agrégées et ne pas conserver les identifiants, mots de passe ou corps de requête.

## Avant toute publication de version

La checklist complète reste `docs/RECETTE-3X.md`. Les contrôles automatisés ne remplacent pas la recette réelle des trois interfaces WordPress, du sélecteur d’albums, du clavier, du responsive et du cas OpenStreetMap signalé par une utilisatrice.

## Sélecteur d’albums : session expirée

Un éditeur laissé ouvert longtemps peut recevoir « La session du sélecteur d’albums a expiré ». Sauvegardez votre travail, puis rechargez la page avant de rouvrir la liste. Le bouton d’actualisation des albums ne renouvelle pas le jeton de sécurité WordPress. La sélection saisie reste conservée ; un refus d’autorisation distinct ne doit pas être présenté comme une expiration.

La recette automatisée `editor-security.yml` exerce les composants React/Gutenberg et jQuery réels, le nonce expiré et les albums public/privé homonymes sur deux Piwigo jetables. Les résultats sont à vérifier sur le SHA exact de chaque branche ; ils ne remplacent pas la recette de l’installation de l’utilisatrice.

## Albums accentués et extensions Unicode du serveur

La recherche par nom, chemin ou permalien utilise `mbstring` pour la casse Unicode et `intl` (`Normalizer`) pour les formes composées/décomposées. Sans `mbstring`, un catalogue contenant des caractères non ASCII est refusé pour une recherche textuelle ; sans `intl`, la présence de caractères combinatoires entraîne le même refus conservateur. Un nom ASCII peut donc aussi être refusé si un autre album du catalogue rend la comparaison incertaine.

Le message invite alors à utiliser l’identifiant numérique Piwigo. Cet identifiant reste utilisable sans ces deux extensions. Les noms ASCII restent comparables sur un catalogue entièrement ASCII ; l’absence d’`intl` seule conserve la comparaison des accents précomposés lorsque `mbstring` est présent. Un nom ambigu ne doit jamais sélectionner automatiquement un album privé.

La matrice `unicode-fallback.yml` vérifie PHP 8.1 et 8.4 avec les deux extensions, sans `mbstring`, sans `intl`, puis sans les deux. Elle contrôle les extensions réellement chargées et appelle les résolveurs de production avec un catalogue de test, dans les deux ordres et avec le cache froid puis chaud. Ce contrôle ciblé ne remplace pas les essais WordPress/Piwigo réels.
