# Configuration

La configuration globale de Piwigo Display reste volontairement simple. Les réglages d’affichage peuvent ensuite être adaptés dans Gutenberg, Classic Editor, le composeur d’administration ou directement dans les shortcodes.

## URL de la galerie Piwigo

Renseigner l’adresse de la galerie, par exemple :

```text
https://phototheque.pelemele.org
```

Cette URL est utilisée pour communiquer avec l’API officielle de Piwigo.

Pour un usage public simple, les appels restent anonymes. Pour les albums privés, utiliser le compte de service décrit plus bas plutôt qu’un couple identifiant/mot de passe générique.

## Tester la connexion

Le bouton **Tester la connexion Piwigo** vérifie que WordPress peut joindre l’API de la galerie configurée.

En cas d’échec, vérifier en priorité :

- l’URL ;
- HTTPS ;
- la disponibilité de Piwigo ;
- les restrictions réseau éventuelles du serveur WordPress.

## Cache

Le cache limite les appels à Piwigo et accélère l’affichage.

Une durée plus élevée réduit les appels API. Une durée plus courte fait apparaître plus rapidement les nouvelles photos ou modifications d’albums.

Le bouton **Vider le cache** force le prochain affichage à relire les données depuis Piwigo.

Les contextes anonyme et authentifié par compte de service utilisent des caches distincts afin de ne pas mélanger les droits d’accès.

## Compte de service Piwigo

Le compte de service permet au serveur WordPress d’accéder à des albums privés autorisés.

Configuration recommandée dans `wp-config.php` :

```php
define('WPD_PIWIGO_SERVICE_ENABLED', true);
define('WPD_PIWIGO_SERVICE_USERNAME', 'wordpress-publication');
define('WPD_PIWIGO_SERVICE_PASSWORD', 'mot-de-passe-fort');
```

Le compte doit être dédié, non administrateur et limité aux albums destinés à la publication WordPress.

Le client de service exige une URL HTTPS valide, vérifie TLS et n’autorise pas les redirections pendant l’authentification.

Voir [Compte de service Piwigo](COMPTE-DE-SERVICE.md).

## Légendes par défaut

Le réglage **Légendes** détermine les informations affichées par défaut :

- aucune ;
- titre ;
- description ;
- titre et description.

Chaque affichage peut remplacer ce choix via le paramètre `caption`.

## Intégration graphique

Le réglage **Intégration graphique** propose plusieurs modes :

- thème WordPress ;
- style standard du plugin ;
- minimal ;
- sans habillage graphique.

Chaque affichage peut remplacer ce choix via le paramètre `style`.

## Réglages propres à chaque affichage

Selon le type choisi, l’interface permet notamment de régler :

- galerie, slider ou Masonry ;
- album et profondeur des sous-albums ;
- tri, ordre et limite ;
- tags et orientation ;
- légendes et style ;
- lightbox ;
- largeur, hauteur, ratio et ajustement d’image ;
- navigation du slider ;
- autoplay, intervalle et vitesse ;
- transition `slide`, `fade` ou `none` ;
- direction `ltr` ou `rtl` ;
- nombre de colonnes et espacement en Masonry.

## Accessibilité

Le sélecteur d’albums expose son état aux technologies d’assistance, peut être parcouru au clavier et se ferme avec Échap en restaurant le focus.

Les utilisateurs ayant activé la réduction des mouvements dans leur système bénéficient d’un slider sans autoplay ni transition animée agressive.

## Diagnostic

Les fonctions de diagnostic permettent de vérifier la connexion et l’état du plugin sans exposer les identifiants du compte de service.
