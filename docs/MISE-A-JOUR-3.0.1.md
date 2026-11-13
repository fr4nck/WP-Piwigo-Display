# Piwigo Display — Mise à jour de 3.0.0 vers 3.0.1

**Statut :** procédure de la candidate 3.0.1. Ne pas déployer tant qu'une release GitHub 3.0.1 n'est pas explicitement publiée et qualifiée. Aucun ZIP de PR n'est une publication publique.

## Préparation

1. Sauvegarder **la base WordPress, les fichiers, les paramètres et le ZIP 3.0.0**, puis vérifier que la sauvegarde est restaurable. Conserver les identifiants hors des rapports.
2. Vérifier que Piwigo Display 3.0.0 est installé dans le répertoire `wp-piwigo-display/`. Sur un multisite, effectuer l'opération avec un administrateur réseau.
3. Prévoir une maintenance adaptée au site. Vérifier les pages utilisant `[piwigo]` et le bloc `wp-piwigo-display/gallery` avant et après l'opération.

## Installation de la 3.0.1 GitHub

1. Télécharger le **ZIP officiel** `piwigo-display-3.0.1.zip` de la future release GitHub ; comparer son SHA-256 au fichier `SHA256SUMS` publié.
2. Dans WordPress : **Extensions → Ajouter → Téléverser une extension**. Sélectionner le ZIP.
3. Si WordPress propose de remplacer l'installation existante, choisir le **remplacement en place**. Ne pas cliquer sur « Supprimer » ni désinstaller la 3.0.0.
4. Vérifier que le dossier reste `wp-piwigo-display/`, que la version affichée est **3.0.1**, et que l'extension conserve son état d'activation.
5. Comparer les options `wp_piwigo_display_options` et `wp_piwigo_display_service_account` sans divulguer leur contenu. Tester une ancienne page, un ancien diaporama, un bloc Gutenberg et les albums publics/privés autorisés.

**Aucun changement des shortcodes, blocs, identifiants d'albums ou contenus enregistrés n'est nécessaire.** L'en-tête `Update URI` du paquet GitHub empêche une assimilation involontaire aux mises à jour WordPress.org ; il ne constitue pas un mécanisme automatique de téléchargement depuis GitHub.

## En cas de problème

Ne pas désinstaller une copie encore nécessaire pour conserver les données. Restaurer le site à partir de la sauvegarde vérifiée (fichiers **et** base WordPress). Pour revenir de la 3.0.1 GitHub à la 3.0.0, restaurer les fichiers 3.0.0 **dans le même dossier**, sans exécuter l'ancien désinstalleur. Contrôler ensuite la configuration, les galeries et les droits d'accès.

## Bascule éventuelle vers WordPress.org

Cette opération est **distincte** et n'est possible qu'après approbation du slug et publication d'un paquet officiel. D'abord mettre à jour la copie GitHub **en place** vers 3.0.1. Ensuite installer la nouvelle distribution **inactive**, désactiver GitHub sans le supprimer, activer l'autre copie et vérifier les diaporamas. En cas d'anomalie, désactiver la nouvelle copie et réactiver la 3.0.1 GitHub. Ne supprimer l'ancienne copie protégée qu'après la recette.

Voir [les conditions et limites complètes de migration](MIGRATION-3.0.1.md). Les contrôles automatisés se déroulent sur WordPress/MySQL jetables ; ils ne remplacent pas la vérification du site concerné.