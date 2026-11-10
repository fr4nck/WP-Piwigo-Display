# WPORG-01 — Préparer la diffusion sur WordPress.org

État au 10 novembre 2026 : **préparation documentaire ; extension non soumise et non approuvée**. La release GitHub [v3.0.0](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.0) reste la version stable publique.

## Périmètre et références

- Extension : **Piwigo Display**, licence GPLv3 ou ultérieure.
- Version stable : **3.0.0**, commit `1e679ec9cc9ec61a60064c17776dfbfe3d1ab340`.
- ZIP GitHub validé : `piwigo-display-3.0.0.zip`, SHA-256 `50681266437068e425d6504b8f7c7e4d2517403003673f49c8a30b4df28d08c5`.
- Dossier de l'extension installé chez les utilisateurs : `wp-piwigo-display`.
- Fichier principal : `wp-piwigo-display.php` ; text domain : `wp-piwigo-display`.
- Documentation officielle : [préparation et soumission](https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/), [utilisation de SVN](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/), [assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/), [règles](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).

## À régler avant la soumission

- [x] Version publique stable publiée, ZIP installable, empreinte et provenance conservées.
- [x] Base du `readme.txt`, en-tête, licence et tests automatiques Plugin Check disponibles.
- [x] Limite de compatibilité **Piwigo OpenStreetMap** explicitée : [issue #97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97) toujours ouverte. Le filtre de nettoyage des réponses API a des tests automatiques, mais aucune validation réelle sur l'installation concernée ; aucune fonction de carte/GPS annoncée.
- [x] Expliquer les échanges réseau avec le serveur Piwigo configuré, le chargement distant des images et la visibilité des albums privés publiés sur une page WordPress publique.
- [ ] Vérifier auprès de WordPress.org que le slug demandé **`wp-piwigo-display`** est recevable et disponible. Ne pas choisir `piwigo-display` sans mesurer les effets sur les dossiers existants, le text domain et les mises à jour.
- [ ] Vérifier la conformité du paquet exact à la validation du répertoire, y compris licences/mentions des dépendances embarquées (Splide 4.1.4 sous MIT), fichiers de sources et restrictions de marque.
- [x] Préparer et approuver une icône originale aux couleurs de Pêle-Mêle : [128 × 128](../.github/wordpress-org/assets/icon-128x128.png) et [256 × 256](../.github/wordpress-org/assets/icon-256x256.png). Les deux PNG sont prêts pour le futur dépôt SVN (sans inclusion dans le ZIP WordPress).
- [ ] Préparer la bannière et les captures avec des données de démonstration (aucun album privé réel, aucune information personnelle).
- [ ] Préparer un dossier de soumission avec un descriptif concis, le lien GitHub et les réponses aux observations des examinateurs.

## Attention au ZIP déjà publié

La release `v3.0.0` est figée et ne doit pas être reconstruite ni écrasée. Les ajustements du `readme.txt` sur une branche de documentation ne modifient pas le ZIP déjà publié. Avant de soumettre un **nouveau ZIP** différent, il faut refaire les contrôles adaptés et décider d'un nouveau numéro de version si le paquet distribué diffère du tag public.

Ne jamais modifier un tag Git public pour une correction de texte. Une publication SVN WordPress.org doit être qualifiée et correspondre à son versionnement ; ne pas présumer que GitHub et WordPress.org se mettent à jour l'un l'autre.

## Parcours de soumission

1. Se connecter à son compte WordPress.org et utiliser le [formulaire officiel de dépôt](https://wordpress.org/plugins/developers/add/).
2. Soumettre l'archive autorisée et attendre les éventuelles demandes de correction de l'équipe de revue.
3. Après approbation, récupérer l'adresse du dépôt SVN attribuée par WordPress.org et préparer `trunk/` et `tags/<version>/`.
4. Placer les fichiers de l'extension **directement** à la racine de `trunk/` et du dossier de version ; ne pas conserver le répertoire enveloppe `wp-piwigo-display/` dans `trunk/`.
5. Verser les visuels dans le dossier **SVN racine `assets/`**, distinct de `trunk/assets/` qui contient les CSS/JS du plugin.
6. Vérifier la page publique, les liens de téléchargement, les installations neuves, la coexistence avec les anciennes installations GitHub et le comportement des mises à jour.

Les actions sur le compte WordPress.org et le dépôt SVN demandent une autorisation et un accès distincts de GitHub. Rien n'a encore été soumis.

## Identité visuelle

Utiliser une création originale qui suggère une galerie/photo et ne reproduit pas les logos officiels de Piwigo ou WordPress.

Fichiers attendus par WordPress.org dans le SVN `assets/` :

- `icon-128x128.png` et `icon-256x256.png` : fichiers validés disponibles dans [`.github/wordpress-org/assets/`](../.github/wordpress-org/assets/), à copier ensuite dans `assets/` du SVN WordPress.org ; un `icon.svg` facultatif garde un PNG de repli.
- `banner-772x250.png` ; `banner-1544x500.png` facultative.
- `screenshot-1.png`, `screenshot-2.png`, etc., avec les légendes correspondantes dans le `readme.txt`.

Les visuels de catalogue ne doivent pas être ajoutés au ZIP d'installation du plugin. Dans GitHub, conserver leur source de préparation à l'écart des dossiers copiés par le packaging.

## Travaux suivants

- [ ] Finaliser la décision de slug avec la revue WordPress.org ; vérifier précisément les implications des installations existantes.
- [ ] Rassembler la vérification finale du readme (validateur WordPress.org), les licences des composants et le résultat Plugin Check.
- [ ] Soumettre l'extension après décision explicite ; garder une trace de la réponse des examinateurs.
- [ ] Préparer une procédure de publication SVN reproductible depuis Windows 10 / i5-10400 sans 5700X, avec vérification du paquet avant toute écriture.
