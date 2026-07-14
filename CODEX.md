# Règles de développement pour Codex

Ce dépôt contient le plugin WordPress **WP Piwigo Display**. Toute contribution automatisée ou assistée par Codex doit respecter les règles suivantes.

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

Ne jamais augmenter la complexité sans bénéfice démontrable. Toute optimisation quantifiable doit être démontrée par une mesure, et toute nouvelle dépendance doit être justifiée.

## Conventions Git

- Tous les commits sont rédigés en français.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- Une Pull Request = une seule responsabilité.
- Un commit = une seule responsabilité.
- Les messages de commit doivent décrire l'intention métier ou technique du changement, pas seulement les fichiers modifiés.
- Une Pull Request doit cibler la branche `1.9.x`.
- Ne pas mélanger corrections, refactorisations, documentation et changements fonctionnels dans une même Pull Request, sauf si cela est indispensable et expliqué.

## Pull Requests

Chaque Pull Request doit répondre aux questions suivantes :

- Quel problème est résolu ?
- Quel bénéfice apporte cette modification ?
- Quel impact technique apporte-t-elle ?
- Quel est son impact mesurable sur les performances ou la qualité ?
- Comment ce bénéfice a-t-il été vérifié ?

Une Pull Request qui ne présente aucun impact mesurable doit le justifier.

Une Pull Request ne traite qu'un seul sujet.

## Développement

- Toute optimisation doit être accompagnée de mesures avant/après lorsque cela est possible.
- Les optimisations doivent être démontrées, jamais supposées.
- Avant toute Pull Request, rechercher si du code peut être supprimé ou simplifié plutôt qu'ajouté.
- La solution la plus simple est privilégiée lorsqu'elle répond au besoin.
- Les ressources CPU, mémoire, réseau et stockage sont utilisées avec discernement.
- La rétrocompatibilité est préservée autant que possible.

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais, conformément aux conventions WordPress.
- Respecter les conventions WordPress applicables au PHP, au JavaScript, au CSS et à l'internationalisation.
- Préférer un code explicite, court et lisible à une abstraction prématurée.
- Ne pas introduire de nouvelle dépendance sans justification claire, alternative étudiée et valeur démontrable.
- Préserver la compatibilité avec les versions de WordPress et PHP supportées par le projet.
- Toute logique de sécurité doit être explicite : validation des entrées, échappement des sorties, vérification des capacités et protection des actions sensibles.

## Règles de documentation

- Toute la documentation du projet est rédigée en français.
- Documenter les comportements publics, les shortcodes, les réglages, les décisions d'architecture et les limites connues.
- Mettre à jour la documentation dans la même Pull Request que le changement concerné.
- Éviter la documentation décorative : chaque paragraphe doit aider à installer, utiliser, maintenir ou vérifier le plugin.
- Les exemples doivent être exacts, reproductibles et cohérents avec le code actuel.

## Règles de tests

- Chaque changement doit être accompagné des vérifications adaptées à son risque : tests automatisés, analyse statique, lint, test manuel WordPress ou contrôle ciblé.
- Documenter dans la Pull Request les commandes exécutées et les résultats obtenus.
- Tester en priorité les chemins critiques : affichage des galeries, cache, appels API Piwigo, shortcodes, réglages d'administration et compatibilité WordPress.
- Lorsqu'un test ne peut pas être exécuté, expliquer clairement la contrainte d'environnement et le risque résiduel.
- Toute optimisation doit être accompagnée de mesures avant/après lorsque cela est possible, ou d'une méthode de mesure reproductible lorsque la mesure directe n'est pas réalisable.

## Règles de revue de code

- La revue vérifie d'abord la responsabilité unique de la Pull Request et du commit.
- La revue privilégie la simplicité, la robustesse, la compatibilité, la sécurité et la maintenabilité.
- Refuser les abstractions, dépendances ou optimisations qui ne démontrent pas de bénéfice concret.
- Vérifier que les noms de code restent en anglais et que la documentation, les commits et la Pull Request restent en français.
- Vérifier les impacts sur la compatibilité WordPress, la compatibilité PHP, la sécurité, la performance et la qualité.
- Les commentaires de revue doivent être précis, actionnables et liés à un risque réel pour le projet.
