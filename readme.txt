=== Piwigo Display ===
Contributors: fr4nck
Tags: piwigo, gallery, photos, shortcode, slider
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Affiche des albums Piwigo dans WordPress via l’API officielle, sans importer les images dans la médiathèque.

== Description ==

Piwigo Display conserve les images dans Piwigo et génère leur affichage dans WordPress.

La dernière version stable officiellement publiée reste la 1.8.0.

La version 2.0.0 n’a jamais été publiée. Les travaux qui avaient été engagés pour cette version ont ensuite été repris dans le développement de la V3, actuellement en Release Candidate.

La V3 ajoute notamment :

* galeries responsives ;
* diaporamas Splide ;
* lightbox ;
* Gutenberg et éditeur classique ;
* filtres par orientation et tags ;
* albums et sous-albums ;
* cache et diagnostic renforcés ;
* compte de service Piwigo pour les albums privés ;
* prise en charge de la clé API Piwigo lorsqu’elle est disponible.

Ces fonctions restent candidates tant que la V3 n’est pas publiée comme version stable.

== Installation ==

Pour la version stable, télécharger le ZIP de la release 1.8.0 depuis GitHub Releases.

Pour tester une Release Candidate V3, utiliser exclusivement le ZIP attaché à la release GitHub correspondante et remplacer l’extension existante depuis l’administration WordPress. Une désinstallation complète supprime volontairement les réglages et identifiants enregistrés ; elle n’est donc pas recommandée pour une simple mise à jour.

Après installation :

1. Activer Piwigo Display.
2. Renseigner l’URL de Piwigo dans les réglages.
3. Tester la connexion.
4. Utiliser le shortcode `[piwigo album="154"]`, l’éditeur classique ou le bloc Gutenberg.

== Connexion à Piwigo ==

Piwigo Display communique avec l’API Web de Piwigo.

Les requêtes sortantes utilisent les mécanismes HTTP sûrs de WordPress. Par défaut, WordPress peut refuser les hôtes privés ou locaux. Le plugin ne contourne pas cette protection. Un site Piwigo situé sur un réseau privé doit être explicitement autorisé au niveau WordPress si cette configuration est réellement souhaitée.

Une URL Piwigo en HTTP peut fonctionner sur un site WordPress également en HTTP. Sur un site WordPress en HTTPS, les navigateurs peuvent bloquer certaines ressources provenant d’un Piwigo en HTTP ; HTTPS est donc recommandé des deux côtés.

== Shortcodes ==

`[piwigo album="154"]`

`[piwigo album="154" type="slider"]`

`[piwigo album="154" recursive="true" depth="2"]`

`[piwigo album="154" sort="date" order="desc" limit="20"]`

== Frequently Asked Questions ==

= Les images sont-elles copiées dans WordPress ? =

Non. Elles restent dans Piwigo.

= Quelle est la dernière version stable publiée ? =

La version 1.8.0. La 2.0.0 n’a jamais été publiée.

= Où en est la V3 ? =

Elle est en Release Candidate et n’est pas encore annoncée comme version stable.

= Piwigo doit-il être accessible publiquement ? =

Il doit être accessible depuis le serveur WordPress. Les protections SSRF de WordPress restent actives ; Piwigo Display ne force pas l’accès aux adresses privées ou locales.

== Changelog ==

= 3.0.0-rc.4 =

* Durcissement des échanges HTTP avec Piwigo et du diagnostic.
* Neutralisation des détails d’erreurs API dans le rendu public.
* Masquage de l’adresse Piwigo dans les exports de diagnostic.
* Préparation de Splide 4.1.4 en dépendance locale avec licence et provenance documentées.
* Nettoyage des métadonnées et de la documentation avant 3.0.0 stable.

= 1.8.0 =

* Albums récursifs et profondeur configurable.
* Amélioration de l’intégration graphique.
* Évolutions du rendu et du cache.

= Développement non publié =

Les travaux postérieurs à 1.8.0, y compris ceux initialement regroupés sous le numéro 2.0.0, appartiennent au développement V3 et ne correspondent pas encore à une release stable publiée.
