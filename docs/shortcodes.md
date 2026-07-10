# Shortcodes

Le plugin utilise un unique shortcode :

```text
[piwigo]
```

Le comportement est entièrement piloté par ses paramètres.

---

# album

Identifiant numérique de l'album Piwigo à afficher.

**Obligatoire.**

Exemple :

```text
[piwigo album="154"]
```

---

# type

Détermine le mode d'affichage.

| Valeur | Description |
|--------|-------------|
| gallery | Galerie responsive |
| slider | Diaporama |

Exemple :

```text
[piwigo album="154" type="gallery"]
```

---

# autoplay

Active ou désactive le défilement automatique du diaporama.

Valeurs :

```text
true
false
```

Défaut :

```text
true
```

Exemple :

```text
[piwigo album="154" autoplay="false"]
```

---

# interval

Temps d'affichage de chaque image avant le passage à la suivante.

Valeur exprimée en millisecondes.

Exemple :

```text
[piwigo album="154" interval="5000"]
```

= 5 secondes.

---

# speed

Durée de la transition entre deux images.

Valeur exprimée en millisecondes.

Exemple :

```text
[piwigo album="154" speed="400"]
```

---

# fit

Détermine la manière dont les images sont affichées.

| Valeur | Description |
|--------|-------------|
| cover | Remplit entièrement le cadre en pouvant recadrer l'image. |
| contain | Affiche l'image entièrement sans recadrage. |
| auto | Choisit automatiquement le mode selon l'orientation de la photo. |
| raw | Respecte au maximum la photographie, sans recherche d'uniformité. |

Exemple :

```text
[piwigo album="154" fit="contain"]
```

---

# height

Définit la hauteur du diaporama.

Exemple :

```text
[piwigo album="154" height="450px"]
```

---

# ratio

Détermine le ratio du diaporama lorsqu'aucune hauteur n'est définie.

Exemple :

```text
[piwigo album="154" ratio="16/9"]
```

---

# rounded

Ajoute des coins arrondis.

Valeurs :

```text
true
false
```

Exemple :

```text
[piwigo album="154" rounded="true"]
```

---

# lightbox

Active l'affichage des images en grand format.

Valeurs :

```text
true
false
```

Exemple :

```text
[piwigo album="154" lightbox="false"]
```

---

# random

Affiche un nombre aléatoire d'images de l'album.

Exemple :

```text
[piwigo album="154" random="20"]
```

---

# latest

Affiche uniquement les dernières images de l'album.

Exemple :

```text
[piwigo album="154" latest="15"]
```

---

# max

Limite le nombre maximum d'images affichées.

Exemple :

```text
[piwigo album="154" max="30"]
```


---

# preset

Applique un ensemble de paramètres prédéfinis.

| Valeur | Description |
|--------|-------------|
| galerie | Galerie simple avec lightbox. |
| slider | Diaporama avec miniatures. |
| actualites | Diaporama des dernières images. |

Exemple :

```text
[piwigo album="154" preset="slider"]
```

Un preset peut être complété ou remplacé par des paramètres du shortcode.

```text
[piwigo album="154" preset="actualites" limit="20"]
```

---

# album par nom ou chemin

Le paramètre `album` peut recevoir un identifiant numérique, un nom d'album ou un chemin.

```text
[piwigo album="154"]
[piwigo album="Séjour voile"]
[piwigo album="/ALSH/Été 2026/Séjour voile"]
```

L'identifiant numérique reste le moyen le plus fiable.


---

# recursive

Inclut les photos des sous-albums.

```text
[piwigo album="154" recursive="true"]
```

Par défaut, toute la descendance est incluse.

# depth

Limite le nombre de niveaux de sous-albums parcourus.

```text
[piwigo album="154" recursive="true" depth="1"]
```

- `depth="0"` : album indiqué uniquement ;
- `depth="1"` : album et enfants directs ;
- `depth="2"` : album, enfants et petits-enfants ;
- `depth="10"` : toute la descendance.

Les doublons sont supprimés lorsqu'une même photo appartient à plusieurs albums.
