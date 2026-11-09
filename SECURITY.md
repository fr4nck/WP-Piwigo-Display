# Sécurité

## Versions et état de maintenance

Les publications GitHub 1.8.0, 1.9.0 et 1.10.0 sont historiques. Aucune d'elles ne bénéficie ici d'une garantie de maintenance active.

Les versions 3.0.0-rc.1 et 3.0.0-rc.3 sont des **candidates de test**, pas des versions stables. Elles présentent des anomalies connues et ne constituent pas une base de déploiement recommandée. La candidate 3.0.0-rc.4 **n'a jamais été publiée** ; ses changements sont intégrés à la version stable **3.0.0**, version maintenue de la V3.

Un audit indépendant et une recette WordPress/Piwigo réelle restent obligatoires avant toute nouvelle publication.

## Signaler une faille

Ne publiez pas de secrets, de données privées ou de détails d'exploitation dans une issue publique. Contactez directement le mainteneur via GitHub pour un signalement confidentiel.

## Principes

- L'authentification du compte de service n'est autorisée que vers l'URL Piwigo configurée.
- Le contenu d'un album privé affiché sur une page WordPress publique devient public sur cette page.
- Les messages provenant du réseau ou de Piwigo ne sont jamais affichés directement aux visiteurs.
- Les données propres au plugin sont effacées lors de sa désinstallation.
- Les actions sensibles sont protégées par des contrôles d'autorisation et de nonce.
- Aucun tag ni aucune release publiée ne doit être réécrit.
