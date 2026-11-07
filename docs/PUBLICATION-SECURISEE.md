# Préparer et publier une version

Les branches produisent uniquement des artefacts de recette. Le travail de construction utilise un jeton en lecture seule. Le ZIP, son SHA-256 et la provenance (commit et exécution CI) sont conservés ensemble pendant 30 jours.

Une poussée d'un **nouveau tag annoté** peut préparer un brouillon, après les vérifications de construction. Le tag doit correspondre exactement à la version et au commit vérifié. Le travail dédié utilise l'environnement `release` et vérifie le SHA-256 avant tout envoi. Il refuse toute release existante. Aucun de ces travaux ne publie de release : la publication du brouillon reste une étape explicite du mainteneur après recette WordPress/Piwigo.

Le workflow `build-plugin.yml` reste désactivé dans les réglages GitHub tant que ses anciennes copies destructrices peuvent être utilisées sur d'autres branches. Sa réactivation exige que toutes les branches autorisées à construire utilisent cette nouvelle procédure. Ne jamais relancer une ancienne exécution de publication.

Le 7 novembre 2026, le ruleset `Godmode` protège `refs/tags/v*` contre les mises à jour, suppressions et envois forcés, sans contournement. La création de nouveaux tags reste possible. L'immuabilité des prochaines releases est activée dans GitHub. La purge programmée des exécutions est suspendue pour conserver les journaux de l'incident RC3 ; la rétention normale de GitHub peut néanmoins expirer des artefacts.

Avant de créer un tag :

1. Exécuter la CI sur le commit candidat et conserver les résultats.
2. Tester le ZIP exact sur un environnement de recette WordPress/Piwigo, y compris les albums privés, les deux éditeurs, le diaporama et la lightbox.
3. Conserver le ZIP, son SHA-256 et le commit testé. Le fichier `PROVENANCE.txt` identifie aussi l'exécution CI.
4. Créer un nouveau tag annoté seulement après validation. Ne jamais déplacer `v3.0.0-rc.3` ni réutiliser un numéro publié.
5. Vérifier les pièces jointes et compléter les notes du brouillon avant de publier manuellement.

Les actions et les dépendances de contrôle ne sont pas encore toutes figées par empreinte. Les ZIP ne sont donc pas garantis identiques à l'octet entre deux constructions ; l'empreinte à conserver est celle du paquet effectivement testé.
