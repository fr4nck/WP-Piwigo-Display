# AGENTS.md — Gouvernance de Piwigo Display

Ce fichier adapte les règles éprouvées de PMSL-Arch au plugin WordPress Piwigo Display. Il s'applique aux agents, audits, contre-audits, corrections et livraisons. Les restrictions explicites d'une mission prévalent sur les autorisations générales ci-dessous.

## Ordre des priorités

1. Exactitude fonctionnelle et confidentialité des albums Piwigo.
2. Sécurité, stabilité et absence de régression.
3. Lisibilité et simplicité.
4. Compatibilité WordPress, PHP et Piwigo.
5. Performances, sobriété et maintenance.

Une amélioration technique ne justifie jamais une fuite d'album privé, une régression ni une architecture inutilement complexe.

## Méthode et autonomie — héritage PMSL-Arch

Avant toute intervention : identifier le livrable demandé, le dépôt et le SHA réels ; lire la documentation officielle pertinente et le code effectivement exécuté (AGT-014 / RTFM) ; distinguer faits documentés, observations, hypothèses et inconnues. Une source primaire accessible non étudiée interdit un correctif spéculatif. Ne pas étendre indéfiniment un « go » : chaque lot a un périmètre, des critères de sortie et un bilan.

Privilégier les mécanismes natifs WordPress et Piwigo, les adaptations locales, les changements réversibles et les tests discriminants. Ne pas créer de moteur parallèle sans démontrer que l'existant est insuffisant.

### AGT-012 — Prompts autonomes

Chaque instruction destinée à un autre agent doit être complète et directement collable en une seule fois : ordre d'exécution, dépôt, branche/SHA, contexte, droits, interdictions, prévol, tests, preuves et livrable. Ne jamais exiger que l'utilisateur recompose plusieurs messages. Lors d'une correction, redonner le prompt complet.

### AGT-013 — Commandes sans retouche

Les commandes Bash/sh/PowerShell remises à l'utilisateur sont des blocs uniques directement exécutables, adaptés au shell et à la machine cible réellement identifiés, sans retouche manuelle ni téléchargement de script imposé. Inclure prévol, répertoire canonique, état Git, limites d'écriture et de réseau, exécution, vérifications, bilan et vrai code retour. Installer la finalisation avant les opérations susceptibles d'échouer ; copier automatiquement le bilan expurgé au presse-papiers lorsque disponible, y compris en cas d'échec, sans masquer le code retour initial. Ne jamais afficher/copier de secrets. Une commande fournie n'est pas une preuve d'exécution.

### AGT-014 — RTFM

Consulter d'abord les contrats et limitations des API WordPress/Piwigo, puis vérifier le code, les versions et les chemins d'exécution réels. Consigner les références, le SHA, les observations et les inconnues. Si les sources sont indisponibles, déclarer la limite et ne pas inventer une validation.

## Architecture et données — DATA-001 / Existing Data First

Inventorier → identifier la source de vérité → réutiliser ou adapter → étendre ou migrer si justifié → créer uniquement si nécessaire. Pour ce plugin, examiner d'abord les options WordPress, transients, caches, métadonnées et données Piwigo existantes. Ne pas créer une deuxième source de vérité pour les albums, permissions ou identifiants.

Toute nouvelle persistance doit justifier pourquoi RÉUTILISER, ADAPTER, ÉTENDRE ou MIGRER ne suffit pas ; documenter reconstruction, invalidation, expiration et cohérence des caches. Absence d'inventaire démontrable = NO-GO architectural pour un changement de modèle de données.

## Doctrine de ratissage systématique

Un défaut confirmé déclenche la recherche de sa **famille de défauts**, pas seulement la correction d'une ligne. Rechercher les chemins analogues (frontend, shortcode, Classic Editor, Gutenberg, AJAX, cache, API, SVG, migration), distinguer « détecté » de « confirmé », éliminer les faux positifs et créer si possible un test de non-régression. Ne pas modifier le produit pour satisfaire un test manifestement incorrect.

Pour chaque correction : reproduction RED avant modification → cause racine → correctif minimal → GREEN → tests de régression → examen du diff et des occurrences analogues. Documenter les exceptions quand RED est techniquement impossible.

## Qualification et niveaux de preuve

Ne jamais confondre :
1. code modifié ;
2. contrôles statiques réussis ;
3. tests automatisés réussis ;
4. artefact ZIP réellement construit ;
5. ZIP installé et exécuté dans les versions cibles ;
6. parcours critiques validés ;
7. recette visuelle/fonctionnelle sur données représentatives ;
8. décision explicite de qualification ou publication.

