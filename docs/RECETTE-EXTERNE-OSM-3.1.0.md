# Recette externe — Piwigo Display 3.1.0 et OpenStreetMap

**Version du document : 13 novembre 2026.** Cette fiche accompagne la [PR de consolidation #146](https://github.com/fr4nck/WP-Piwigo-Display/pull/146) et [l'issue #97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97). La version 3.1.0 est une **candidate non publiée**, non destinée à être installée directement sur un site en production.

## Objectif

Confirmer que Piwigo Display récupère normalement les albums et photos d'un **Piwigo équipé d'OpenStreetMap**, sans supprimer ni masquer la carte côté Piwigo. Le laboratoire public a testé Piwigo 16.4.0 et une version épinglée de l'extension ; cela ne prouve pas la résolution sur toutes les combinaisons de versions, de thèmes et de modules.

## Préalables

- Un **WordPress de préproduction ou de test**, indépendant du site public, avec un **Piwigo de test** ou des albums **publics non sensibles**. Aucune expérimentation sur le site en production.
- Une sauvegarde vérifiée de la base WordPress, des options et du répertoire `wp-piwigo-display`. Une restauration doit avoir été essayée.
- Noter les versions **sans identifiants** : WordPress, PHP, Piwigo, OpenStreetMap Piwigo, thème Piwigo (ex. Bootstrap Darkroom), thème WordPress, Piwigo Display actuellement installé, et le type d'album concerné.
- Télécharger le ZIP **GitHub** `piwigo-display-3.1.0.zip` depuis les [artefacts du dernier workflow de qualification](https://github.com/fr4nck/WP-Piwigo-Display/pull/146/checks), et lire `SHA256SUMS` et `PROVENANCE.txt`. Un artefact GitHub Actions est éphémère et nécessite parfois une connexion GitHub ; ne pas réutiliser un ZIP dont l'empreinte ou le commit ne correspondent pas au dernier HEAD qualifié. Ne pas utiliser la variante `display4piwigo-wporg-3.1.0-candidate.zip` pour une mise à jour en place.

## Protocole sur préproduction

1. Vérifier que la **3.0.1 stable** fonctionne, avec un album Piwigo classique et un album contenant une carte OpenStreetMap. Noter les deux résultats avant l'installation.
2. Sauvegarder de nouveau, puis effectuer une **mise à jour en place** vers la 3.1.0 candidate : conserver le même dossier `wp-piwigo-display/` et **ne pas désinstaller** l'ancienne extension. Ne jamais activer simultanément une seconde copie du même plugin.
3. Tester sur WordPress une galerie utilisant `[piwigo]` avec un album ordinaire puis un album dont Piwigo affiche une carte. Le plugin WordPress doit afficher les **photos et les albums** et ne pas injecter de carte dans sa galerie par défaut.
4. Cliquer sur une miniature : la **lightbox historique** doit fonctionner si le comportement par défaut est choisi. Activer si souhaité la navigation `piwigo_photo` ou `piwigo_album` et vérifier la destination, notamment la carte sur la page Piwigo.
5. Confirmer dans Piwigo que la **carte et le marqueur GPS** restent visibles sur la page de l'album ou de la photo géolocalisée. Aucun réglage OpenStreetMap ne devrait avoir été modifié par Piwigo Display.
6. Contrôler les URL d'albums qui commencent par `#`, les contenus Gutenberg/éditeur classique déjà enregistrés et une page mobile.
7. En cas d'échec : sauvegarder uniquement le **message d'erreur expurgé** et les captures publiques, puis revenir au ZIP officiel 3.0.1 dans le même répertoire. **Ne pas cliquer sur Supprimer**, ne pas exécuter `uninstall.php`.

## Retour souhaité (sans secrets)

```text
Version WordPress :
Version PHP :
Version Piwigo :
Version d'OpenStreetMap Piwigo :
Thème Piwigo et options de carrousel :
Version de Piwigo Display (avant / après) :
ALBUM CLASSIQUE avant / après : OK / KO
ALBUM AVEC CARTE avant / après : OK / KO
CARTE ET MARQUEUR VISIBLES SUR PIWIGO : OUI / NON
LIGHTBOX HISTORIQUE : OK / KO
NAVIGATION PHOTO / ALBUM : OK / KO / NON TESTÉ
Erreur expurgée, étape de reproduction et navigateur :
```

Ne jamais publier : mots de passe, cookies, clés API, contenu de `wp-config.php`, compte de service, export complet d'options ou URL/ID d'albums privés. Une capture de la galerie **publique** ou une description du rendu suffit souvent.

## État et limites

La reproduction sur **l'installation exacte à l'origine du signalement** reste indispensable pour fermer l'issue #97. Une validation de laboratoire, même avec les bonnes versions et un thème proche, ne remplace pas son retour. Aucun accès au serveur ni à WordPress/Piwigo de production n'est demandé.
