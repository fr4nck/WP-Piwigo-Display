# Charte de développement

Cette charte définit les règles communes pour développer, relire et maintenir **WP Piwigo Display**.

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

Une solution simple et robuste est préférée à une architecture plus ambitieuse lorsque cette solution répond au besoin. Ne jamais augmenter la complexité sans bénéfice démontrable. Toute optimisation quantifiable doit être mesurée. Toute nouvelle dépendance doit être justifiée.

## Conventions Git

### Branches et Pull Requests

- Une Pull Request = une seule responsabilité.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- La description répond aux questions de revue du projet, décrit les tests réalisés et mentionne les limites éventuelles.
- Les Pull Requests liées à la série 1.9 ciblent la branche `1.9.x`.
- Les changements sans lien direct doivent être proposés dans des Pull Requests séparées.

Chaque Pull Request doit répondre aux questions suivantes :

- Quel problème est résolu ?
- Quel bénéfice apporte cette modification ?
- Quel impact technique apporte-t-elle ?
- Quel est son impact mesurable sur les performances ou la qualité ?
- Comment ce bénéfice a-t-il été vérifié ?

Une Pull Request ne traite qu'un seul sujet.

### Commits

- Un commit = une seule responsabilité.
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
- Les abstractions sont introduites uniquement lorsqu'elles réduisent une duplication réelle ou clarifient un comportement complexe.
- Les dépendances externes sont évitées par défaut. Toute nouvelle dépendance doit être justifiée par un besoin concret, une valeur démontrable et une maintenance acceptable.
- La compatibilité avec les versions WordPress et PHP supportées est obligatoire.
- Les entrées utilisateur sont validées et nettoyées ; les sorties sont échappées selon leur contexte ; les actions sensibles vérifient les permissions et les jetons de sécurité.

## Règles de documentation

- Toute la documentation est rédigée en français.
- La documentation doit être mise à jour dans la même Pull Request que le changement qu'elle décrit.
- Les documents doivent rester utiles, concis et vérifiables.
- Les exemples de shortcodes, de réglages ou de configuration doivent correspondre au comportement actuel du plugin.
- Les décisions d'architecture importantes sont documentées lorsqu'elles influencent la maintenance future.

## Règles de tests

- Chaque Pull Request décrit les tests et vérifications réalisés.
- Les tests doivent être proportionnés au risque du changement.
- Les chemins critiques à vérifier en priorité sont : shortcodes, rendu public, cache, appels API Piwigo, réglages d'administration, sécurité et compatibilité WordPress.
- Toute optimisation doit inclure une mesure avant/après ou une méthode de mesure reproductible lorsque l'impact est quantifiable.
- Si un test ne peut pas être exécuté, la Pull Request précise la raison, l'impact et le risque résiduel.

## Règles de revue de code

La revue de code doit répondre aux questions suivantes :

1. La Pull Request a-t-elle une responsabilité unique ?
2. Le commit a-t-il une responsabilité unique ?
3. Le changement respecte-t-il les principes de conception du projet ?
4. La complexité ajoutée est-elle justifiée par un bénéfice démontrable ?
5. Les performances, la qualité, la compatibilité et la sécurité sont-elles préservées ?
6. La documentation et les tests sont-ils suffisants ?
7. Les noms de code restent-ils en anglais et les contenus de contribution en français ?

Les commentaires de revue doivent être précis, actionnables et liés à un risque réel. La revue ne doit pas imposer une préférence personnelle si le code proposé respecte les règles du projet.
