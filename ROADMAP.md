# Feuille de route

Cette feuille de route est indicative. Les évolutions sont priorisées selon les besoins concrets rencontrés lors de l'utilisation du plugin.

## Socle stabilisé — 1.13.6

- explorateur visuel et hiérarchique des albums Piwigo ;
- recherche d'albums par nom et chemin ;
- insertion fiable par identifiant numérique ;
- composeur d'administration et Classic Editor ;
- bloc Gutenberg dynamique ;
- galeries, diaporamas, tags, orientations et récursivité ;
- largeur, alignement et adaptation mobile à 100 % ;
- diagnostic, cache et test de connexion.

## Prochaine étape — redimensionnement visuel

Suivi : issue #30.

- redimensionnement à la souris d'un diaporama déjà inséré ;
- poignée horizontale et, si pertinent, verticale ;
- largeur enregistrée en pourcentage ;
- hauteur enregistrée en pixels lorsqu'elle est définie ;
- indication visuelle des dimensions pendant le glissement ;
- modification au clavier pour l'accessibilité ;
- prise en charge de Gutenberg puis de Classic Editor ;
- maintien du responsive mobile et de la compatibilité des anciens shortcodes.

## Étape suivante — transitions des diaporamas

- transition `slide` ;
- fondu simple `fade` ;
- fondu enchaîné `crossfade`, selon les possibilités de Splide ;
- transition désactivée `none` ;
- direction du déplacement : gauche, droite et, si le moteur le permet proprement, haut et bas ;
- distinction claire entre durée d'affichage, vitesse de transition, effet et direction ;
- réglages disponibles dans Gutenberg, Classic Editor et le composeur d'administration.

## Évolutions ultérieures

- mode Masonry, si un besoin réel le justifie ;
- amélioration progressive du cache ;
- meilleure adoption des variables CSS du thème WordPress ;
- espacements et effets sobres ;
- styles optionnels uniquement lorsqu'ils répondent à un besoin réel ;
- amélioration continue de l'accessibilité et des tests de compatibilité.

## Principe

WP Piwigo Display doit rester un plugin léger : Piwigo gère les photos, WordPress les affiche.
