# Matrice de compatibilité API Piwigo Display

Cette matrice sert de base aux tests de non-régression avant 3.0 finale.

| Cas | Attendu |
|---|---|
| Piwigo standard, album public | `pwg.categories.getImages` retourne une galerie exploitable |
| Piwigo + OpenStreetMap, images sans coordonnées | rendu identique au cas standard |
| Piwigo + OpenStreetMap, images avec `latitude` / `longitude` | champs supplémentaires tolérés, aucun traitement spécifique requis |
| Réponse enrichie par un plugin Piwigo | clés inconnues ignorées sans casser le rendu |
| HTTP non-2xx | erreur WordPress explicite, aucun rendu silencieux |
| JSON invalide / contenu parasite | erreur explicite `wpd_invalid_json` |
| URL Piwigo redirigée vers destination non sûre | requête bloquée par le transport sûr WordPress |
| Compte de service | HTTPS obligatoire, TLS vérifié, aucune redirection, session non persistée |
| Cache anonyme / authentifié | aucune réponse privée réutilisée dans un contexte public |

La présence d'OpenStreetMap ne doit pas modifier le contrat de lecture de `pwg.categories.getImages`. Si une extension Piwigo enrichit les objets image, Piwigo Display doit ignorer les champs qu'il n'utilise pas.
