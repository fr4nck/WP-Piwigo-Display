# Sécurité

## Versions supportées

La dernière version publiée du plugin est la seule version maintenue.

## Signaler une faille

Si vous pensez avoir trouvé un problème de sécurité, merci de ne pas ouvrir publiquement une issue contenant les détails exploitables.

Contactez le mainteneur du projet via GitHub afin de permettre une correction avant publication.

## Principes retenus

Le plugin applique les principes suivants :

- validation stricte des paramètres de shortcode ;
- vérification des capacités administrateur ;
- protection des actions administrateur par nonce ;
- échappement des sorties HTML ;
- validation des URL Piwigo ;
- limitation des appels HTTP vers l’API Piwigo ;
- aucune écriture de fichier côté serveur.
