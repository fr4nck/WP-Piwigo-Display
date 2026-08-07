# Compte de service Piwigo

Le compte de service permet à WordPress de récupérer côté serveur des photos provenant d’albums privés Piwigo, puis de les afficher sur une page WordPress.

Cette fonction rend volontairement visibles sur WordPress des photos qui restent privées dans Piwigo. Le compte utilisé doit donc être dédié à cette fonction et limité aux seuls albums destinés à être publiés.

## Configuration recommandée

Définir les identifiants dans `wp-config.php` afin d’éviter de stocker le mot de passe dans la base WordPress :

```php
define('WPD_PIWIGO_SERVICE_ENABLED', true);
define('WPD_PIWIGO_SERVICE_USERNAME', 'wordpress-publication');
define('WPD_PIWIGO_SERVICE_PASSWORD', 'mot-de-passe-fort');
```

Les constantes ont priorité sur les valeurs éventuellement enregistrées dans les réglages du plugin.

## Règles de sécurité

- utiliser un compte Piwigo dédié, jamais un administrateur ;
- accorder uniquement l’accès aux albums destinés à WordPress ;
- utiliser HTTPS entre WordPress et Piwigo ;
- ne jamais placer les identifiants dans un shortcode, un bloc ou du JavaScript ;
- révoquer immédiatement le compte Piwigo en cas de doute ;
- vider le cache WordPress après une modification urgente des droits.

## Fonctionnement

L’authentification est réalisée côté serveur. Les cookies de session Piwigo restent en mémoire pendant la requête PHP et ne sont pas exposés au navigateur.

Le client de service :

- exige une URL HTTPS valide ;
- utilise les appels HTTP sûrs de WordPress ;
- vérifie TLS ;
- interdit les redirections pendant l’authentification ;
- sépare le cache authentifié du cache anonyme.

Les visiteurs WordPress ne sont jamais connectés directement à Piwigo.

## Publication d’un album privé

Un album privé affiché sur une page publique WordPress devient accessible via cette page WordPress. Il faut donc considérer l’affichage comme une publication volontaire des photos concernées.

Avant de publier :

1. limiter les droits du compte de service ;
2. vérifier l’album sélectionné ;
3. tester la page WordPress ;
4. vider le cache si les droits Piwigo viennent d’être modifiés.

## Diagnostic

Les fonctions de diagnostic et de test de connexion ne doivent jamais afficher le mot de passe ni les cookies de session. En cas d’erreur d’authentification, le plugin renvoie un message générique plutôt que les secrets Piwigo.
