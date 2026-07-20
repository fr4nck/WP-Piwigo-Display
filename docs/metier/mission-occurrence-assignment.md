# MissionOccurrenceAssignment

`MissionOccurrenceAssignment` représente uniquement l'affectation déclarée d'un salarié (`Employee`) à une occurrence précise de mission (`MissionOccurrence`).

## Données portées

- `id` : identifiant `UUID` de l'affectation ;
- `employee` : salarié affecté ;
- `occurrence` : créneau de mission concerné ;
- `status` : statut déclaré de l'affectation ;
- `observations` : note optionnelle normalisée ;
- `active` : indicateur booléen déclaré.

## Statuts

Les statuts possibles sont :

- `PLANNED` ;
- `CONFIRMED` ;
- `CANCELLED` ;
- `COMPLETED` ;
- `ABSENT`.

Les prédicats de statut lisent uniquement la valeur déclarée de `status`.
`is_active()` lit uniquement la valeur déclarée de `active`.

## Limites explicites

L'objet ne calcule pas son statut à partir des dates de l'occurrence et ne vérifie pas automatiquement la disponibilité, les qualifications, le contrat, le temps de travail, la présence réelle, la rémunération, les remplacements, les notifications ou la persistance.
