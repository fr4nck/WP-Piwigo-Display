"""Statuts déclarés d'affectation à une occurrence de mission."""

from __future__ import annotations

from enum import Enum


class MissionOccurrenceAssignmentStatus(Enum):
    """Statut déclaré d'une affectation à une occurrence de mission."""

    PLANNED = "planned"
    CONFIRMED = "confirmed"
    CANCELLED = "cancelled"
    COMPLETED = "completed"
    ABSENT = "absent"
