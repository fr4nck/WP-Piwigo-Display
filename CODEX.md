# Règles de développement pour Codex

Ce dépôt contient le plugin WordPress **WP Piwigo Display**. Toute contribution automatisée ou assistée par Codex doit respecter les règles suivantes.

## Philosophie du projet

WP Piwigo Display doit rester un plugin léger, rapide et facilement maintenable. Chaque changement doit préserver l’expérience WordPress tout en facilitant l’intégration avec Piwigo.

Les priorités du projet sont, dans cet ordre :

1. Simplicité
2. Performance
3. Compatibilité
4. Sécurité
5. Maintenabilité

Ne jamais augmenter la complexité sans bénéfice mesurable. Toute optimisation doit être mesurable et toute nouvelle dépendance doit être justifiée.

## Conventions Git

- Tous les commits sont rédigés en français.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- Une Pull Request = une seule responsabilité.
- Un commit = une seule responsabilité.
- Les messages de commit doivent décrire l’intention métier ou technique du changement, pas seulement les fichiers modifiés.
- Une Pull Request doit cibler la branche de maintenance ou de développement appropriée ; pour la série 1.9, cibler `1.9.x`.
- Ne pas mélanger corrections, refactorisations, documentation et changements fonctionnels dans une même Pull Request, sauf si cela est indispensable et expliqué.

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais, conformément aux conventions WordPress.
- Respecter les conventions WordPress applicables au PHP, au JavaScript, au CSS et à l’internationalisation.
- Préférer un code explicite, court et lisible à une abstraction prématurée.
- Ne pas introduire de nouvelle dépendance sans justification claire, alternative étudiée et bénéfice mesurable.
- Préserver la compatibilité avec les versions de WordPress et PHP supportées par le projet.
- Toute logique de sécurité doit être explicite : validation des entrées, échappement des sorties, vérification des capacités et protection des actions sensibles.

## Règles de documentation

- Toute la documentation du projet est rédigée en français.
- Documenter les comportements publics, les shortcodes, les réglages, les décisions d’architecture et les limites connues.
- Mettre à jour la documentation dans la même Pull Request que le changement concerné.
- Éviter la documentation décorative : chaque paragraphe doit aider à installer, utiliser, maintenir ou vérifier le plugin.
- Les exemples doivent être exacts, reproductibles et cohérents avec le code actuel.

## Règles de tests

- Chaque changement doit être accompagné des vérifications adaptées à son risque : tests automatisés, analyse statique, lint, test manuel WordPress ou contrôle ciblé.
- Documenter dans la Pull Request les commandes exécutées et les résultats obtenus.
- Tester en priorité les chemins critiques : affichage des galeries, cache, appels API Piwigo, shortcodes, réglages d’administration et compatibilité WordPress.
- Lorsqu’un test ne peut pas être exécuté, expliquer clairement la contrainte d’environnement et le risque résiduel.
- Toute optimisation doit être accompagnée d’une mesure avant/après ou d’une méthode de mesure reproductible.

## Règles de revue de code

- La revue vérifie d’abord la responsabilité unique de la Pull Request et du commit.
- La revue privilégie la simplicité, la performance, la compatibilité, la sécurité puis la maintenabilité.
- Refuser les abstractions, dépendances ou optimisations qui ne démontrent pas de bénéfice concret.
- Vérifier que les noms de code restent en anglais et que la documentation, les commits et la Pull Request restent en français.
- Vérifier les impacts sur la compatibilité WordPress, la compatibilité PHP, la sécurité et la performance.
- Les commentaires de revue doivent être précis, actionnables et liés à un risque réel pour le projet.
