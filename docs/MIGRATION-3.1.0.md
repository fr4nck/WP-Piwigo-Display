# MIG-310 — Migration de 3.0.1 vers 3.1.0

**Statut : candidate non publiée, 13 novembre 2026.** Aucune installation en production autorisée par cette procédure.

## Invariants

Conserver le dossier `wp-piwigo-display/`, le fichier principal, le shortcode `[piwigo]`, le bloc `wp-piwigo-display/gallery`, les contenus WordPress, identifiants d'album, réglages des diaporamas, options `wp_piwigo_display_options` et `wp_piwigo_display_service_account`. Tous les nouveaux modes sont facultatifs. La lightbox historique demeure le comportement par défaut.

## Préparation

1. Sauvegarder la base SQL, les fichiers WordPress et le répertoire de l'extension ; **vérifier la restauration**. Ne jamais mettre d'identifiants de production dans les rapports.
2. Vérifier l'origine de la 3.0.1 publiée : `piwigo-display-3.0.1.zip`, SHA-256 `20faab8e598299a7db5d3ace1622f3b8f7c464c27b0ce16778ab93e7c1e59ed4` et commit `d25bd97dc4f975d940bc4ce6ca363d04a81c416d`.
3. Inventorier les shortcodes et blocs historiques, les diaporamas, les comptes autorisés et un nom d'album commençant par `#`.

## Migration sur WordPress jetable

1. Vérifier la somme SHA-256 et la provenance du paquet candidat 3.1.0. Un ZIP de PR n'est pas une release.
2. Installer **en remplacement dans le même répertoire** `wp-piwigo-display/` ; ne jamais désinstaller l'ancienne copie ni installer simultanément un second plugin actif.
3. Comparer les empreintes des options historiques et le contenu brut des pages ; valider l'identité shortcode/bloc et les diaporamas avec leur lightbox.
4. Tester les modes facultatifs : navigation Piwigo, Justified, Collage, 18 formes, SVG désinfectés, Texte-photo, polices locales, cache, URLs invalides et albums privés.
5. Vérifier les trois éditeurs, les migrations WordPress mono/multisite et les opérations de suppression groupée.

## rollback vers 3.0.1

1. Conserver le ZIP officiel 3.0.1 et la sauvegarde vérifiée.
2. **Remplacer en place** les fichiers 3.1.0 par ceux de 3.0.1. Ne pas exécuter `uninstall.php` et ne pas cliquer sur « Supprimer ».
3. Vérifier les options, contenus, droits, shortcodes, bloc et diaporamas ; restaurer la base si des valeurs sont altérées.
4. Les **nouveaux contenus spécifiques** à la 3.1.0 ne peuvent pas être garantis visuellement identiques dans la 3.0.1.

## WordPress.org

Le paquet Display4Piwigo, slug candidat `display4piwigo` non attribué, exige une qualification distincte de coexistence, migration et mise à jour. Aucune soumission autorisée.

**Critères de sortie :** CI réussie sur le commit final, vérification des deux ZIP et SHA-256, recette réelle navigateur/Piwigo, compatibilité OpenStreetMap documentée, puis autorisation expresse de publication.
