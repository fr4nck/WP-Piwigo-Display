# Compte de service Piwigo

Le compte de service permet à WordPress de récupérer côté serveur des photos provenant d'albums privés Piwigo, puis de les afficher sur une page publique WordPress.

Cette fonction rend volontairement publiques, sur la page WordPress concernée, des photos qui restent privées dans Piwigo. Le compte utilisé doit être dédié à cette fonction et limité aux seuls albums à publier.

## Configuration recommandée

Définissez les identifiants dans `wp-config.php` afin d'éviter de stocker le mot de passe dans la base WordPress :

```php
define('WPD_PIWIGO_SERVICE_ENABLED', true);
define('WPD_PIWIGO_SERVICE_USERNAME', 'wordpress-publication');
define('WPD_PIWIGO_SERVICE_PASSWORD', 'mot-de-passe-fort');
```

Les constantes ont priorité sur les valeurs éventuellement enregistrées dans les réglages du plugin.

## Règles de sécurité

- utiliser un compte Piwigo dédié, jamais un administrateur ;
- accorder uniquement l'accès aux albums destinés à WordPress ;
- utiliser HTTPS entre WordPress et Piwigo ;
- ne jamais placer les identifiants dans un shortcode, un bloc ou du JavaScript ;
- révoquer immédiatement le compte Piwigo en cas de doute ;
- vider le cache WordPress après une modification urgente des droits.

## État d'implémentation

La classe de configuration et l'isolation du contexte de cache sont introduites en premier. L'authentification HTTP Piwigo, les réglages d'administration, le sélecteur d'albums privés et les tests sont ajoutés dans les commits suivants de la même branche.
