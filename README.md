# Piwigo Display

Plugin WordPress pour construire et afficher des galeries Piwigo directement dans WordPress via l’API officielle, sans recopier les images dans la médiathèque WordPress.

> English version: [README.en.md](README.en.md) — pour celles et ceux qui n’arrivent pas encore à utiliser Google Translate. 😄

## État du projet

**Version candidate actuelle : 3.1.0-rc.1.**

La dernière version stable effectivement publiée avant la V3 est **1.8.0**. La branche de développement **2.0.0 n’a jamais été distribuée comme release publique** : ses travaux ont été repris et consolidés dans la V3.

La 3.1 est désormais en phase de recette réelle dans WordPress. Le développement fonctionnel est gelé pendant cette RC : les corrections de bugs restent admises, les nouvelles fonctions attendront une version ultérieure.

## Une interface visuelle avant tout

Piwigo Display propose plusieurs outils partageant le même moteur de rendu :

- **bloc Gutenberg dynamique** avec sélection d’album et réglages visuels ;
- **composeur d’administration** avec prévisualisation ;
- **Classic Editor / TinyMCE** avec bouton dédié et aperçu ;
- **sélecteur d’albums hiérarchique et recherchable** ;
- **parité fonctionnelle** entre Gutenberg, Classic Editor et le composeur ;
- **shortcodes** conservés pour l’automatisation, les usages avancés et la compatibilité historique.

## Nouveautés 3.1

### Justified Gallery

Disposition en lignes justifiées conservant le ratio des images, avec hauteur cible et espacement configurables.

### Collage / Pêle-mêle

Disposition déterministe de photos inclinées, décalées et légèrement superposées. Une même graine et les mêmes photos produisent la même composition.

### Formes et masques SVG personnalisés

La bibliothèque de formes intégrées est étendue avec notamment nuage, cœur, goutte, triangle, pentagone, octogone et enseignes de cartes.

Les administrateurs peuvent aussi importer des masques SVG personnalisés. Le SVG est filtré avant stockage : scripts, événements, styles actifs, références externes, `DOCTYPE`/`ENTITY` et contenus dangereux sont rejetés. Seule la version sanitizée est conservée.

### Texte rempli de photos

Un mot, un titre ou plusieurs lignes peuvent servir de masque typographique rempli par plusieurs photos Piwigo.

Réglages disponibles :

- texte jusqu’à quatre lignes ;
- taille, largeur maximale, interlettrage et hauteur de ligne ;
- alignement gauche, centre ou droite ;
- remplissage **grille**, **masonry** ou **pêle-mêle** ;
- densité et nombre maximal de photos ;
- rotation et dispersion du pêle-mêle ;
- contour, couleur, épaisseur et fond ;
- graine déterministe ;
- police du thème, système, serif ou monospace ;
- polices libres incluses **Bebas Neue** et **Bungee** ;
- import local administrateur de polices **WOFF2/WOFF** validées et stockées dans les uploads WordPress.

Aucune police distante tierce n’est chargée automatiquement.

## Fonctionnalités principales

- connexion à Piwigo via l’API officielle ;
- albums publics et albums privés autorisés via compte de service côté serveur ;
- galerie responsive classique ;
- diaporama / carousel Splide avec fallback natif si Splide tarde ou échoue ;
- Masonry en colonnes CSS ;
- Justified Gallery ;
- Collage / Pêle-mêle ;
- Texte rempli de photos ;
- lightbox ;
- sélection d’album par identifiant, nom, chemin ou arborescence ;
- sous-albums et profondeur configurable ;
- tri, limites, orientations, tags, légendes et styles ;
- formes intégrées et masques SVG personnalisés ;
- transitions de slider `slide`, `fade` et `none` ;
- direction `ltr` ou `rtl` ;
- largeur, hauteur, ratio, vitesse et intervalle configurables ;
- cache WordPress séparé par contexte d’accès ;
- diagnostic et purge du cache ;
- navigation clavier, focus visible et prise en compte de `prefers-reduced-motion`.

## Santé API & cache

Le bloc **Piwigo Display → Diagnostic → Santé API & cache** suit notamment :

- nombre d’appels réels à l’API Piwigo ;
- HIT et MISS du cache ;
- taux de HIT ;
- temps API cumulé, moyen et appel le plus lent ;
- dernière méthode Piwigo observée ;
- dernier statut HTTP ;
- dernière erreur détectée ;
- verdict synthétique de santé.

Les métriques sont agrégées sans conserver les identifiants, mots de passe ou corps de requête.

## Installation de la RC

1. Télécharger le ZIP `piwigo-display-3.1.0-rc.1.zip` produit par GitHub Actions.
2. Dans WordPress : **Extensions → Ajouter une extension → Téléverser une extension**.
3. Activer **Piwigo Display**.
4. Renseigner l’URL HTTPS de Piwigo dans les réglages.
5. Tester la connexion.
6. Créer un affichage avec Gutenberg, le composeur d’administration ou Classic Editor.
7. Tester les nouveaux modes 3.1 avant tout usage de production.

Pour les albums privés, utiliser un compte Piwigo dédié et limité aux seuls albums destinés à être publiés dans WordPress.

## Shortcodes : interface avancée

```text
[piwigo album="154"]
[piwigo album="154" type="slider" width="72%" height="480px"]
[piwigo album="154" type="masonry" masonry_columns="4" masonry_gap="16"]
[piwigo album="154" type="justified" justified_row_height="220" justified_gap="8"]
[piwigo album="154" type="collage" collage_seed="2026"]
[piwigo album="154" type="photo-text" photo_text="ÉTÉ 2026" photo_text_font="bundled-bebas-neue"]
```

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
- [Recette 3.x](docs/RECETTE-3X.md)
- [Architecture](docs/architecture.md)
- [Feuille de route](ROADMAP.md)

## Licence

GNU GPL v3 ou version ultérieure.
