# Piwigo Display 3.1.0-rc.2 — Préversion corrective

**Préversion GitHub destinée aux essais.** La 3.0.1 demeure stable ; la RC1 publiée reste intacte.

## Correction expérimentale du HTTP 403

Une installation de test a signalé qu'un appel `pwg.categories.getList` échouait avec HTTP 403. Certains hébergeurs peuvent filtrer les requêtes POST vers `ws.php` mais accepter la requête GET équivalente.

Piwigo Display tente désormais **une seule requête GET de secours** lorsqu'un POST public `pwg.categories.getList` ou `pwg.categories.getImages` reçoit HTTP 403. Seuls les paramètres publics attendus sont autorisés. Les comptes de service et les appels authentifiés restent strictement en POST ; ni mots de passe ni cookies ne passent dans l'URL.

**Si GET renvoie également 403, l'erreur d'accès est conservée.** Il peut s'agir d'une restriction serveur ou de droits Piwigo. La cause exacte sur l'installation à l'origine du signalement n'est pas démontrée.

## Validation

- Tests PHP : POST403→GET200, POST403→GET403, échec réseau GET, succès POST sans GET, refus API Piwigo, absence de données sensibles et méthodes non autorisées.
- WP-CLI dans WordPress jetable : simulation d'un serveur refusant POST ; récupération par GET de six photographies réelles dans les deux configurations Piwigo 16.2 + Bootstrap Darkroom / Piwigo 16.4 + OpenStreetMap.
- Galerie Justified, Collage, Texte-photo, Gutenberg, Classic Editor, lightbox et marqueur GPS sans régression détectée.
- ZIP GitHub et migrations avec retour arrière en WordPress mono-site/multisite, PHP 8.1 et 8.4.

## Installation

Installer uniquement **sur préproduction**, en mettant à jour **dans le même répertoire** `wp-piwigo-display/` sans désinstaller la version précédente. Effectuer une sauvegarde et vérifier la possibilité de revenir au [ZIP officiel 3.0.1](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.1) en remplacement des fichiers, sans suppression des options.

Le paquet attendu est `piwigo-display-3.1.0-rc.2.zip`. Vérifier son SHA-256 avant installation. Le ZIP expérimental WordPress.org `display4piwigo` ne sert pas à une mise à jour en place.

En cas de nouvel HTTP 403, vérifier directement la disponibilité de `/ws.php?format=json&method=pwg.categories.getList` sur l'hébergement Piwigo, et joindre seulement les codes d'erreur et versions (jamais d'identifiants ni de données d'albums privés).
