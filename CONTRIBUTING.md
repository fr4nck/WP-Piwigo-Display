# Contribuer à WP Piwigo Display

Merci de contribuer à **WP Piwigo Display**. Ce document résume les règles à respecter avant d’ouvrir une Pull Request.

## Principes de développement

Le projet définit sa manière de développer sans chercher à se comparer à d’autres projets. Les changements doivent rester compréhensibles, expliqués et relisibles dans le temps.

Le développement privilégie :

- la simplicité ;
- la robustesse ;
- la lisibilité ;
- la maintenabilité ;
- la compatibilité ;
- la mesure des modifications lorsqu’elle est pertinente.

Avant toute modification :

- comprendre le fonctionnement existant ;
- rechercher la solution la plus simple répondant au besoin ;
- éviter les dépendances inutiles ;
- éviter les traitements inutiles ;
- éviter les duplications de code ;
- préserver la compatibilité avec les versions précédentes.

Chaque modification doit pouvoir être expliquée simplement.

## Avant de commencer

- Vérifiez que votre changement traite un seul sujet.
- Préparez une Pull Request séparée pour chaque sujet distinct.
- Consultez la charte complète dans `docs/CHARTE_DEVELOPPEMENT.md`.
- Préservez la compatibilité avec les versions WordPress, PHP et Piwigo supportées par le projet.
- Identifiez les vérifications adaptées au risque du changement.

## Conventions Git

- Tous les commits sont rédigés en français.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- Une Pull Request traite un seul sujet.
- Un commit traite un seul sujet.
- Les Pull Requests de la série 1.9 doivent cibler la branche `1.9.x`.

Exemple de commit :

```text
Documente les principes de développement
```

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais, conformément aux conventions WordPress.
- Respectez les conventions WordPress pour PHP, JavaScript, CSS, HTML et internationalisation.
- Préférez une solution explicite et simple à une abstraction prématurée.
- Évitez les dépendances, traitements et duplications de code inutiles.
- Justifiez toute nouvelle dépendance dans la Pull Request.
- Validez et nettoyez les entrées utilisateur, échappez les sorties et vérifiez les permissions pour les actions sensibles.
- Ajoutez des commentaires lorsqu’ils facilitent la compréhension du fonctionnement, et non pour décrire l’évidence.

## Documentation

- Toute la documentation est rédigée en français.
- Mettez à jour la documentation dans la même Pull Request que le changement concerné.
- Les exemples doivent être exacts, reproductibles et cohérents avec le code.
- Documentez les limites connues lorsqu’elles aident à utiliser ou maintenir le plugin.
- Les identifiants techniques restent en anglais conformément aux conventions WordPress.

## Tests et vérifications

- Exécutez les tests ou vérifications adaptés au changement.
- Mentionnez les commandes exécutées et leurs résultats dans la Pull Request.
- Vérifiez en priorité les shortcodes, le rendu public, le cache, les appels API Piwigo et les réglages d’administration.
- Lorsqu’une amélioration est mesurable, fournissez une mesure avant/après ou une méthode de mesure reproductible.
- Si un test n’est pas possible, expliquez pourquoi et indiquez le risque résiduel.

## Revue de code

Avant de demander une revue, vérifiez que :

- la Pull Request traite un seul sujet ;
- chaque commit traite un seul sujet ;
- le changement respecte les principes de développement du projet ;
- les dépendances, traitements et duplications inutiles sont évités ;
- la compatibilité, la sécurité et les comportements existants sont préservés ;
- la documentation et les vérifications sont adaptées au changement ;
- les contenus de contribution sont en français et les noms de code restent en anglais ;
- les mesures avant/après sont indiquées lorsqu’elles sont pertinentes.

Les échanges de revue doivent rester précis, respectueux et orientés vers la maintenance du plugin.
