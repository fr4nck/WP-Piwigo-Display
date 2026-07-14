# Règles de développement pour Codex

Ce dépôt contient le plugin WordPress **WP Piwigo Display**. Toute contribution automatisée ou assistée par Codex doit respecter les règles suivantes.

## Principes de développement

Le projet définit une manière de développer fondée sur des changements simples, expliqués et relisibles dans le temps. Il ne cherche pas à se comparer à d’autres projets.

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

Chaque modification doit pouvoir être expliquée simplement. Le projet privilégie un développement progressif, documenté et relisible dans le temps.

## Conventions Git

- Tous les commits sont rédigés en français.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- Une Pull Request traite un seul sujet.
- Un commit traite un seul sujet.
- Les messages de commit décrivent l’intention métier ou technique du changement, pas seulement les fichiers modifiés.
- Une Pull Request doit cibler la branche `1.9.x` pour la série 1.9.
- Ne pas mélanger corrections, refactorisations, documentation et changements fonctionnels dans une même Pull Request, sauf si cela est nécessaire et expliqué.

## Pull Requests

Chaque Pull Request doit répondre aux questions suivantes lorsque ces points sont applicables :

- Quel besoin traite-t-elle ?
- Quelle solution est retenue ?
- Comment la compatibilité est-elle préservée ?
- Quels tests ou vérifications ont été réalisés ?
- Des mesures avant/après sont-elles pertinentes ? Si oui, quels sont les résultats ?

Une Pull Request ne doit traiter qu’un seul sujet. Lorsqu’une amélioration est mesurable, les mesures avant/après sont indiquées. Lorsqu’une mesure n’est pas pertinente, la Pull Request l’indique simplement.

## Développement

- Comprendre le fonctionnement existant avant de modifier le code ou la documentation.
- Rechercher la solution la plus simple répondant au besoin.
- Préserver la compatibilité avec les versions de WordPress, PHP et Piwigo supportées par le projet.
- Éviter les dépendances, traitements et duplications de code inutiles.
- Limiter les modifications au besoin traité par la Pull Request.
- Expliquer simplement les choix de conception dans la Pull Request ou la documentation lorsque cela facilite la maintenance.
- Indiquer des mesures avant/après lorsqu’une amélioration est mesurable.

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais, conformément aux conventions WordPress.
- Respecter les conventions WordPress applicables au PHP, au JavaScript, au CSS et à l’internationalisation.
- Préférer un code explicite, court et lisible à une abstraction prématurée.
- Introduire une abstraction uniquement lorsqu’elle réduit une duplication réelle ou clarifie un comportement.
- Ne pas introduire de nouvelle dépendance sans justification claire.
- Préserver la compatibilité avec les versions de WordPress et PHP supportées par le projet.
- Toute logique de sécurité doit être explicite : validation des entrées, échappement des sorties, vérification des capacités et protection des actions sensibles.
- Les commentaires sont ajoutés lorsqu’ils facilitent la compréhension du fonctionnement, et non pour décrire l’évidence.

## Règles de documentation

- Toute la documentation du projet est rédigée en français.
- Documenter les comportements publics, les shortcodes, les réglages, les décisions d’architecture et les limites connues lorsque cela aide à utiliser ou maintenir le plugin.
- Mettre à jour la documentation dans la même Pull Request que le changement concerné.
- Chaque paragraphe doit aider à installer, utiliser, maintenir ou vérifier le plugin.
- Les exemples doivent être exacts, reproductibles et cohérents avec le code actuel.

## Règles de tests et vérifications

- Chaque changement doit être accompagné des vérifications adaptées à son risque : tests automatisés, analyse statique, lint, test manuel WordPress ou contrôle ciblé.
- Documenter dans la Pull Request les commandes exécutées et les résultats obtenus.
- Tester en priorité les chemins critiques : affichage des galeries, cache, appels API Piwigo, shortcodes, réglages d’administration et compatibilité WordPress.
- Lorsqu’un test ne peut pas être exécuté, expliquer la contrainte d’environnement et le risque résiduel.
- Lorsqu’une amélioration est mesurable, fournir des mesures avant/après ou une méthode de mesure reproductible.

## Règles de revue de code

- La revue vérifie d’abord que la Pull Request et le commit traitent un seul sujet.
- La revue vérifie la simplicité, la robustesse, la lisibilité, la maintenabilité, la compatibilité et la pertinence des mesures.
- Vérifier que les abstractions, dépendances et traitements ajoutés répondent au besoin décrit.
- Vérifier que les noms de code restent en anglais et que la documentation, les commits et la Pull Request restent en français.
- Vérifier les impacts sur la compatibilité WordPress, la compatibilité PHP, la sécurité et les comportements existants.
- Les commentaires de revue doivent être précis, actionnables et liés à un risque réel pour le projet.
