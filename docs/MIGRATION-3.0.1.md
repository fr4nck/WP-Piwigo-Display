# MIG-301 — Transition GitHub vers WordPress.org

État au 12 novembre 2026 : préparation de migration **non fusionnée, non publiée, non validée sur WordPress réel**. Ne pas modifier le tag et l'archive GitHub `v3.0.0`. Aucun slug WordPress.org n'est approuvé ou arrêté ici.

## Contrat de compatibilité

Conserver les clés WordPress `wp_piwigo_display_options` et `wp_piwigo_display_service_account`, les contenus Gutenberg `wp-piwigo-display/gallery` et le shortcode `[piwigo]`. Ne pas renommer les identifiants des contenus WordPress lors d'une migration de distribution.

Le paquet **GitHub** 3.0.1 déclare `Update URI: https://github.com/fr4nck/WP-Piwigo-Display` pour éviter une récupération accidentelle par le catalogue WordPress.org. Cela ne fournit pas un mécanisme de mise à jour GitHub automatique. Le futur paquet **WordPress.org** doit retirer cet en-tête, utiliser le domaine de traduction exactement égal au slug attribué (en-tête principal, `block.json`, chaînes gettext) et prouver son canal de mise à jour séparé.

## Protections candidates

- Le bootstrap non conditionnel de 3.0.0 devient une closure ; les deux copies peuvent être chargées sans redéclaration de cette fonction. Testé hors ligne avec dépendances WordPress simulées.
- Un garde refuse une activation concurrente, y compris sur un autre blog lors de l'activation réseau. En cas d'état forcé comportant deux copies actives, il peut désactiver la copie secondaire dans l'administration sans exécuter de désinstallation. L'ancien répertoire a priorité.
- `uninstall.php` de la **nouvelle** version conserve impérativement les réglages et identifiants **lorsqu'une autre copie de Piwigo Display est installée**. Lors du retrait définitif de la dernière copie, il nettoie normalement les options du compte de service, comme la version 3.0.0. Pour une réinstallation volontaire sans autre copie, le propriétaire peut demander explicitement la conservation par `define( 'WPD_KEEP_DATA_ON_UNINSTALL', true );`, en acceptant que les identifiants restent dans la base. Si la liste des plugins est indisponible, le script s'abstient de toute purge.
- **Impossible de modifier rétroactivement 3.0.0** : son désinstalleur efface les deux options. Lors de la migration, ne jamais utiliser le bouton « Supprimer » sur l'ancienne copie 3.0.0.

## Installation neuve

Installer le futur candidat officiel seul sur un WordPress jetable ; contrôler réglages, identifiant de bloc, shortcode, médias, traductions et provenance des mises à jour.

## Migration d'une copie GitHub 3.0.0

1. Sauvegarder et vérifier la base, les fichiers et `wp-config.php`, conserver le ZIP original ; ne pas journaliser les secrets.
2. Si la destination est **GitHub 3.0.1 dans le même dossier**, effectuer une mise à jour ZIP en place, **sans désinstallation**. Comparer les options, le compte de service, les blocs et shortcodes, et le rendu d'albums de test.
3. Si la destination est un **nouveau répertoire WordPress.org** : installer le nouveau paquet **sans l'activer**, désactiver sans supprimer l'ancien, puis activer uniquement le nouveau.
4. Comparer les options, les contenus et les droits de lecture d'albums publics/privés sur **Piwigo de test**. Vérifier le ZIP et le dossier ciblés par les mises à jour.
5. Retour arrière : désactiver la nouvelle copie, réactiver l'ancienne et contrôler l'intégrité. En cas de données modifiées de manière incompatible, restaurer base et fichiers depuis la sauvegarde.
6. **Ne jamais désinstaller l'ancienne 3.0.0 par WordPress** ; une fois la recette réellement passée, archiver son dossier hors `wp-content/plugins`, sans exécuter `uninstall.php`.

## Recette obligatoire avant merge/publication

Exécuter les tests `migration-*.php` ainsi que `tests/uninstall-private-cache.php`. Répéter sur véritables WordPress 6.x/7.x, PHP 8.1 et 8.4 et multisite jetable : activation normale et conflit, cas avec deux répertoires, suppression 3.0.1, reprise de configuration, mise à jour ZIP, contenu Gutenberg, shortcode, photos publiques/privées autorisées ou refusées, UI et retour arrière. Tester les updaters tiers présents. Ne pas qualifier une migration WordPress réelle sur la seule base de mocks PHP.

Pas de merge, nouveau tag, release, soumission WordPress.org ni intervention sur production sans validation explicite.
