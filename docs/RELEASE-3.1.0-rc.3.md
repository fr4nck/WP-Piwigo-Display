# Piwigo Display 3.1.0-rc.3 — notes de candidate (non publiée)

**Statut au 17 novembre 2026 : en qualification, aucune release GitHub ni tag RC3 créé.** La dernière stable publiée est `v3.0.1` (13 novembre 2026). Les préversions `v3.1.0-rc.1` et `v3.1.0-rc.2` demeurent immuables.

## Correctifs depuis RC2

- Les noms d'albums identiques ne sont plus résolus selon l'ordre de réponse de Piwigo. Un nom ambigu est refusé ; les chemins complets, permaliens et IDs explicites conservent leur signification. Aucune image privée n'est demandée après ambiguïté.
- Un nonce de sélecteur expiré entraîne HTTP 403 avant tout accès à Piwigo, avec conseil de sauvegarder le contenu de l'éditeur puis de recharger ; la sélection n'est pas écrasée et la récupération est testée en Gutenberg et jQuery.
- Deux blocs Gutenberg partagent l'actualisation de la liste sans accepter une réponse périmée. Fermeture, annuler/rétablir, sauvegarde et réouverture sont couverts.
- Renforcement des invariants de cache public/privé et des gardes de transport, budget et administration ; la lightbox historique et les modes facultatifs restent préservés.

## Preuves réelles sur la tête avant versionnement

- Code `40cdcbade9d87fe972d64f506296127d7b86f059` : [Plugin CI PASS](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/43930406418).
- [Piwigo 16.2 / Darkroom et 16.4 / OpenStreetMap dans Chromium : 2/2 PASS](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/43930406230).
- [Gutenberg, homonymes privés, cache et nonces expirés : 2/2 PASS](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/43930406270).

Ces preuves ne suffisent pas à elles seules pour qualifier le nouveau paquet portant `3.1.0-rc.3`.

## Critères avant toute décision de publication

1. Sur **le commit de versionnement RC3 exact** : lint PHP 8.1/8.4, tests PHP/JavaScript, sécurité, accessibilité, WordPress Plugin Check et provenance.
2. Construire deux **ZIP candidats de vérification** distincts (distribution GitHub `wp-piwigo-display` et arbre WordPress.org `display4piwigo`), vérifier structure, exclusions et SHA-256 ; ne pas publier ni substituer de nouveau dossier à une installation existante.
3. Rejouer migrations **3.0.0 → 3.0.1 publiée → candidate RC3 → retour 3.0.1 → réinstallation RC3** sur WordPress mono/multisite, notamment `wpd_custom_svg_masks`, shortcodes, blocs, réglages et compte de service.
4. Confirmer les tests réels Piwigo 16.2/16.4 et conserver la réserve [OpenStreetMap #97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97) sur l'installation externe qui a signalé le défaut.
5. Vérifier la sauvegarde/restauration du site cible avant installation **en place**. Ne jamais désinstaller la 3.0.1 pour migrer ni effacer les options.
6. Obtenir un **GO explicite et distinct pour créer le tag et publier une éventuelle RC3** ; le choix du slug WordPress.org nécessite encore une approbation propre.

**Verdict actuel : NO-GO publication.** Les workflows de vérification produisent uniquement des artefacts éphémères et des comptes rendus.