Une CI verte prouve seulement ce qu'elle teste. Chaque résultat doit être rattaché au **SHA exact**. Une CI d'un ancêtre ne qualifie pas automatiquement son descendant. Distinguer défaut produit, défaut du test et problème d'environnement.

## Sécurité WordPress / Piwigo

- Un nonce n'est pas une autorisation : vérifier capabilities, nonce quand pertinent, validation serveur et échappement adapté au contexte.
- Les albums privés, secrets et permissions du compte de service ne doivent jamais être exposés par un alias, un cache, une erreur ou une API publique.
- Respecter la séparation compte de service / lecture publique ; appliquer le moindre privilège.
- Borner les appels HTTP, réponses, JSON, catégories, images, pagination, récursivité, temps, mémoire et caches ; tester les chemins d'échec et les réponses malformées.
- Contrôler les hôtes et URL, les redirections, le type des données, les SVG et l'encodage ; interdire l'exécution de contenu distant ou de shortcodes issus de métadonnées non fiables.
- Ne jamais journaliser, committer ni recopier de jetons, mots de passe ou données privées.

## Organisation des audits, corrections et suivi

**Audit indépendant :** examiner le code et établir ses propres constats avant de lire les revues historiques ; fournir reproductions, limites et preuves ; aucune écriture produit.

**Contre-audit :** contester les hypothèses, reproduire indépendamment, rechercher faux positifs, faux négatifs et régressions ; aucune écriture produit.

**Corrections :** intervenir uniquement dans le périmètre et la branche autorisés, avec RED/GREEN, commits thématiques et SHA transmis ; aucune fusion implicite.

**Coordination :** maintenir un registre unique des constats, divergences, SHAs, preuves, CI et prochaines actions ; arbitrer les désaccords sur la base de tests discriminants. Les conversations ne se synchronisent pas automatiquement : transmettre explicitement les résultats par un support partagé vérifiable.

### Campagne RC3 — références de qualification

- Dépôt : `fr4nck/WP-Piwigo-Display`.
- Base figée : `23f458fe7a3699361326fc98bf40462bb0f0bb15`, PR #162.
- Historique : issue #151 et PR #161.
- Registre : S-01 à S-16 et F-01 à F-04.

La base figée ne change pas parce qu'un nouveau commit est poussé. Chaque constat indique : identifiant, gravité, description, reproduction, statut (détecté / confirmé / corrigé / contre-vérifié), SHA, preuve, CI et prochaine action. Ne jamais annoncer « résolu » sur la seule foi d'un correctif déclaré.

## Git — trois branches maximum

**Invariant de gouvernance : au plus trois branches pérennes pour cette application.** Le dépôt contient actuellement des branches historiques supplémentaires : c'est une dette à résorber progressivement, **pas** une autorisation d'en créer davantage. Par défaut, conserver `main` (stable), une branche d'intégration/développement et, seulement si nécessaire, une branche de qualification/correction ; choisir leurs noms après inventaire réel.

- Avant toute action, vérifier dépôt, branche, HEAD, arbre de travail, PR dépendantes et statut CI.
- Préférer SHA figés et copies locales isolées aux branches distantes jetables.
- Avant suppression d'une branche : vérifier commits uniques, PR ouvertes, références, sauvegarde du travail et intégration effective ; consigner la preuve et obtenir l'autorisation de suppression.
- Ne jamais supprimer, fusionner, réécrire l'historique ou forcer un push par simple souci de rangement.
- Les missions RC3 en cours interdisent merge, modification de `main`, tag, release, remplacement des ZIP et déploiement sans autorisation explicite.
- Une mise à jour documentaire peut être committée sur une **branche existante autorisée**, sans changer le SHA figé de qualification ; elle ne justifie pas une quatrième branche.

## Communication et livraison

Échanges, rapports, commits et documentation en français, sauf contrainte amont. Donner des faits, pas des annonces promotionnelles. Chaque bilan précise : objectif, dépôt, branche, SHA avant/après, fichiers touchés, tests exécutés avec PASS/FAIL, CI exacte, limites, risques et prochaine action. Ne jamais présenter un projet, une commande ou une hypothèse comme un résultat exécuté.

## Documentation

Conserver README, CHANGELOG, instructions utilisateur, contrats techniques, tests et packaging cohérents avec le comportement réellement qualifié. Les règles transversales pérennes viennent de PMSL-Arch ; les choix propres à Piwigo Display restent documentés ici.
