# Feuille de route

Cette feuille de route décrit l’état public réel de **Piwigo Display pour WordPress**.

## Historique public

- **1.8.0** : dernière version stable effectivement publiée avant la V3.
- **2.0.0** : jalon de développement interne, **jamais publié comme release publique**.
- **3.0.0-rc.3** : Release Candidate V3 actuelle.

Les développements initialement préparés sous le numéro 2.0.0 ont été repris, consolidés et largement étendus dans la V3.

## V3 — Release Candidate actuelle

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

## Avant 3.0.0 stable

La V3 reste une Release Candidate tant que la recette réelle n’est pas suffisamment consolidée.

Travail restant :

1. installer le ZIP RC sur des WordPress de recette réels ;
2. tester Administration, Classic Editor et Gutenberg avec des instances Piwigo réelles ;
3. vérifier les anciens shortcodes et les principaux modes d’affichage ;
4. vérifier ordinateur, tablette et mobile ;
5. vérifier albums publics et compte de service ;
6. contrôler le diagnostic Santé API & cache sur une utilisation réelle ;
7. recueillir les retours utilisateurs et corriger les régressions bloquantes ;
8. publier 3.0.0 stable uniquement lorsqu’aucun problème bloquant connu ne subsiste.

Les corrections de bugs et de documentation nécessaires à cette recette restent du périmètre 3.0 RC ; elles ne doivent pas être repoussées artificiellement en 3.1.

## 3.1 Core

Le développement 3.1 se fait séparément de la stabilisation 3.0. Les fonctionnalités 3.1 ne doivent pas contaminer la branche RC tant qu’elles ne sont pas destinées à la V3 stable.

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
