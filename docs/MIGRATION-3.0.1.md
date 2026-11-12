# MIG-301 — Transition GitHub vers WordPress.org

État au 12 novembre 2026 : **correctifs en PR, non fusionnés ni publiés**. Cette procédure est obligatoire pour toute migration existante, mais ne vaut **pas** recette WordPress/Piwigo réelle ni autorisation de déploiement. Le tag et l'archive v3.0.0 restent immuables. Aucun slug WordPress.org n'est considéré approuvé. La copie de recette WordPress.org utilise provisoirement le dossier/domaine `display4piwigo` et le nom affiché `Display4Piwigo`; les identifiants persistants `[piwigo]`, `wp-piwigo-display/gallery` et les options restent intacts.

## Contrat de compatibilité

Ne jamais renommer les clés WordPress `wp_piwigo_display_options` et `wp_piwigo_display_service_account`, les blocs `wp-piwigo-display/gallery` ni les shortcodes `[piwigo]`. Les anciennes pages, galeries et diaporamas doivent continuer à fonctionner sans modification.

**Contrat d'identité des paquets :** les futures distributions doivent conserver le fichier `includes/class-wpd-migration-guard.php`, servant de signature de famille stable même si le nom affiché, le fichier principal, l'URI et le domaine de traduction changent. Le désinstalleur reconnaît aussi le fichier principal historique, le nom, le domaine et l'URI GitHub sans dépendre de sa casse. Un paquet qui retire ce fichier ET modifie tous les marqueurs historiques n'est **pas sûr** : la désinstallation peut supprimer les options communes. Vérifier concrètement la signature et les deux ordres de retrait sur le ZIP officiel, avant tout déploiement.

La variante **GitHub** 3.0.1 possède `Update URI: https://github.com/fr4nck/WP-Piwigo-Display`, pour isoler les mises à jour natives WordPress.org. Cela n'installe pas de système automatique de mise à jour depuis GitHub. Le futur paquet WordPress.org sera préparé **séparément**, sans cet en-tête et avec son domaine de traduction égal au slug approuvé. Les exceptions `TextDomainMismatch` de la CI historique ne constituent pas une conformité WordPress.org.

## Règle P0 : mise à jour en place obligatoire AVANT toute bascule

**Interdiction de migrer directement d'une 3.0.0 installée vers un deuxième répertoire, puis de supprimer l'ancienne copie.** Le `uninstall.php` historique 3.0.0 efface les options partagées, même si une nouvelle copie est active. La nouvelle version ne peut pas corriger une 3.0.0 qui n'a pas été mise à jour.

1. Faire une sauvegarde complète vérifiée : base SQL, fichiers WordPress, `wp-config.php`, ZIP de la 3.0.0, hash des options et preuves des diaporamas. Conserver les secrets hors des journaux.
2. Mettre à jour **dans le même répertoire `wp-piwigo-display/`** la 3.0.0 vers le **ZIP GitHub 3.0.1 qualifié**, sans désinstaller. Vérifier le numéro de version, les fichiers réels, en particulier le nouveau `uninstall.php`, et toutes les options.
3. Installer le futur paquet WordPress.org (après attribution du slug) dans son dossier, **sans l'activer**.
4. Désactiver l'ancienne copie GitHub **sans la supprimer**. Activer la nouvelle copie. Vérifier shortcodes, blocs, diaporamas, albums, droits publics/privés, identifiants du compte de service et canaux de mise à jour.
5. En cas de problème, désactiver la nouvelle copie et réactiver la copie GitHub 3.0.1 ; comparer les options. Si elles ont changé de manière incompatible, restaurer la sauvegarde.
6. Uniquement après recette et accord explicite, retirer le dossier GitHub **3.0.1** en suivant la procédure qualifiée. Son désinstalleur conserve les données si une copie sœur est installée. Ne jamais supprimer la 3.0.0 non corrigée via l'interface ou WP-CLI.

## Limites établies par le contre-audit CA-301-R2

- **Multisite P1 :** ne jamais supprimer l'ancienne 3.0.0 depuis l'administration réseau si la 3.0.1 protectrice n'est active que sur un autre blog. WordPress peut charger le désinstalleur 3.0.0 sur le site principal, **effacer options et identifiants sur tous les sites**, sans avoir chargé le nouveau garde. Le seul parcours autorisé commence par la mise à jour du dossier GitHub **en place** vers 3.0.1, vérification du ZIP et sauvegardes.
- **`WPD_KEEP_DATA_ON_UNINSTALL` ne protège pas la 3.0.0 :** cette constante n'existe pas dans le désinstalleur historique. Elle ne doit jamais être présentée comme un filet de sécurité pour cette version.
- **Paquets WordPress.org :** la variante officielle ne doit pas être déclarée prête tant que sa signature familiale, son domaine de traduction, son canal de mises à jour et son slug réellement approuvé n'ont pas été testés sans les exceptions historiques.
- **Origine d'un ZIP de PR :** GitHub Actions exécute généralement le commit temporaire de fusion de la PR ; consigner séparément le SHA du checkout testé et le SHA de la tête de PR. Ne jamais les présenter comme identiques sans comparaison.
- **Périmètre de recette :** CA-301-R2 a validé des rendus historiques sur WordPress 6.9 / PHP 8.1 et WordPress 7.1.4 / PHP 8.4 avec Piwigo de test. Le sélecteur Gutenberg navigateur, les albums privés HTTPS via compte de service et l'effet externe d'`Update URI` restent non qualifiés.

## Checklist pour la future intégration 3.0.1

- Harmoniser `Version` dans l'en-tête PHP, `WPD_VERSION`, `blocks/piwigo/block.json`, `blocks/piwigo/index.asset.php`, les métadonnées ZIP et la documentation. La branche #137 isolée est encore marquée 3.0.0 : ne jamais la publier telle quelle.
- Rejouer les migrations sur des WordPress et des Piwigo **jetables**, y compris multisite et les diaporamas existants.
- Vérifier la signature `includes/class-wpd-migration-guard.php` dans les deux paquets GitHub / WordPress.org, avec un main PHP et un nom de produit renommés.
- Démontrer la conformité de traduction WordPress.org **sans masquer** `TextDomainMismatch`.
- Faire approuver la publication explicitement, après nouveau contre-audit.

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
