# Piwigo Display

Plugin WordPress pour construire et afficher des galeries Piwigo directement dans WordPress, via l’API officielle, sans recopier les images dans la médiathèque WordPress.

> English version: [README.en.md](README.en.md) — pour celles et ceux qui n’arrivent pas encore à utiliser Google Translate. 😄

## État du projet

**Version candidate actuelle : 3.0.0-rc.3.**

La dernière version stable effectivement publiée avant la V3 est **1.8.0**. La branche de développement **2.0.0 n’a jamais été distribuée comme release publique** : ses travaux ont été repris et consolidés dans la V3.

La V3 est actuellement en phase de Release Candidate et doit encore être considérée comme une version de test avant la publication de 3.0.0 stable.

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
- récupération défensive du JSON Piwigo lorsqu’une extension ajoute accidentellement du HTML ou du JavaScript autour de la réponse API ;
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

## Compatibilité avec les réponses Piwigo polluées

Certaines extensions Piwigo peuvent ajouter accidentellement du HTML ou du JavaScript autour du JSON renvoyé par `ws.php?format=json`. Un cas a notamment été signalé avec OpenStreetMap.

La RC3 sait isoler une réponse Piwigo JSON complète contenant le champ `stat` au milieu de ce contenu parasite. Cette récupération est volontairement étroite : elle ne s’applique qu’aux requêtes émises par **Piwigo Display** vers l’endpoint JSON de Piwigo. Les autres requêtes HTTP de WordPress ne sont pas modifiées.

Le correctif est couvert par des tests automatisés, mais le cas OpenStreetMap reste à valider sur une installation Piwigo réellement affectée avant de considérer ce point comme définitivement clos.

## Installation de la RC

1. Télécharger le ZIP de la Release Candidate depuis les artefacts/release GitHub prévus pour la V3.
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
- contrôles automatisés de syntaxe, sécurité, accessibilité, rendu frontend, compatibilité PHP, WPCS, packaging et WordPress Plugin Check.

## Documentation

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Dépannage](docs/DEPANNAGE.md)
- [Shortcodes](docs/shortcodes.md)
- [Compte de service](docs/COMPTE-DE-SERVICE.md)
- [Formes](docs/FORMES.md)
- [Parité des composeurs](docs/PARITE-COMPOSEURS.md)
- [Recette V3](docs/RECETTE-3X.md)
- [Architecture](docs/architecture.md)
- [Feuille de route](ROADMAP.md)

## Licence

GNU GPL v3 ou version ultérieure.