# WP Piwigo Display

Plugin WordPress pour afficher des albums Piwigo via l’API officielle, sans copier les images dans la médiathèque WordPress.

## État du projet

**Version candidate actuelle : 3.0.0-rc.3.**

La dernière version stable effectivement publiée avant la V3 est **1.8.0**. La branche de développement **2.0.0 n’a jamais été distribuée comme release publique** : ses travaux ont été repris et consolidés dans la V3.

La V3 est actuellement en phase de Release Candidate et doit encore être considérée comme une version de test avant la publication de 3.0.0 stable.

## Fonctionnalités principales de la V3

- connexion à Piwigo via l’API officielle ;
- albums publics et albums privés autorisés via compte de service côté serveur ;
- bloc Gutenberg dynamique ;
- intégration Classic Editor avec aperçu TinyMCE ;
- composeur d’administration ;
- parité fonctionnelle entre Gutenberg, Classic Editor et le composeur ;
- galerie responsive classique ;
- diaporama / carousel Splide ;
- Masonry natif basé sur les colonnes CSS ;
- lightbox ;
- sélection d’album par identifiant, nom, chemin ou arborescence ;
- sélecteur visuel hiérarchique et recherchable ;
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

La V3 RC3 restaure et protège le compteur de diagnostic dans **WP Piwigo Display → Diagnostic**.

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

## Installation de la RC

1. Télécharger le ZIP de la Release Candidate depuis les artefacts/release GitHub prévus pour la V3.
2. Dans WordPress : **Extensions → Ajouter une extension → Téléverser une extension**.
3. Activer **WP Piwigo Display**.
4. Renseigner l’URL HTTPS de Piwigo dans les réglages du plugin.
5. Tester la connexion.
6. Insérer le bloc Gutenberg, utiliser le composeur ou saisir un shortcode tel que `[piwigo album="154"]`.

Pour les albums privés, utiliser un compte Piwigo dédié, limité aux seuls albums destinés à être publiés dans WordPress.

## Exemples

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

## Modes d’affichage

### Galerie classique

Grille responsive standard, compatible avec les légendes, la lightbox, les formes et les filtres.

### Slider / carousel

Diaporama Splide avec dimensions configurables, autoplay, vitesse de transition, direction et transitions `slide`, `fade` ou `none`.

### Masonry

Disposition en colonnes CSS :

- `type="masonry"` active le mode ;
- `masonry_columns="4"` définit de 2 à 6 colonnes sur grand écran ;
- `masonry_gap="16"` définit l’espacement ;
- le nombre de colonnes diminue automatiquement sur tablette et mobile.

## Compatibilité

- WordPress 6.0 ou supérieur ;
- PHP 8.1 à 8.4 validé par CI ;
- Piwigo accessible en HTTPS pour le compte de service ;
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

## Licence

GNU GPL v3 ou version ultérieure.
