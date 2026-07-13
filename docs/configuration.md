# Configuration

La configuration du plugin est volontairement réduite afin de rester simple à utiliser.

Une fois le plugin activé, ouvrir :

**Réglages → WP Piwigo Display**

Les paramètres suivants sont disponibles.

---

# URL de la galerie Piwigo

Adresse de votre galerie Piwigo.

Exemple :

```text
https://phototheque.pelemele.org
```

Cette adresse est utilisée pour communiquer avec l'API officielle de Piwigo.

---

# Identifiant

Si votre galerie nécessite une authentification, renseignez ici votre identifiant.

Si votre galerie est publique, ce champ peut rester vide.

---

# Mot de passe

Mot de passe associé à l'identifiant précédent.

Il est uniquement utilisé pour les appels à l'API lorsque l'authentification est nécessaire.

---

# Durée du cache

Détermine la durée de conservation des informations récupérées auprès de Piwigo.

Une durée plus élevée permet :

- de limiter les appels à l'API ;
- d'améliorer les performances du site ;
- de réduire le temps de chargement des pages.

Une durée plus faible permet de voir plus rapidement les nouvelles photos publiées.

---

# Vider le cache

Le bouton **Vider le cache** supprime immédiatement les données mises en cache.

Utilisez-le par exemple :

- après l'ajout de nouvelles photos ;
- après la création d'un nouvel album ;
- après une modification importante de votre galerie Piwigo.

Le cache sera automatiquement recréé lors du prochain affichage d'un album.

---

# Conseils

Pour une galerie publique qui évolue peu, une durée de cache de plusieurs heures est généralement suffisante.

Pour une galerie fréquemment mise à jour, il est conseillé de réduire cette durée ou de vider le cache après les mises à jour importantes.


---

# Tester la connexion

Le bouton **Tester la connexion Piwigo** vérifie que WordPress peut joindre l’API de la galerie configurée.


---

# Légendes par défaut

Le réglage **Légendes** détermine les informations affichées par défaut :

- aucune ;
- titre ;
- description ;
- titre et description.

Chaque shortcode peut remplacer ce choix avec le paramètre `caption`.


---

# Intégration graphique

Le réglage **Intégration graphique** propose quatre modes :

- **Thème WordPress** : utilise les variables CSS du thème lorsqu'elles sont disponibles ;
- **Style standard du plugin** ;
- **Minimal** ;
- **Sans habillage graphique**.

Chaque shortcode peut remplacer ce réglage avec le paramètre `style`.
