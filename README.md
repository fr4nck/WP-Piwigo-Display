# Piwigo Display

<img src=".github/branding/piwigo-display-icon.png" width="144" alt="Piwigo Display : icône de galerie bleue et orange" />

Extension WordPress libre qui affiche les albums et photos d'une photothèque Piwigo sans recopier les images dans la médiathèque WordPress.

## Version stable

La dernière version stable publique est **3.0.0**, sortie le 10 novembre 2026.

- [Télécharger Piwigo Display 3.0.0](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.0)
- [Documentation complète de la V3](https://github.com/fr4nck/WP-Piwigo-Display/blob/release/3.0.0/README.md)
- [Feuille de route V3 et distribution WordPress.org](https://github.com/fr4nck/WP-Piwigo-Display/blob/release/3.0.0/ROADMAP.md)
- [Signaler un problème](https://github.com/fr4nck/WP-Piwigo-Display/issues)

Le code précis de cette version est celui de la branche `release/3.0.0`, figé dans le tag `v3.0.0`. **La branche `main` est distincte et ne doit pas servir à reconstruire l'archive stable 3.0.0.** La version 2.0.0 n'a jamais été publiée comme release publique.

## Fonctionnalités de la V3

- bloc Gutenberg, intégration Classic Editor et composeur d'administration ;
- sélecteur d'albums Piwigo hiérarchique et recherchable ;
- galeries responsives, diaporamas Splide, disposition Masonry et lightbox ;
- tri, filtres, tags, sous-albums, légendes et personnalisation des affichages ;
- prise en charge des albums privés avec un compte de service Piwigo côté serveur ;
- cache, statistiques des appels API et diagnostic ;
- compatibilité avec les anciens shortcodes.

### Limite connue

La compatibilité avec l'extension **OpenStreetMap de Piwigo** n'est pas encore confirmée sur une installation réelle concernée. Un correctif des réponses API est intégré, mais l'[issue #97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97) reste ouverte. Piwigo Display n'offre pas de carte GPS dans WordPress.

## Installation

1. Télécharger le ZIP `piwigo-display-3.0.0.zip` depuis la [release stable](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.0).
2. Dans WordPress, aller dans **Extensions → Ajouter une extension → Téléverser une extension**.
3. Activer Piwigo Display, renseigner l'adresse HTTPS de Piwigo et tester la connexion.
4. Insérer une galerie depuis Gutenberg, l'éditeur classique ou le composeur.

La publication dans le catalogue WordPress.org est **en préparation** ; GitHub reste pour l'instant la source de téléchargement officielle.

## Identité visuelle

L'icône bleue et orange est disponible dans [les ressources GitHub](.github/branding/piwigo-display-icon.png). Les versions 128 et 256 pixels pour WordPress.org sont conservées dans [les ressources de distribution](.github/wordpress-org/assets/), hors du ZIP d'installation.

## Licence

GNU GPL v3 ou version ultérieure.
