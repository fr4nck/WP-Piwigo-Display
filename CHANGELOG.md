## 1.6.1

* Documentation complète de l'affichage récursif.
* Ajout d'exemples pour `recursive` et `depth`.
* Mise à jour du README et du `readme.txt`.
* Ajout d'une page dédiée aux albums récursifs.
* Mise à jour de la documentation des shortcodes.
* Ajout de `ROADMAP.md`.
* Aucun changement fonctionnel dans le moteur d'affichage.

## 1.6.0

* Affichage d'un album avec l'ensemble de ses sous-albums.
* Ajout de la profondeur configurable avec `depth`.
* Pagination automatique au-delà de 500 images.
* Suppression des doublons entre albums.
* Cache distinct selon l'album, le mode récursif et la profondeur.

## 1.5.5

* Correction d’une erreur fatale lors de l’appel à l’API Piwigo.
* Ajout de la méthode manquante de validation de l’URL de base.
* Vérification des appels internes aux méthodes de classe.
* Aucun changement fonctionnel en dehors de ce correctif.

## 1.5.4

* Fourniture d’une archive complète et directement installable.
* Intégration du correctif du test de connexion Piwigo.
* Intégration du correctif de l’erreur fatale du diaporama.
* Vérification de la présence de toutes les classes requises.
* Aucun dossier de patch incomplet dans l’archive.

## 1.5.3

* Correction d’une erreur fatale lors de l’affichage du diaporama.
* Ajout de la méthode manquante de validation du mode de navigation.
* Aucun changement fonctionnel en dehors de ce correctif.

## 1.5.2

* Correction du bouton de test de connexion Piwigo.
* Interception des erreurs internes afin de ne jamais interrompre l’administration WordPress.
* Utilisation d’une requête GET simple vers `pwg.session.getStatus`.
* Messages de diagnostic plus précis.

## 1.5.1

* Durcissement de la validation des paramètres de shortcode.
* Validation plus stricte des URL Piwigo.
* Sécurisation des réglages d'administration.
* Amélioration de l'échappement des titres issus de Piwigo.
* Suppression de l'utilisation de `innerHTML` dans la lightbox.
* Durcissement des appels HTTP vers l'API Piwigo.
* Ajout d'un fichier `SECURITY.md`.

## 1.5.0

* Ajout des presets `galerie`, `slider` et `actualites`.
* Possibilité d’utiliser un album par identifiant, nom ou chemin.
* Amélioration des messages d’erreur destinés aux utilisateurs.
* Ajout du test de connexion Piwigo.
* Nettoyage de fichiers PHP corrompus par les versions intermédiaires.
* Ajout d’un `readme.txt` compatible WordPress.org.

## 1.4.0

* Amélioration de la page de réglages.
* Ajout d’un mode debug pour les administrateurs.
* Affichage d’un résumé technique sous les galeries en mode debug.
* Documentation du mode debug.

## 1.3.1

* Ajout des réglages globaux de tri.
* Ajout des réglages globaux d’ordre.
* Ajout d’une limite globale d’images.
* Les shortcodes peuvent toujours remplacer ces valeurs.

## 1.3.0

* Ajout du paramètre `sort`.
* Ajout du paramètre `order`.
* Ajout du paramètre `limit`.
* Conservation de la compatibilité avec `max`, `latest` et `random`.

## 1.2.0

* Ajout du paramètre `recursive`.
* Ajout du paramètre `depth`.
* Possibilité d'afficher les images des sous-albums.
* Le cache tient compte du mode récursif.

## 1.1.3

* Ajout du paramètre `url` dans le shortcode.
* Possibilité d'utiliser ponctuellement une autre galerie Piwigo.
* Le cache tient compte de l'URL utilisée.

## 1.1.2

* Ajout du bouton de vidage du cache dans les réglages.
* Ajout d’une action sécurisée par nonce.
* Affichage d’un message de confirmation après suppression du cache.

## 1.1.1

* Ajout du paramètre `navigation` pour le diaporama.
* Valeurs possibles : `thumbnails`, `dots`, `none`.
* Les points sont désactivés quand les miniatures sont affichées.
* Ajout du réglage global de navigation par défaut.

## 1.1.0

* Ajout des réglages d’affichage par défaut.
* Les shortcodes peuvent remplacer les réglages globaux au cas par cas.
* Ajout d’un filtre `wp_piwigo_display_shortcode_defaults`.
* Ajout d’un filtre `wp_piwigo_display_render` pour permettre des rendus externes.

# Journal des modifications

## 1.0.6

* Retour au moteur Splide pour le diaporama.
* Correction fiable de `interval` et `speed`.
* Conservation des miniatures optionnelles.
* Conservation de la lightbox.

## 1.0.5

* Correction du tempo automatique du diaporama.
* Remplacement de l’intervalle par une minuterie relancée proprement après chaque image.
* Meilleure stabilité après pause, survol, focus ou clic manuel.

## 1.0.4

* Correction du rendu slider par défaut.
* Suppression du comportement brut variable dans le diaporama.
* Le diaporama affiche les images en entier, sans recadrage ni déformation.
* Passage de la transition par défaut à zéro pour éviter les effets fantômes.

## 1.0.3

* Stabilisation du diaporama en mode brut.
* Limitation propre des photos portrait.
* Ajout du paramètre `speed` pour la durée de transition.
* Correction du défilement irrégulier.

## 1.0.2

* Ajout du mode `fit="raw"`.
* Passage du cadrage par défaut en mode brut, sans recadrage imposé.
* La galerie respecte la hauteur naturelle des images en mode brut.
* Le diaporama respecte les proportions originales en mode brut.

## 1.0.1

* Ajout du mode `fit="auto"` par défaut.
* Détection automatique portrait / paysage.
* Les photos en portrait utilisent `contain` automatiquement.
* Les photos en paysage utilisent `cover` automatiquement.

## 1.0.0

* Stabilisation du rendu galerie.
* Stabilisation du rendu diaporama.
* Ajout des miniatures optionnelles dans le diaporama.
* Conservation de la lightbox locale.
* Conservation des options `latest` et `random`.
* Nettoyage de la documentation.

## 0.9.0

* Ajout d'une lightbox locale.
* Suppression de la dépendance au CDN.
* Ajout des options `latest` et `random`.

## 0.8.1

* Ajout de l'option `rounded`.
* Suppression des angles arrondis par défaut.

## 0.7.1

* Correction de la hauteur par défaut du diaporama.
