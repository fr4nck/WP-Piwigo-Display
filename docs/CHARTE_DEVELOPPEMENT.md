# Charte de développement

Cette charte définit les règles communes pour développer, relire et maintenir **WP Piwigo Display**.

## Principes de développement

Le projet définit une manière de développer sans établir de comparaison avec d'autres projets.

Le développement privilégie :

- la simplicité ;
- la robustesse ;
- la lisibilité ;
- la maintenabilité ;
- la compatibilité ;
- la mesure des modifications lorsqu'elle est pertinente.

Avant toute modification :

- comprendre le fonctionnement existant ;
- rechercher la solution la plus simple répondant au besoin ;
- éviter les dépendances inutiles ;
- éviter les traitements inutiles ;
- éviter les duplications de code ;
- préserver la compatibilité avec les versions précédentes.

Chaque modification doit pouvoir être expliquée simplement. Le projet privilégie un développement progressif, documenté et relisible dans le temps.

## Conventions Git

### Branches et Pull Requests

- Une Pull Request traite un seul sujet.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- La description explique le besoin traité, la solution retenue, les vérifications réalisées et les limites éventuelles.
- Les Pull Requests liées à la série 1.9 ciblent la branche `1.9.x`.
- Les changements sans lien direct doivent être proposés dans des Pull Requests séparées.
- Lorsqu'une amélioration est mesurable, les mesures avant/après sont indiquées.

### Commits

- Un commit traite un seul sujet.
- Tous les commits sont rédigés en français.
- Le message de commit doit être court, explicite et orienté intention.
- Éviter les messages vagues comme `mise à jour`, `fix` ou `changements divers`.

Exemples acceptables :

- `Ajoute la charte de développement`
- `Corrige l'échappement du titre des albums`
- `Documente les paramètres du shortcode galerie`

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais conformément aux conventions WordPress.
- Le code suit les conventions WordPress pour PHP, JavaScript, CSS, HTML et internationalisation.
- Le code doit rester lisible, direct et limité au besoin réel.
- Les abstractions sont introduites uniquement lorsqu'elles réduisent une duplication réelle ou clarifient un comportement.
- Les dépendances externes sont évitées lorsqu'elles ne répondent pas à un besoin identifié. Toute nouvelle dépendance doit être justifiée.
- La compatibilité avec les versions WordPress, PHP et Piwigo supportées est préservée.
- Les entrées utilisateur sont validées et nettoyées ; les sorties sont échappées selon leur contexte ; les actions sensibles vérifient les permissions et les jetons de sécurité.
- Les commentaires sont ajoutés lorsqu'ils facilitent la compréhension du fonctionnement, et non pour décrire l'évidence.

## Règles de documentation

- Toute la documentation est rédigée en français.
- La documentation doit être mise à jour dans la même Pull Request que le changement qu'elle décrit.
- Les documents doivent rester utiles, concis et vérifiables.
- Les exemples de shortcodes, de réglages ou de configuration doivent correspondre au comportement actuel du plugin.
- Les décisions d'architecture importantes sont documentées lorsqu'elles influencent la maintenance future.
- Les identifiants techniques restent en anglais conformément aux conventions WordPress.

## Règles de tests et vérifications

- Chaque Pull Request décrit les tests et vérifications réalisés.
- Les tests doivent être proportionnés au risque du changement.
- Les chemins critiques à vérifier en priorité sont : shortcodes, rendu public, cache, appels API Piwigo, réglages d'administration, sécurité et compatibilité WordPress.
- Lorsqu'une amélioration est mesurable, la Pull Request inclut une mesure avant/après ou une méthode de mesure reproductible.
- Si un test ne peut pas être exécuté, la Pull Request précise la raison, l'impact et le risque résiduel.

## Règles de revue de code

La revue de code doit répondre aux questions suivantes :

1. La Pull Request traite-t-elle un seul sujet ?
2. Le commit traite-t-il un seul sujet ?
3. Le changement respecte-t-il les principes de développement du projet ?
4. La solution retenue répond-elle au besoin sans dépendance, traitement ou duplication inutile ?
5. La compatibilité, la sécurité et les comportements existants sont-ils préservés ?
6. La documentation et les vérifications sont-elles adaptées au changement ?
7. Les noms de code restent-ils en anglais et les contenus de contribution en français ?
8. Les mesures avant/après sont-elles indiquées lorsqu'elles sont pertinentes ?

Les commentaires de revue doivent être précis, actionnables et liés à un risque réel. La revue ne doit pas imposer une préférence personnelle si le changement proposé respecte les règles du projet.
