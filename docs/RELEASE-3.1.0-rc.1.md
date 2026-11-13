# Piwigo Display 3.1.0-rc.1 — notes de préversion

**Préversion GitHub pour essais sur préproduction.** La 3.0.1 reste la dernière version stable. Aucun déploiement automatique sur WordPress.org ou sur un site public.

## Changements

- Ouverture facultative de la photo ou de l'album Piwigo au clic. Lightbox historique conservée par défaut.
- Galeries Justified et Collage, 18 formes, masques SVG contrôlés, texte-photo.
- Polices libres Bebas Neue et Bungee incluses localement (avec licences OFL).
- Shortcodes, blocs Gutenberg et diaporamas existants conservés.

## Contrôles préalables

- Tests PHP, JavaScript, sécurité, accessibilité et Plugin Check ; ZIP et SHA-256.
- Migration WordPress depuis le ZIP officiel 3.0.1, retour à 3.0.1 puis réinstallation, mono-site et multisite.
- Navigateur Chromium sur Piwigo 16.2 / Bootstrap Darkroom / OpenStreetMap et Piwigo 16.4 / OpenStreetMap ; six JPEG synthétiques aux ratios variés et vérification d'un marqueur GPS visible.
- **Réserve :** le problème OpenStreetMap [#97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97) n'a pas encore été confirmé résolu sur l'installation qui l'a signalé.

## Installation sur site de test

1. Sauvegarder et tester la restauration de WordPress avant installation.
2. Télécharger uniquement le paquet GitHub `piwigo-display-3.1.0-rc.1.zip` joint à cette préversion. Vérifier son SHA-256 à l'aide de `SHA256SUMS`.
3. Mettre à jour **en place** le répertoire `wp-piwigo-display/` ; **ne pas désinstaller** la 3.0.1 (son désinstalleur peut supprimer les options historiques). La variante candidate WordPress.org `display4piwigo` n'est pas destinée à cet essai.
4. Vérifier les galeries, les shortcodes existants, le bloc, les permissions d'albums privés, la lightbox et la navigation vers Piwigo.

Pour revenir à 3.0.1, remplacer **dans le même répertoire** les fichiers par ceux du [ZIP officiel v3.0.1](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.1), sans supprimer l'extension, puis vérifier les options et le contenu. Les nouveaux modes 3.1 n'auront pas nécessairement le même rendu en 3.0.1.

La procédure détaillée OpenStreetMap figure dans [la fiche de recette externe](RECETTE-EXTERNE-OSM-3.1.0.md). Pour signaler un défaut, fournir les versions, étapes et messages expurgés ; **jamais d'identifiants ou d'albums privés**.
