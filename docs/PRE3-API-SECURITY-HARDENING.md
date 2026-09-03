# Durcissement API / sécurité pré-3.0

Ce lot ferme les écarts identifiés lors de la revue croisée WordPress ↔ Piwigo ↔ extensions Piwigo (notamment OpenStreetMap) avant promotion de la V3.

## Priorité immédiate

- remplacer les transports HTTP configurables par les variantes sûres de WordPress (`wp_safe_remote_post`) afin de bénéficier de la validation SSRF des destinations et redirections ;
- conserver HTTPS obligatoire, `sslverify` et redirections désactivées pour le compte de service ;
- éviter la divergence entre `WPD_Api` et `WPD_Service_Api` en préparant un transport/parsing commun sans refonte fonctionnelle ;
- tester les réponses Piwigo comportant des champs supplémentaires (`latitude`, `longitude`, métadonnées ajoutées par plugins) ;
- tester les erreurs HTTP, JSON invalide et contenu parasite afin que les plugins Piwigo ne puissent pas casser silencieusement le rendu ;
- vérifier la séparation stricte des caches anonymes et authentifiés ;
- préparer l'adoption des clés API Piwigo, tout en conservant la session login/password en compatibilité tant que nécessaire.

## OpenStreetMap

Le plugin Piwigo OpenStreetMap enregistre un hook WebService global mais ne doit traiter que `pwg.images.setInfo`. `pwg.categories.getImages` doit rester transparent. La compatibilité doit donc être couverte par un test de non-régression, sans introduire de traitement OSM spécifique dans Piwigo Display.

## Critère de sortie

Aucun appel HTTP vers une URL Piwigo configurable ne doit contourner la validation SSRF WordPress ; les réponses enrichies par des plugins Piwigo doivent être tolérées ; les accès publics et authentifiés doivent rester séparés ; la CI doit couvrir ces invariants avant la 3.0 finale.
