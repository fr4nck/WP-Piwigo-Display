# QUAL-310 — Qualification de la candidate Piwigo Display 3.1.0

**État au 13 novembre 2026 : candidate en brouillon, aucune publication autorisée.**

## Référence de sécurité et provenance

- [Release GitHub v3.0.1](https://github.com/fr4nck/WP-Piwigo-Display/releases/tag/v3.0.1), commit `d25bd97dc4f975d940bc4ce6ca363d04a81c416d` ; archive officielle SHA-256 `20faab8e598299a7db5d3ace1622f3b8f7c464c27b0ce16778ab93e7c1e59ed4`.
- Sept PR #139–145 empilées sans perte des protections introduites en 3.0.1 ; sommet initial `0d688140f47d85e7b01ad211f1abe48c33d51a8e`.
- PR de consolidation [#146](https://github.com/fr4nck/WP-Piwigo-Display/pull/146) en brouillon, sans merge sur `main` et sans modification de release existante.
- Les archives de vérification ne sont pas des releases : l'empreinte et la provenance doivent correspondre **au commit final qualifié**, pas à une CI antérieure.

## Vérifications déjà réussies

- [Plugin CI #43435321952](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/43435321952), HEAD `12a69c4874e7e5d35f0a670a8f68f8a4bae1c94e` : PHP 8.1/8.4, front-end JS, tests de sécurité/migration, WordPress Plugin Check, archives GitHub et WordPress.org candidates, tests WP-CLI WordPress 6.9 et 7.1.4 mono-site/multisite. Les deux ZIP 3.1.0 ont été vérifiés avec leurs SHA-256 dans le run.
- Les tests de migration de cette CI utilisent le ZIP **public 3.0.1** vérifié par son hash, puis `3.1.0`, un rollback vers `3.0.1`, puis un nouvel upgrade. Ils comparent les empreintes des options historiques, contenus shortcode et bloc, identité des enregistrements et comportement de coexistence/désinstallation.
- [CI navigateur #43435321883](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/43435321883), HEAD `12a69c4874e7e5d35f0a670a8f68f8a4bae1c94e` : WordPress 6.9, Piwigo 16.4.0, MariaDB 11.4 et Chromium Playwright, avec 6 photos et des albums synthétiques, sans identifiants réels.
- Extension réelle [Piwigo/piwigo-openstreetmap](https://github.com/Piwigo/piwigo-openstreetmap) au commit `567f7aae970f41524c03e93b4cd2543d0548d039`, activée via le gestionnaire Piwigo. Les réponses `pwg.categories.getList` et `pwg.categories.getImages` sont restées JSON valide ; la carte `#map` est **visible** dans Chromium et ses ressources Leaflet sont présentes. Galeries WordPress inchangées en nombre de photos.
- Gutenberg, compositeur d'administration et **Classic Editor officiel 1.7.0** : tests navigateur réussis ; le dialogue Classic génère un shortcode Justified lié au bon album.
- Captures et observations de test conservées par l'artefact `piwigo-3.1.0-disposable-browser-proof`.

## Correctif spécifique au laboratoire

Lors de la première activation OpenStreetMap en conteneur, la méthode upstream `maintain.class.php` voulait réécrire `osmmap.php` mais l'utilisateur PHP web n'avait pas les droits. Le banc crée **uniquement ce fichier** avec écriture autorisée dans le conteneur jetable, sans altérer le plugin Piwigo amont ni ouvrir les permissions du répertoire racine. Le comportement des droits d'une installation réelle peut différer et doit être contrôlé séparément. Cela **ne constitue pas une correction du plugin OpenStreetMap amont**.

## Vérifications ajoutées après les précédentes CI vertes

- Clic navigateur réel sur la galerie historique : la lightbox doit s'ouvrir sans navigation Piwigo.
- Clics navigateur réels `piwigo_album` et `piwigo_photo` : navigation Piwigo HTTP 200, sans interception par la lightbox.
- Ces nouveaux contrôles exigent une **nouvelle CI du HEAD final** ; ne pas réutiliser un PASS d'une ancienne révision pour les déclarer validés.

## Limites et décision

- Le laboratoire confirme la compatibilité avec le couple testé **Piwigo 16.4.0 + plugin OpenStreetMap épinglé**, pas avec toutes les versions ni la configuration de l'utilisatrice concernée par [l'issue #97](https://github.com/fr4nck/WP-Piwigo-Display/issues/97). L'issue reste ouverte.
- Les captures automatisées ne remplacent pas une relecture visuelle approfondie sur les thèmes et navigateurs réellement utilisés.
- Aucun import de polices utilisateur WOFF/WOFF2 n'est activé ; ce chantier est séparé.
- Les tests jetables ne reprennent pas les données de production et ne démontrent pas une migration complète des contenus PMSL.
- WordPress.org : slug candidat `display4piwigo` **non attribué**, pas de publication ni soumission.

**Verdict provisoire : GitHub — GO SOUS CONDITIONS d'une CI verte sur le HEAD final et de l'accord explicite de publication ; WordPress.org — NO GO avant approbation et qualification propres.** Aucun merge ni tag ni release autorisés automatiquement.
