# Contribuer à WP Piwigo Display

Merci de contribuer à **WP Piwigo Display**. Ce document résume les règles à respecter avant d’ouvrir une Pull Request.

## Philosophie du projet

Le plugin doit rester léger, rapide et facilement maintenable. Toute contribution doit servir un besoin clair et éviter la complexité inutile.

Priorités du projet :

1. Simplicité
2. Performance
3. Compatibilité
4. Sécurité
5. Maintenabilité

Ne jamais augmenter la complexité sans bénéfice mesurable. Toute optimisation doit être mesurable. Toute nouvelle dépendance doit être justifiée.

## Avant de commencer

- Vérifiez que votre changement correspond à une seule responsabilité.
- Préparez une Pull Request séparée pour chaque sujet distinct.
- Consultez la charte complète dans `docs/CHARTE_DEVELOPPEMENT.md`.
- Préservez la compatibilité avec les versions WordPress et PHP supportées par le projet.

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

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais, conformément aux conventions WordPress.
- Respectez les conventions WordPress pour PHP, JavaScript, CSS, HTML et internationalisation.
- Préférez une solution explicite et simple à une abstraction prématurée.
- Justifiez toute nouvelle dépendance dans la Pull Request.
- Validez et nettoyez les entrées utilisateur, échappez les sorties et vérifiez les permissions pour les actions sensibles.

## Documentation

- Toute la documentation est rédigée en français.
- Mettez à jour la documentation dans la même Pull Request que le changement concerné.
- Les exemples doivent être exacts, reproductibles et cohérents avec le code.
- Documentez les limites connues lorsqu’elles aident à utiliser ou maintenir le plugin.

## Tests et vérifications

- Exécutez les tests ou vérifications adaptés au changement.
- Mentionnez les commandes exécutées et leurs résultats dans la Pull Request.
- Vérifiez en priorité les shortcodes, le rendu public, le cache, les appels API Piwigo et les réglages d’administration.
- Pour une optimisation, fournissez une mesure avant/après ou une méthode de mesure reproductible.
- Si un test n’est pas possible, expliquez pourquoi et indiquez le risque résiduel.

## Revue de code

Avant de demander une revue, vérifiez que :

- la Pull Request a une responsabilité unique ;
- chaque commit a une responsabilité unique ;
- le changement respecte les priorités du projet ;
- la complexité ajoutée est justifiée ;
- les performances, la compatibilité et la sécurité sont préservées ;
- la documentation et les tests sont suffisants ;
- les contenus de contribution sont en français et les noms de code restent en anglais.

Les échanges de revue doivent rester précis, respectueux et orientés vers la qualité du plugin.
