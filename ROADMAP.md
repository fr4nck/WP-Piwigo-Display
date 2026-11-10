# Feuille de route

Cette feuille de route décrit l’état public réel de **Piwigo Display pour WordPress**.

## Historique public

- **1.8.0, 1.9.0, 1.10.0** : versions effectivement publiées avant la V3 ; **1.10.0** est la dernière release GitHub non marquée préversion (15 juillet 2026).
- **2.0.0** : jalon de développement interne, **jamais publié comme release publique**.
- **3.0.0-rc.3** : version candidate publiée, touchée par un incident de remplacement de tag/ZIP (voir le journal d'audit).
- **3.0.0-rc.4** : candidate de maintenance jamais publiée, intégrée à la 3.0.0.
- **3.0.0** : première version stable de la V3, publiée sur GitHub le 10 novembre 2026.

Les développements initialement préparés sous le numéro 2.0.0 ont été repris, consolidés et largement étendus dans la V3.

## V3 — version stable 3.0.0

Le socle 3.0 comprend notamment :

- connexion Piwigo via l’API officielle ;
- albums publics et albums privés autorisés via compte de service côté serveur ;
- bloc Gutenberg dynamique ;
- intégration Classic Editor avec aperçu TinyMCE ;
- composeur d’administration ;
- parité fonctionnelle entre Gutenberg, Classic Editor et le composeur ;
- galerie responsive classique ;
- slider / carousel Splide ;
- lightbox ;
- mode Masonry natif en colonnes CSS ;
- sélecteur d’albums visuel, hiérarchique et recherchable ;
- sélection manuelle par identifiant, nom ou chemin en secours ;
- albums récursifs et profondeur configurable ;
- tri, limites, orientations, tags et légendes ;
- styles et formes d’encadrement ;
- transitions de slider `slide`, `fade` et `none` ;
- direction `ltr` / `rtl` ;
- dimensions, ratio, intervalle et vitesse configurables ;
- fond transparent indépendant du style ;
- gestion de `prefers-reduced-motion` ;
- cache séparé par contexte d’accès ;
- purge et résilience du cache ;
- diagnostic administrateur ;
- bloc **Santé API & cache** avec nombre d’appels, HIT/MISS, taux de HIT, temps cumulé/moyen/maximal, dernier appel/statut/erreur et verdict synthétique ;
- tests de non-régression, sécurité, accessibilité, compatibilité PHP 8.1–8.4, WPCS, packaging et WordPress Plugin Check.

La matrice de référence des interfaces est disponible dans `docs/PARITE-COMPOSEURS.md` et la recette V3 dans `docs/RECETTE-3X.md`.

## Après 3.0.0 — suivi des compatibilités

La 3.0.0 est stable et publiée. La recette réelle de la V3 a couvert WordPress 7.1.4, PHP 8.3, Piwigo 16.4.0, Gutenberg, Classic Editor, le composeur d'administration, les galeries publiques/privées, les mises à jour depuis 1.10/RC3/RC4 et les navigateurs Chromium, Firefox et WebKit. Les contrôles PHP 8.1–8.4, sécurité, accessibilité et packaging restent assurés par la CI.

Points encore ouverts ou à mieux qualifier :

- **Piwigo OpenStreetMap / géolocalisation** — [issue #97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97) : un filtre isole le JSON Piwigo lorsque du HTML/JavaScript parasite est ajouté autour des réponses `ws.php?format=json`. Les tests automatiques passent, mais la compatibilité doit être vérifiée sur l'installation réellement affectée. Ne pas annoncer de compatibilité OpenStreetMap complète ni de carte GPS WordPress.
- Quelques limites d'interface non bloquantes restent à suivre : flèches sur un diaporama à une image, statut de diagnostic neutre pour certaines URL refusées et comportement d'une poignée de redimensionnement Firefox hors fenêtre.
- Vérifier progressivement d'autres versions WordPress/Piwigo, l'extension Classic Editor officielle et les appareils mobiles physiques. Les essais automatisés ne remplacent pas les essais réels.

Toute correction fonctionnelle après publication exige une nouvelle version (par exemple 3.0.1), des tests et un nouveau tag ; **ne jamais modifier `v3.0.0` ni son ZIP**.

## WPORG-01 — Référencement dans le répertoire WordPress.org

**État : préparation, non soumis / non approuvé.** [Suivi détaillé](docs/WORDPRESS-ORG.md).

1. Maintenir un `readme.txt` clair et vérifié, avec la limite OpenStreetMap et les échanges avec le serveur Piwigo.
2. Examiner l'identité technique existante `wp-piwigo-display` (dossier installé, fichier principal, text domain) avant de choisir le slug de soumission ; éviter les doubles installations et les ruptures de mise à jour.
3. Vérifier les exigences du répertoire et de Plugin Check sur le paquet soumis ; contrôler les licences des dépendances et le comportement des albums privés.
4. Préparer une icône originale et, ensuite, une bannière et des captures d'écran. Les visuels de catalogue iront dans `/assets` à la racine du dépôt SVN WordPress.org, pas dans le ZIP du plugin.
5. Soumettre le ZIP via un compte WordPress.org ; répondre aux demandes des examinateurs.
6. Après approbation, publier le code dans le `trunk` SVN et créer le tag de version stable, en vérifiant les mises à jour et le nom de répertoire.

La soumission et la publication SVN ne sont pas automatisées avant validation explicite. GitHub reste le dépôt de développement et la référence des versions déjà publiées.

## 3.1 Core

Le développement 3.1 reste séparé des correctifs 3.0.x et des adaptations nécessaires à la distribution WordPress.org.

Axes actuels :

- galerie **Justified** ;
- pagination / chargement progressif (« Charger plus ») ;
- métadonnées et informations photo enrichies ;
- nettoyage progressif WPCS et réduction de la dette technique ;
- maintien d’une CI lisible et ciblée.

Les fonctionnalités créatives avancées doivent rester découplées du Core afin de conserver un plugin principal léger et maintenable.

## Évolutions ultérieures

Après stabilisation du Core :

- filtres frontend plus riches ;
- plein écran amélioré ;
- meilleure adoption des variables et réglages du thème WordPress ;
- optimisation mesurée du cache et des appels Piwigo ;
- API/hooks d’extension plus explicites ;
- préparation progressive d’un moteur de rendu plus portable sans casser l’intégration WordPress actuelle.

## Principes permanents

- Piwigo reste la photothèque et la source des médias ; WordPress les affiche sans duplication inutile.
- Une nouvelle fonctionnalité ne doit pas casser les shortcodes existants.
- Responsive, accessibilité, sécurité et performances font partie de la définition de « terminé ».
- Les diagnostics utiles, notamment Santé API & cache, sont des fonctions du Core et doivent être protégés contre les régressions.
- La documentation publique doit correspondre aux versions réellement distribuées.
