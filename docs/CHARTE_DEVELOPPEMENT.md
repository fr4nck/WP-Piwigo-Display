# Charte de développement

Cette charte définit les règles communes pour développer, relire et maintenir **WP Piwigo Display**.

## Philosophie du projet

WP Piwigo Display a pour objectif d’afficher simplement du contenu Piwigo dans WordPress sans alourdir le site. Le plugin doit rester léger, rapide et facilement maintenable.

Les décisions techniques suivent toujours cet ordre de priorité :

1. Simplicité
2. Performance
3. Compatibilité
4. Sécurité
5. Maintenabilité

Une solution simple et robuste est préférée à une architecture plus ambitieuse si le bénéfice n’est pas mesurable. Ne jamais augmenter la complexité sans bénéfice mesurable. Toute optimisation doit être mesurable. Toute nouvelle dépendance doit être justifiée.

## Conventions Git

### Branches et Pull Requests

- Une Pull Request = une seule responsabilité.
- Tous les titres et descriptions de Pull Request sont rédigés en français.
- La description explique le problème traité, la solution retenue, les tests réalisés et les limites éventuelles.
- Les Pull Requests liées à la série 1.9 ciblent la branche `1.9.x`.
- Les changements sans lien direct doivent être proposés dans des Pull Requests séparées.

### Commits

- Un commit = une seule responsabilité.
- Tous les commits sont rédigés en français.
- Le message de commit doit être court, explicite et orienté intention.
- Éviter les messages vagues comme `mise à jour`, `fix` ou `changements divers`.

Exemples acceptables :

- `Ajoute la charte de développement`
- `Corrige l’échappement du titre des albums`
- `Documente les paramètres du shortcode galerie`

## Conventions de code

- Les noms de classes, fonctions, méthodes, variables et constantes restent en anglais conformément aux conventions WordPress.
- Le code suit les conventions WordPress pour PHP, JavaScript, CSS, HTML et internationalisation.
- Le code doit rester lisible, direct et limité au besoin réel.
- Les abstractions sont introduites uniquement lorsqu’elles réduisent une duplication réelle ou clarifient un comportement complexe.
- Les dépendances externes sont évitées par défaut. Toute nouvelle dépendance doit être justifiée par un besoin concret, un bénéfice mesurable et une maintenance acceptable.
- La compatibilité avec les versions WordPress et PHP supportées est obligatoire.
- Les entrées utilisateur sont validées et nettoyées ; les sorties sont échappées selon leur contexte ; les actions sensibles vérifient les permissions et les jetons de sécurité.

## Règles de documentation

- Toute la documentation est rédigée en français.
- La documentation doit être mise à jour dans la même Pull Request que le changement qu’elle décrit.
- Les documents doivent rester utiles, concis et vérifiables.
- Les exemples de shortcodes, de réglages ou de configuration doivent correspondre au comportement actuel du plugin.
- Les décisions d’architecture importantes sont documentées lorsqu’elles influencent la maintenance future.

## Règles de tests

- Chaque Pull Request décrit les tests et vérifications réalisés.
- Les tests doivent être proportionnés au risque du changement.
- Les chemins critiques à vérifier en priorité sont : shortcodes, rendu public, cache, appels API Piwigo, réglages d’administration, sécurité et compatibilité WordPress.
- Toute optimisation doit inclure une mesure avant/après ou une méthode de mesure reproductible.
- Si un test ne peut pas être exécuté, la Pull Request précise la raison, l’impact et le risque résiduel.

## Règles de revue de code

La revue de code doit répondre aux questions suivantes :

1. La Pull Request a-t-elle une responsabilité unique ?
2. Le commit a-t-il une responsabilité unique ?
3. Le changement respecte-t-il l’ordre de priorité du projet ?
4. La complexité ajoutée est-elle justifiée par un bénéfice mesurable ?
5. Les performances, la compatibilité et la sécurité sont-elles préservées ?
6. La documentation et les tests sont-ils suffisants ?
7. Les noms de code restent-ils en anglais et les contenus de contribution en français ?

Les commentaires de revue doivent être précis, actionnables et liés à un risque réel. La revue ne doit pas imposer une préférence personnelle si le code proposé respecte les règles du projet.
