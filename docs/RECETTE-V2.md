# Recette WP Piwigo Display 2.0.0-rc1

Cette recette doit être exécutée sur une instance WordPress de test reliée à une instance Piwigo HTTPS.

## Préparation Piwigo

1. Créer un utilisateur dédié, non administrateur, par exemple `wordpress-publication`.
2. Créer trois albums :
   - un album public ;
   - un album privé autorisé au compte technique ;
   - un album privé non autorisé au compte technique.
3. Ajouter au moins trois images distinctes dans chaque album.

## Préparation WordPress

1. Installer et activer la version `2.0.0-rc1`.
2. Configurer l’URL HTTPS de Piwigo.
3. Vider le cache du plugin.

## Cas 1 — Fonctionnement anonyme

1. Désactiver le compte de service.
2. Vérifier que l’album public apparaît dans le sélecteur.
3. Vérifier qu’il s’affiche avec le shortcode, Gutenberg et le Composer.
4. Vérifier que les deux albums privés sont absents et inaccessibles.

Résultat attendu : aucun changement par rapport au fonctionnement public historique.

## Cas 2 — Compte technique valide

1. Activer le compte de service.
2. Saisir son identifiant et son mot de passe.
3. Enregistrer les réglages puis tester la connexion.
4. Vérifier que le message de réussite est affiché.
5. Vérifier que l’album privé autorisé apparaît dans le sélecteur.
6. Vérifier qu’il s’affiche avec le shortcode, Gutenberg et le Composer.
7. Vérifier que l’album privé non autorisé reste absent et inaccessible.

Résultat attendu : WordPress publie seulement les albums visibles par le compte technique.

## Cas 3 — Identifiants invalides

1. Remplacer temporairement le mot de passe par une valeur incorrecte.
2. Tester la connexion.
3. Charger une page utilisant un album privé.

Résultat attendu : erreur générique, aucune photo privée, aucun identifiant affiché.

## Cas 4 — Révocation d’un droit

1. Restaurer les bons identifiants.
2. Afficher l’album privé autorisé afin de remplir le cache.
3. Retirer dans Piwigo le droit du compte technique sur cet album.
4. Vider le cache dans WordPress.
5. Recharger la page.

Résultat attendu : l’album n’est plus servi après la purge.

## Cas 5 — Conservation du mot de passe

1. Enregistrer un mot de passe valide.
2. Revenir dans les réglages.
3. Vérifier que le mot de passe n’est pas réaffiché.
4. Modifier uniquement l’identifiant ou un autre réglage en laissant le champ mot de passe vide.

Résultat attendu : le mot de passe existant est conservé sans être renvoyé au navigateur.

## Cas 6 — Contrôle des fuites

Inspecter :

- le code source HTML public ;
- les requêtes AJAX de l’administration ;
- les variables JavaScript ;
- les journaux WordPress et PHP ;
- les URL produites par le plugin.

Résultat attendu : aucun mot de passe, cookie de session Piwigo ou jeton d’authentification n’apparaît.

## Cas 7 — Régression des interfaces

Vérifier successivement :

- bloc Gutenberg ;
- éditeur classique ;
- Composer d’administration ;
- shortcode historique `[piwigo album="..."]` ;
- galerie ;
- diaporama ;
- sous-albums et profondeur ;
- tri, limite, tags, légendes, lightbox ;
- largeur et alignement du diaporama ;
- affichage mobile.

## Validation de sortie

La RC peut être promue en `2.0.0` uniquement si :

- la CI PHP 8.1 à 8.4 est verte ;
- tous les cas ci-dessus sont validés ;
- aucun secret n’est exposé ;
- aucune régression n’est constatée sur les pages existantes.
