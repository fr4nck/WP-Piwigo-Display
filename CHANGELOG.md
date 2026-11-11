# Journal des versions

## 3.0.1 — maintenance (préparation, non publiée)

- Correction du compositeur WordPress : choisir puis valider un album actualise immédiatement le shortcode via les événements DOM natifs.
- Ajout d’un bouton d’actualisation dans les sélecteurs classique et Gutenberg, pour retrouver les albums ajoutés récemment sans recharger l’éditeur.
- Protection contre les réponses AJAX périmées ; conservation du comportement de présélection et validation explicite des albums.
- Correction du blocage de chargement Gutenberg après fermeture du panneau pendant une actualisation.
- Prise en charge des identifiants commençant par `#` sans modifier le sens des albums réellement nommés, par exemple, « #2024 ».
- Tests de régression JavaScript et PHP renforcés ; version des scripts incrémentée pour éviter les anciens fichiers conservés en cache.
- Cette version reste soumise à une recette réelle WordPress/Piwigo avant toute publication.

## 3.0.0 — version stable

Première version stable de la V3. Elle reprend l'ensemble des changements de la candidate 3.0.0-rc.4, jamais publiée, et les corrections issues de sa requalification sous WordPress/Piwigo :

- Splide mesure les diapositives sans la mise en page de secours : la première photo s'affiche au chargement et la boucle ne la saute plus.
- La photo de secours tient dans la hauteur de la piste ; les vignettes ne pilotent plus un Splide dont le montage a échoué.
- Les pourcentages de largeur du diaporama s'appliquent dans la colonne de contenu du thème, y compris dans les thèmes à blocs, sans débordement.
- Les poignées de redimensionnement Gutenberg fonctionnent à la souris et au clavier dans le canevas de l'éditeur et conservent le focus.
- Le canevas de l'éditeur charge les styles publics : la prévisualisation du diaporama correspond au rendu public.
- Le bloc Santé API & cache affiche la méthode Piwigo des appels GET, dont la sonde du diagnostic.

## 3.0.0-rc.4 — candidate non publiée, intégrée à 3.0.0

- Suppression du mécanisme automatique qui écrasait les releases et leurs tags.
- Correction des erreurs fatales lorsque WordPress transmet des attributs vides aux shortcodes.
- Application effective des presets, sans écraser les réglages explicitement fournis.
- Masquage des messages techniques issus de Piwigo sur les pages publiques.
- Nettoyage des options et transients du plugin lors de la désinstallation.
- Suppression du repli sur cache périmé pour les albums privés du compte de service.
- Correction des erreurs AJAX, des retours invalides et de la fermeture du sélecteur pendant le chargement.
- Correction de la conversion shortcode vers bloc Gutenberg et du traitement littéral des dollars dans TinyMCE.
- Tests de non-régression PHP et JavaScript correspondants.
- Harmonisation des versions dans l'en-tête, les métadonnées Gutenberg, les assets et la documentation.
- Rectification de l'historique public : GitHub a également publié 1.9.0 et 1.10.0 comme releases non-préversions ; la dernière est 1.10.0.
- Amélioration de l'accessibilité : pause/reprise des diaporamas automatiques et confinement/restauration du focus dans la lightbox.
- Avec compte de service configuré, les contributeurs conservent le sélecteur d'albums public au lieu d'un refus d'accès.
- La lightbox des diaporamas en boucle ne parcourt plus les diapositives dupliquées par Splide.
- Le diaporama conserve une première photo visible et des commandes natives si Splide est absent ou échoue.
- Les largeurs et alignements du diaporama respectent le conteneur des thèmes à blocs.
- Les poignées Gutenberg capturent le pointeur et préservent le focus lors des changements au clavier.
- Le bouton du composeur Classic Editor reste disponible aux contributeurs sans autorisation de téléversement.
- Le diagnostic distingue les échecs API des statuts HTTP, actualise l'erreur et évite un verdict vert sans configuration.

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
