# Architecture

Ce document décrit l'organisation interne de WP Piwigo Display.

L'objectif est de conserver un code simple, modulaire et facile à maintenir.

---

# Principe général

Le plugin suit le cheminement suivant :

```text
Shortcode
    │
    ▼
API Piwigo
    │
    ▼
Cache WordPress
    │
    ▼
Préparation des données
    │
    ▼
Rendu HTML
    │
    ▼
JavaScript / CSS
```

Chaque étape possède une responsabilité précise.

---

# Organisation des fichiers

```text
wp-piwigo-display/
│
├── assets/
│   ├── css/
│   └── js/
│
├── includes/
│   ├── class-wpd-api.php
│   ├── class-wpd-plugin.php
│   ├── class-wpd-renderer.php
│   ├── class-wpd-settings.php
│   └── class-wpd-shortcode.php
│
├── docs/
├── README.md
├── CHANGELOG.md
├── LICENSE
└── wp-piwigo-display.php
```

---

# Description des classes

## class-wpd-api.php

Communique avec l'API officielle de Piwigo.

Responsabilités :

- authentification ;
- appels à l'API ;
- récupération des albums ;
- récupération des images.

---

## class-wpd-shortcode.php

Analyse les paramètres du shortcode.

Responsabilités :

- validation des paramètres ;
- valeurs par défaut ;
- préparation des options de rendu.

---

## class-wpd-renderer.php

Construit le HTML affiché par WordPress.

Responsabilités :

- galerie ;
- diaporama ;
- lightbox ;
- affichage des informations.

Le moteur de rendu ne communique jamais directement avec l'API.

---

## class-wpd-plugin.php

Point d'entrée du plugin.

Responsabilités :

- chargement des classes ;
- enregistrement des hooks WordPress ;
- chargement des feuilles de style et scripts.

---

# Cache

Les données provenant de Piwigo sont mises en cache grâce aux transients WordPress.

Objectifs :

- limiter les appels à l'API ;
- améliorer les performances ;
- accélérer l'affichage des pages.

---

# Philosophie

Chaque classe ne doit remplir qu'une seule responsabilité.

Le plugin privilégie un code lisible à un code complexe.

Les évolutions doivent s'intégrer à cette architecture sans remettre en cause son fonctionnement.
