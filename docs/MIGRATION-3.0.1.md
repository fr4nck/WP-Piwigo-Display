# MIG-301 — Transition GitHub vers WordPress.org

État au 12 novembre 2026 : **correctifs en PR, non fusionnés ni publiés**. Cette procédure est obligatoire pour toute migration existante, mais ne vaut **pas** recette WordPress/Piwigo réelle ni autorisation de déploiement. Le tag et l'archive v3.0.0 restent immuables. Aucun slug WordPress.org n'est considéré approuvé.

## Contrat de compatibilité

Ne jamais renommer les clés WordPress `wp_piwigo_display_options` et `wp_piwigo_display_service_account`, les blocs `wp-piwigo-display/gallery` ni les shortcodes `[piwigo]`. Les anciennes pages, galeries et diaporamas doivent continuer à fonctionner sans modification.

**Contrat d'identité des paquets :** même si le nom commercial ou le répertoire WordPress.org est modifié, la nouvelle distribution doit conserver une signature stable d'origine (`Plugin URI: https://github.com/fr4nck/WP-Piwigo-Display`) ou le fichier principal historique `wp-piwigo-display.php`. Le désinstalleur reconnaît ces marqueurs ; sans aucun d'eux et avec un nom et domaine de traduction différents, il ne peut pas identifier une copie sœur et la purge ne peut pas être considérée sûre. L'identité sera vérifiée dans le ZIP candidat avant tout déploiement.

La variante **GitHub** 3.0.1 possède `Update URI: https://github.com/fr4nck/WP-Piwigo-Display`, pour isoler les mises à jour natives WordPress.org. Cela n'installe pas de système automatique de mise à jour depuis GitHub. Le futur paquet WordPress.org sera préparé **séparément**, sans cet en-tête et avec son domaine de traduction égal au slug approuvé. Les exceptions `TextDomainMismatch` de la CI historique ne constituent pas une conformité WordPress.org.

## Règle P0 : mise à jour en place obligatoire AVANT toute bascule

**Interdiction de migrer directement d'une 3.0.0 installée vers un deuxième répertoire, puis de supprimer l'ancienne copie.** Le `uninstall.php` historique 3.0.0 efface les options partagées, même si une nouvelle copie est active. La nouvelle version ne peut pas corriger une 3.0.0 qui n'a pas été mise à jour.

1. Faire une sauvegarde complète vérifiée : base SQL, fichiers WordPress, `wp-config.php`, ZIP de la 3.0.0, hash des options et preuves des diaporamas. Conserver les secrets hors des journaux.
2. Mettre à jour **dans le même répertoire `wp-piwigo-display/`** la 3.0.0 vers le **ZIP GitHub 3.0.1 qualifié**, sans désinstaller. Vérifier le numéro de version, les fichiers réels, en particulier le nouveau `uninstall.php`, et toutes les options.
3. Installer le futur paquet WordPress.org (après attribution du slug) dans son dossier, **sans l'activer**.
4. Désactiver l'ancienne copie GitHub **sans la supprimer**. Activer la nouvelle copie. Vérifier shortcodes, blocs, diaporamas, albums, droits publics/privés, identifiants du compte de service et canaux de mise à jour.
5. En cas de problème, désactiver la nouvelle copie et réactiver la copie GitHub 3.0.1 ; comparer les options. Si elles ont changé de manière incompatible, restaurer la sauvegarde.
6. Uniquement après recette et accord explicite, retirer le dossier GitHub **3.0.1** en suivant la procédure qualifiée. Son désinstalleur conserve les données si une copie sœur est installée. Ne jamais supprimer la 3.0.0 non corrigée via l'interface ou WP-CLI.

## Protection et limites

- Les deux ordres de chargement ne redéfinissent plus le bootstrap PHP, mais **aucune double activation n'est autorisée**.
- La garde refuse l'activation concurrente dans son contexte (site ou réseau), y compris l'activation inverse lorsque la nouvelle copie est déjà chargée.
- Il n'existe **aucune désactivation automatique depuis admin_init**. Un administrateur est averti et corrige manuellement un état incohérent. Un abonné ne peut pas provoquer une désactivation réseau en visitant son profil.
- Le hook `pre_uninstall_plugin` peut interrompre une tentative de suppression de la 3.0.0 **si** la nouvelle copie est chargée dans cette requête ; ce n'est ni une protection rétroactive universelle, ni une garantie quand le plugin protecteur est inactif. L'annulation repose sur un arrêt explicite, pas sur un simple retour d'action WordPress.
- La suppression groupée de deux copies protégées utilise leurs fichiers locaux pour décider quel est le dernier paquet encore installé ; la copie finale nettoie normalement les secrets. Si la liste d'extensions n'est pas fiable, la purge est évitée.
- La conservation volontaire `WPD_KEEP_DATA_ON_UNINSTALL` accepte `true`, `1` ou `'true'` ; elle **laisse les identifiants en base**, donc ne pas l'utiliser sans raison documentée.
- Ne pas tenter d'activation silencieuse, de réparation automatique via base SQL, de déploiement multisite incomplet ou de remplacement de fichiers à chaud. Une copie active uniquement sur un autre site peut ne pas être chargée au moment d'une tentative d'activation réseau inverse : ce scénario doit être contre-qualifié sur WordPress réel.

## Qualification avant publication

Vérifier les suites de test PHP 8.1/8.4, les mutants MG05/MG12/MG13, la suppression groupée WP-CLI, la portée réseau avec utilisateur abonné, le retour arrière, le passage ZIP GitHub 3.0.0 → 3.0.1, l'identité bloc/shortcode, la sortie du paquet ZIP et les mises à jour. Vérifier le domaine de traduction sans masquer artificiellement les écarts. Exécuter une vraie recette WordPress + MySQL + Piwigo avant publication. Ne toucher ni à la PR #133, ni aux branches canoniques, ni à la production.
