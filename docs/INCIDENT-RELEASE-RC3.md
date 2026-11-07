# Incident de publication de la RC3

## Faits vérifiés

- La version `3.0.0-rc.3` a été publiée initialement depuis le commit `599f34655c7d78426646e52ed18855bd4243e421`.
- Un correctif ultérieur a produit le commit `cfecf6497ace2592b1e7e4bcf74b0897f4300223`, sans changer le numéro de version.
- Le 5 novembre 2026, le workflow `build-plugin.yml` a supprimé et recréé la release et le tag `v3.0.0-rc.3` lors d'un push sur la branche.
- Le tag actuel `v3.0.0-rc.3` pointe donc vers `cfecf6497ace2592b1e7e4bcf74b0897f4300223`, et non vers l'original.
- Le paquet GitHub actuellement publié porte le même numéro que le paquet précédent, bien que le code ne soit pas identique.

## Conséquences

Deux contenus différents ont circulé sous le nom `3.0.0-rc.3`. Les comptes rendus de recette doivent indiquer impérativement leur SHA de commit et, si disponible, l'empreinte SHA-256 du ZIP effectivement testé.

L'empreinte originale du ZIP publié avant le remplacement n'est pas établie. Un ZIP reconstruit depuis `599f346` ne doit pas être présenté comme identique au ZIP historique sans comparaison des empreintes.

## Conservation et résolution

- Ne pas réécrire ou supprimer une nouvelle fois le tag existant pour tenter de restaurer l'historique.
- Conserver les commits `599f346` et `cfecf64` et les références des exécutions GitHub Actions historiques.
- Conserver l'identification explicite de l'incident dans la documentation.
- `audit/3.0.0-rc.4` dérive de `cfecf64` et prépare une nouvelle candidate distincte.
- Le nouveau workflow conserve les ZIP de branches comme artefacts de vérification et refuse de remplacer une release existante.
- La création du tag `v3.0.0-rc.4` doit rester une décision explicite, uniquement après recette complète et sauvegarde de l'empreinte du ZIP.

## Références GitHub

- [Commit RC3 initial](https://github.com/fr4nck/WP-Piwigo-Display/commit/599f34655c7d78426646e52ed18855bd4243e421)
- [Commit RC3 remplacé](https://github.com/fr4nck/WP-Piwigo-Display/commit/cfecf6497ace2592b1e7e4bcf74b0897f4300223)
- [Ancien workflow de build](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/32302715982)
- [Workflow ayant publié le paquet remplacé](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/42033799624)

Dernière vérification : 5 novembre 2026.
