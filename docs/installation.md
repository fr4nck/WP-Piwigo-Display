# Installation

## Prérequis

- WordPress 6.0 ou supérieur ;
- PHP 8.1 à 8.4 ;
- une galerie Piwigo accessible depuis le serveur WordPress ;
- HTTPS obligatoire pour le compte de service.

## Version à installer

La dernière version stable effectivement publiée avant la V3 est **1.8.0**.

La branche **2.0.0 n’a jamais été publiée comme release publique**. Ses développements ont été repris dans la V3.

La version de test actuelle est **3.0.0-rc.3**. Une Release Candidate doit être installée uniquement si l’on accepte de participer à la recette et de signaler les régressions éventuelles.

## Installation du plugin

1. Dans WordPress, ouvrir **Extensions > Ajouter une extension > Téléverser une extension**.
2. Sélectionner le ZIP de Piwigo Display correspondant à la version à tester.
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

## Mise à jour vers la V3

La V3 conserve la compatibilité avec les shortcodes et réglages historiques utiles. Les options existantes ne doivent pas être supprimées lors d’une mise à jour.

Lors de la recette RC, vérifier en particulier :

- conservation de l’URL Piwigo ;
- conservation des réglages de cache, légendes et styles ;
- fonctionnement des shortcodes existants issus des versions publiées ;
- fonctionnement du compte de service s’il était déjà configuré dans une version de développement ;
- présence du bloc **Santé API & cache** dans Diagnostic ;
- absence d’erreur PHP ou JavaScript après mise à jour.

## Désinstallation

La désactivation du plugin ne doit pas supprimer les contenus WordPress ni les photos Piwigo. Les images restent stockées uniquement dans Piwigo.
