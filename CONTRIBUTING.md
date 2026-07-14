# Contribuer à WP Piwigo Display

Merci de contribuer à **WP Piwigo Display**. Ce document résume les règles à respecter avant d'ouvrir une Pull Request.

## Principes de conception

WP Piwigo Display est développé selon des principes d'ingénierie visant à produire un logiciel simple, robuste et durable.

Les règles du projet sont les suivantes :

- Chaque ligne de code doit justifier son existence.
- Chaque dépendance doit apporter une valeur démontrable.
- Chaque requête HTTP, SQL ou API doit être utile.
- Chaque optimisation doit être mesurée lorsqu'elle est quantifiable.
- La solution la plus simple est privilégiée lorsqu'elle répond au besoin.
- Les ressources (CPU, mémoire, réseau et stockage) sont utilisées avec discernement.
- La rétrocompatibilité est préservée autant que possible.
- Le code doit rester compréhensible par un développeur découvrant le projet plusieurs années plus tard.
- Avant d'ajouter du code, toujours rechercher si du code peut être supprimé ou simplifié.

## Avant de commencer

- Vérifiez que votre changement correspond à une seule responsabilité.
- Préparez une Pull Request séparée pour chaque sujet distinct.
- Consultez la charte complète dans `docs/CHARTE_DEVELOPPEMENT.md`.
- Préservez la compatibilité avec les versions WordPress et PHP supportées par le projet.
- Recherchez si le besoin peut être satisfait en supprimant, simplifiant ou réutilisant du code existant.

## Conventions Git

- Tous les commits sont rédigés en français.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- Une Pull Request = une seule responsabilité.
- Un commit = une seule responsabilité.
- Les Pull Requests de la série 1.9 doivent cibler la branche `1.9.x`.

Exemple de commit :

```text
Ajoute la documentation de contribution
```

## Pull Requests

Chaque Pull Request doit répondre aux questions suivantes :

- Quel problème est résolu ?
- Quel bénéfice apporte cette modification ?
- Quel impact technique apporte-t-elle ?
- Quel est son impact mesurable sur les performances ou la qualité ?
- Comment ce bénéfice a-t-il été vérifié ?

Une Pull Request ne traite qu'un seul sujet.

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais, conformément aux conventions WordPress.
- Respectez les conventions WordPress pour PHP, JavaScript, CSS, HTML et internationalisation.
- Préférez une solution explicite et simple à une abstraction prématurée.
- Justifiez toute nouvelle dépendance par une valeur démontrable dans la Pull Request.
- Validez et nettoyez les entrées utilisateur, échappez les sorties et vérifiez les permissions pour les actions sensibles.

## Documentation

- Toute la documentation est rédigée en français.
- Mettez à jour la documentation dans la même Pull Request que le changement concerné.
- Les exemples doivent être exacts, reproductibles et cohérents avec le code.
- Documentez les limites connues lorsqu'elles aident à utiliser ou maintenir le plugin.

## Tests et vérifications

- Exécutez les tests ou vérifications adaptés au changement.
- Mentionnez les commandes exécutées et leurs résultats dans la Pull Request.
- Vérifiez en priorité les shortcodes, le rendu public, le cache, les appels API Piwigo et les réglages d'administration.
- Pour une optimisation, fournissez une mesure avant/après ou une méthode de mesure reproductible lorsque l'impact est quantifiable.
- Si un test n'est pas possible, expliquez pourquoi et indiquez le risque résiduel.

## Revue de code

Avant de demander une revue, vérifiez que :

- la Pull Request a une responsabilité unique ;
- chaque commit a une responsabilité unique ;
- le changement respecte les principes de conception du projet ;
- la complexité ajoutée est justifiée ;
- les performances, la qualité, la compatibilité et la sécurité sont préservées ;
- la documentation et les tests sont suffisants ;
- les contenus de contribution sont en français et les noms de code restent en anglais.

Les échanges de revue doivent rester précis, respectueux et orientés vers la qualité du plugin.
