# QUAL-310 — Suivi de qualification de 3.1.0

## Preuves antérieures vérifiées le 13 novembre 2026

- Release stable 3.0.1 : tag `v3.0.1`, commit `d25bd97dc4f975d940bc4ce6ca363d04a81c416d`, ZIP SHA-256 `20faab8e598299a7db5d3ace1622f3b8f7c464c27b0ce16778ab93e7c1e59ed4`.
- PR #139 à #145 ouvertes en brouillon, strictement empilées depuis la 3.0.1. Sommet initial `0d688140f47d85e7b01ad211f1abe48c33d51a8e`, 15 commits en avance et aucun en retard sur le socle 3.0.1.
- [CI du sommet #43417221568](https://github.com/fr4nck/WP-Piwigo-Display/actions/runs/43417221568) : 6 jobs réussis et publication ignorée. **Les artefacts de cette exécution portent encore le numéro 3.0.1 et ne sont pas des candidats 3.1.0.**
- Fonctions : navigation au clic, Justified, Collage, bibliothèque 18 formes, masques SVG filtrés, texte-photo, Bebas Neue et Bungee locaux. Aucun import personnel WOFF/WOFF2 ouvert.

## Conditions restant à démontrer pour cette branche

- [ ] Tous les contrôles de version, les tests PHP et JavaScript, standards et Plugin Check réussis sur le commit final.
- [ ] Les ZIP GitHub 3.1.0 et WordPress.org candidate distincts ont été vérifiés avec empreintes et provenance.
- [ ] Migration WordPress réelle depuis la **3.0.1 publiée**, conservation des options et contenus, rollback et nouveau passage 3.1.
- [ ] Parité éditeurs/rendus démontrée sur navigateur et WordPress/Piwigo jetables.
- [ ] Absence de régression d'accès aux albums privés, alias `#n`, permissions, sécurité SVG et coexistence.
- [ ] Compatibilité de l'extension Piwigo OpenStreetMap reproduite/testée sur installation jetable ; issue #97 toujours ouverte.
- [ ] Slug WordPress.org approuvé et canal de mise à jour qualifié pour cette distribution uniquement.

**Verdict provisoire : NO GO publication.** La décision GitHub est distincte de la décision WordPress.org. Aucune release ou soumission automatique.
