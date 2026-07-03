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
