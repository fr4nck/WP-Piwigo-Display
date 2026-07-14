## 1.9.0

* Chargement conditionnel des ressources JavaScript selon le type d'affichage.
* Cache mémoire pendant une requête PHP afin d'éviter les appels API identiques.
* Ajout d'une page de diagnostic réservée aux administrateurs avec export TXT.
* Correction de la correspondance entre diapositives et miniatures du diaporama.
* Mutualisation du test de connexion Piwigo et de la déduplication des images.
* Consolidation de la documentation et de l'architecture interne.

## 1.8.0

* Ajout du paramètre `style`.
* Ajout du mode `theme` utilisant les variables CSS du thème WordPress.
* Ajout des modes `default`, `minimal` et `none`.
* Ajout d'un réglage global d'intégration graphique.
* Mise à jour du README, du readme.txt et de la documentation.
* Aucun changement dans le moteur récursif ou l'API Piwigo.

## 1.7.0

* Ajout du paramètre `caption`.
* Modes disponibles : `default`, `none`, `title`, `description` et `title-description`.
* Ajout d'un réglage global des légendes dans l'administration WordPress.
* Prise en charge cohérente des légendes dans les galeries, diaporamas et lightbox.
* Ajout de classes CSS dédiées au titre et à la description.
* Mise à jour du README, du readme.txt et de la documentation.
* Aucun changement dans le moteur récursif.

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
