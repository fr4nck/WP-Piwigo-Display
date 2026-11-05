# Journal des versions

## 2.0.0 — 28 juillet 2026

- Ajout d’un compte de service Piwigo dédié à WordPress pour récupérer les albums privés explicitement autorisés.
- Authentification `pwg.session.login` réalisée uniquement côté serveur.
- Cookies Piwigo conservés uniquement pendant la requête PHP et jamais persistés dans WordPress.
- HTTPS obligatoire et refus des redirections HTTP pour les appels authentifiés.
- Refus des sessions `guest` et validation du statut de session.
- Réglages WordPress avec mot de passe jamais réaffiché et priorité aux constantes `wp-config.php`.
- Test de connexion du compte technique et messages d’administration dédiés.
- Sélecteur d’albums utilisant les droits du compte de service.
- Récupération des images privées pour les galeries et les diaporamas.
- Séparation des caches anonyme et authentifié, avec purge lors d’un changement d’identifiants.
- Ajout du redimensionnement visuel des diaporamas dans Gutenberg, à la souris et au clavier.
- Ajout d’un aperçu TinyMCE et de la réouverture du composeur par double-clic.
- Compatibilité maintenue avec Gutenberg, l’éditeur classique, le composeur et les shortcodes existants.
- Validation syntaxique automatisée sous PHP 8.1, 8.2, 8.3 et 8.4.
- Génération automatique du ZIP installable par GitHub Actions.

## 1.13.0

- Ajout du composeur de shortcode dans l’éditeur classique.
- Paramétrage des galeries, diaporamas, tris, limites, orientations, légendes, styles et tags.

## 1.12.0

- Ajout du bloc Gutenberg dynamique avec aperçu serveur.
- Réutilisation du moteur de rendu des shortcodes.

## 1.11.0

- Ajout du filtrage par tags Piwigo avec `tag`, `tags` et `tag_mode`.

## 1.10.0

- Ajout du filtrage par orientation : portrait, paysage et carré.

## 1.9.1

- Publication corrective et consolidation du générateur de shortcodes.

## 1.9.0

- Cache mémoire par requête, diagnostic administrateur et chargement conditionnel des scripts.

## 1.8.0

- Ajout des modes d’intégration graphique `theme`, `default`, `minimal` et `none`.

## 1.7.0

- Ajout des modes de légendes.

## 1.6.0

- Ajout des albums récursifs, de la profondeur configurable, de la pagination et de la déduplication.

## 1.5.x

- Durcissement des validations, corrections des erreurs fatales, diagnostic et sécurité des appels API.

## 1.1 à 1.4

- Premières versions du shortcode, réglages, tri, limites, URL Piwigo ponctuelle et mode debug.
