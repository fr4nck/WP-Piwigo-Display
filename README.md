# Piwigo Display

<img src=".github/branding/piwigo-display-icon.png" width="144" alt="Icône Piwigo Display : galerie de photos bleue et orange" />

Plugin WordPress pour construire et afficher des galeries Piwigo directement dans WordPress, via l’API officielle, sans recopier les images dans la médiathèque WordPress.

> English version: [README.en.md](README.en.md).

## État du projet

**Dernière version stable publiée : 3.0.1 (13 novembre 2026).**

La dernière version stable disponible dans les releases GitHub avant la V3 est **1.10.0** (15 juillet 2026). La branche de développement **2.0.0 n’a jamais été distribuée comme release publique** : ses travaux ont été repris et consolidés dans la V3.

La 3.0.0 est la première version stable de la V3, publiée le 10 novembre 2026. Les candidates 3.0.0-rc.1 à 3.0.0-rc.4 étaient des versions de test ; la 3.0.0-rc.4 n’a jamais été publiée.

La [3.0.1](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.1) corrige le sélecteur d'albums et sécurise la migration. **3.1.0 est une candidate non publiée** : navigation Piwigo, Justified, Collage, formes, SVG, texte-photo et polices locales. Voir [migration 3.0.1 → 3.1.0](docs/MIGRATION-3.1.0.md).

## Distribution

- [Télécharger la version stable 3.0.1](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.1) : le ZIP s'installe depuis l'administration WordPress.
- Le référencement sur WordPress.org est **en préparation** ; l'extension n'y est pas encore disponible. Une publication GitHub ne déclenche pas les mises à jour automatiques du répertoire WordPress.org.
- La préparation de la soumission et des visuels est décrite dans [Distribution WordPress.org](docs/WORDPRESS-ORG.md).
- Ne jamais remplacer les tags et ZIP publics `v3.0.0` et `v3.0.1`.

## Une interface visuelle avant tout

Piwigo Display V3 n’est plus seulement un jeu de shortcodes. Le plugin propose plusieurs outils visuels partageant les mêmes réglages et le même moteur de rendu :

- **bloc Gutenberg dynamique** avec sélection d’album et réglages visuels dans l’éditeur ;
- **composeur d’administration** pour préparer et prévisualiser une galerie avant insertion ;
- **intégration Classic Editor / TinyMCE** avec bouton dédié et aperçu ;
- **sélecteur d’albums visuel, hiérarchique et recherchable** ;
- **parité fonctionnelle** entre Gutenberg, Classic Editor et le composeur ;
- **shortcodes** conservés comme interface avancée, format portable et solution de compatibilité.

L’objectif est de permettre à un utilisateur de construire une galerie sans écrire de code, tout en gardant les shortcodes pour l’automatisation, les usages avancés et la compatibilité historique.

## Fonctionnalités principales de la V3

- connexion à Piwigo via l’API officielle ;
- albums publics et albums privés autorisés via compte de service côté serveur ;
- galerie responsive classique ;
- diaporama / carousel Splide ;
- Masonry natif basé sur les colonnes CSS ;
- lightbox ;
- sélection d’album par identifiant, nom, chemin ou arborescence ;
- sous-albums et profondeur configurable ;
- tri, limites, orientations, tags, légendes et styles ;
- formes d’encadrement ;
- transitions de slider `slide`, `fade` et `none` ;
- direction `ltr` ou `rtl` ;
- fond de diaporama transparent indépendamment du style visuel ;
- largeur, hauteur, ratio, vitesse et intervalle configurables ;
- cache WordPress séparé par contexte d’accès ;
- diagnostic et purge du cache ;
- navigation clavier renforcée, focus visible et prise en compte de `prefers-reduced-motion`.

## Santé API & cache

La V3 RC3 restaure et protège le compteur de diagnostic dans **Piwigo Display → Diagnostic**.

Le bloc **Santé API & cache** permet de suivre notamment :

- nombre d’appels réels à l’API Piwigo ;
- HIT et MISS du cache ;
- taux de HIT ;
- temps API cumulé, moyen et appel le plus lent ;
- dernière méthode Piwigo observée ;
- dernier statut HTTP ;
- dernière erreur détectée ;
- verdict synthétique de santé.

Les métriques sont agrégées sans conserver les identifiants, mots de passe ou corps de requête. Un test de non-régression empêche leur disparition accidentelle lors d’un futur refactoring.

## Installation

1. Télécharger le ZIP `piwigo-display-3.0.0.zip` depuis la release GitHub 3.0.0 et vérifier son empreinte SHA-256.
2. Dans WordPress : **Extensions → Ajouter une extension → Téléverser une extension**.
3. Activer **Piwigo Display**.
4. Renseigner l’URL HTTPS de Piwigo dans les réglages du plugin.
5. Tester la connexion.
6. Créer l’affichage avec le bloc Gutenberg, le composeur d’administration ou le bouton du Classic Editor.
7. Si nécessaire, utiliser directement un shortcode pour un usage avancé ou automatisé.

Pour les albums privés, utiliser un compte Piwigo dédié, limité aux seuls albums destinés à être publiés dans WordPress.

## Modes d’affichage

### Galerie classique

Grille responsive standard, compatible avec les légendes, la lightbox, les formes et les filtres.

### Slider / carousel

Diaporama Splide avec dimensions configurables, autoplay, vitesse de transition, direction et transitions `slide`, `fade` ou `none`.

### Masonry

Disposition en colonnes CSS :

- le nombre de colonnes est configurable de 2 à 6 sur grand écran ;
- l’espacement est configurable ;
- le nombre de colonnes diminue automatiquement sur tablette et mobile.

## Shortcodes : interface avancée

Les shortcodes restent disponibles pour les intégrations manuelles, les modèles, les contenus générés et la compatibilité avec les versions précédentes. Ils ne sont plus l’unique interface du plugin.

```text
[piwigo album="154"]
[piwigo album="154" type="slider" width="72%" height="480px"]
[piwigo album="154" type="slider" transition="fade" speed="700"]
[piwigo album="154" type="slider" transition="slide" direction="rtl"]
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
[piwigo album="154" recursive="true" depth="2"]
[piwigo album="154" sort="date" order="desc" limit="20"]
[piwigo album="154" tags="nature,animaux" tag_mode="all"]
```

## Compatibilité

- WordPress 6.0 ou supérieur ;
- PHP 8.1 à 8.4 validé par CI ;
- Piwigo accessible en HTTPS pour le compte de service ;
- recette réelle 3.0.0 : WordPress 7.1.4, PHP 8.3, Piwigo 16.4.0 ;
- **OpenStreetMap pour Piwigo : compatibilité non confirmée**. Un traitement des réponses API perturbées est présent, mais le problème signalé reste à revalider sur l'installation concernée ([issue #97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97)). L'extension n'affiche pas de cartes GPS ;
- contrôles automatisés de syntaxe, sécurité, accessibilité, rendu frontend, compatibilité PHP, WPCS, packaging et WordPress Plugin Check.

## Documentation

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Shortcodes](docs/shortcodes.md)
- [Compte de service](docs/COMPTE-DE-SERVICE.md)
- [Formes](docs/FORMES.md)
- [Parité des composeurs](docs/PARITE-COMPOSEURS.md)
- [Recette V3](docs/RECETTE-3X.md)
- [Architecture](docs/architecture.md)
- [Feuille de route](ROADMAP.md)
- [Préparation WordPress.org](docs/WORDPRESS-ORG.md)

## Licence

GNU GPL v3 ou version ultérieure.
