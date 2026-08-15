# Installation

## Prérequis

- WordPress 6.0 ou supérieur ;
- PHP 8.1 à 8.4 ;
- une galerie Piwigo accessible depuis le serveur WordPress ;
- HTTPS obligatoire pour le compte de service.

## Installation du plugin

1. Dans WordPress, ouvrir **Extensions > Ajouter une extension > Téléverser une extension**.
2. Sélectionner le ZIP de Piwigo Display.
3. Installer puis activer l’extension.
4. Ouvrir les réglages de Piwigo Display.
5. Renseigner l’URL de la galerie Piwigo.
6. Utiliser **Tester la connexion Piwigo** avant de créer les premiers affichages.

## Premier affichage

Le moyen le plus simple est le bloc Gutenberg **Piwigo Display**. Le composeur d’administration et l’intégration Classic Editor permettent d’obtenir les mêmes réglages principaux.

Un shortcode minimal fonctionne aussi :

```text
[piwigo album="154"]
```

L’album peut être choisi par identifiant, nom, chemin ou sélecteur hiérarchique lorsque l’interface le propose. L’identifiant numérique reste le choix le plus robuste dans un shortcode écrit manuellement.

## Albums privés

Pour afficher des albums privés, créer dans Piwigo un compte dédié à WordPress et limité aux seuls albums destinés à être publiés.

La configuration recommandée utilise `wp-config.php` :

```php
define('WPD_PIWIGO_SERVICE_ENABLED', true);
define('WPD_PIWIGO_SERVICE_USERNAME', 'wordpress-publication');
define('WPD_PIWIGO_SERVICE_PASSWORD', 'mot-de-passe-fort');
```

Ne jamais utiliser un compte administrateur Piwigo comme compte de service.

Voir [Compte de service Piwigo](COMPTE-DE-SERVICE.md).

## Mise à jour depuis la V2

La V3 conserve la compatibilité avec les shortcodes et réglages historiques utiles. Les options existantes ne doivent pas être supprimées lors d’une mise à jour.

Avant la Release Candidate, la version déclarée reste 2.0.0 afin de ne pas présenter prématurément la branche 3.x comme une version stable.

Lors de la recette RC, vérifier en particulier :

- conservation de l’URL Piwigo ;
- conservation des réglages de cache, légendes et styles ;
- fonctionnement des shortcodes existants ;
- fonctionnement du compte de service lorsqu’il était déjà configuré.

## Désinstallation

La désactivation du plugin ne doit pas supprimer les contenus WordPress ni les photos Piwigo. Les images restent stockées uniquement dans Piwigo.
