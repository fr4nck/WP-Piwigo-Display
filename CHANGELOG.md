# Journal des versions

## 3.0.0-rc.3 — 19 août 2026

- Restauration du bloc **Santé API & cache** dans le diagnostic administrateur.
- Comptage persistant des appels réels à l’API Piwigo.
- Comptage des HIT/MISS du cache, calcul du taux de HIT et identification de la dernière couche de cache observée.
- Mesure du temps API cumulé, moyen et de l’appel le plus lent.
- Affichage de la dernière méthode Piwigo, du dernier statut HTTP et de la dernière erreur nettoyée.
- Ajout d’un verdict synthétique de santé API/cache.
- Les métriques ne conservent ni identifiants, ni mots de passe, ni corps de requête HTTP.
- Ajout d’un test de non-régression dédié afin d’empêcher la disparition accidentelle du compteur lors d’un futur refactoring.
- Clarification de l’historique public : **1.8.0 est la dernière version stable publiée avant la V3 ; 2.0.0 n’a jamais été distribuée comme release publique**.
- Restauration du nom public **WP Piwigo Display**, afin de conserver explicitement l’identité de l’intégration WordPress.

## 3.0.0-rc.2 — 17 août 2026

- Correction de l’erreur fatale PHP 8.1 lorsque le filtre Masonry recevait la valeur initiale `null` du moteur de rendu, notamment sur les diaporamas.
- Le filtre Masonry accepte désormais cette valeur nullable et laisse le moteur standard poursuivre le rendu hors du mode Masonry.
- Ajout d’un test de non-régression reproduisant exactement l’appel fatal observé dans le journal WordPress.
- Correction du paquet ZIP qui réécrivait les chemins des fichiers CSS et JavaScript sans renommer les fichiers correspondants, ce qui masquait le diaporama.
- Ajout d’un contrôle vérifiant que chaque ressource frontend enregistrée existe dans le paquet final.
- Ajout d’une option « Fond transparent » propre au diaporama, indépendante du style, des arrondis et des ombres.
- Correction de l’aperçu TinyMCE dont les attributs HTML pouvaient apparaître comme texte lors d’une modification par double-clic.

## 3.0.0-rc.1

- Première Release Candidate de la branche V3.
- Refactorisation de l’architecture du plugin pour le socle 3.x.
- Ajout du mode Masonry natif en colonnes CSS.
- Ajout des transitions de slider `slide`, `fade` et `none`.
- Ajout de la direction `ltr` / `rtl`.
- Amélioration de la parité Gutenberg, Classic Editor et composeur d’administration.
- Durcissement sécurité, accessibilité et CI.

## 2.0.0 — jalon de développement non publié

Cette version a servi de jalon interne mais **n’a jamais été publiée comme release publique**. Ses travaux ont été repris dans la V3.

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
- Ajout du redimensionnement visuel des diaporamas dans Gutenberg.
- Ajout d’un aperçu TinyMCE et de la réouverture du composeur par double-clic.
- Compatibilité maintenue avec Gutenberg, l’éditeur classique, le composeur et les shortcodes existants.

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
