"""Affectation déclarée d'un salarié à une occurrence de mission."""

from __future__ import annotations

from dataclasses import dataclass, field
from uuid import UUID, uuid4

from domain.employees import Employee

from .mission_occurrence import MissionOccurrence
from .mission_occurrence_assignment_status import MissionOccurrenceAssignmentStatus


@dataclass(frozen=True, slots=True)
class MissionOccurrenceAssignment:
    """Représente l'affectation concrète d'un salarié à une occurrence."""

    employee: Employee
    occurrence: MissionOccurrence
    status: MissionOccurrenceAssignmentStatus
    observations: str | None = None
    active: bool = True
    id: UUID = field(default_factory=uuid4)

    def __post_init__(self) -> None:
        if not isinstance(self.id, UUID):
            raise TypeError("id must be a UUID")
        if not isinstance(self.employee, Employee):
            raise TypeError("employee must be an Employee")
        if not isinstance(self.occurrence, MissionOccurrence):
            raise TypeError("occurrence must be a MissionOccurrence")
        if not isinstance(self.status, MissionOccurrenceAssignmentStatus):
            raise TypeError("status must be a MissionOccurrenceAssignmentStatus")
        if not isinstance(self.active, bool):
            raise TypeError("active must be a bool")
        if self.observations is not None:
            if not isinstance(self.observations, str):
                raise TypeError("observations must be a str or None")
            normalized_observations = self.observations.strip()
            if not normalized_observations:
                raise ValueError("observations must not be empty")
            object.__setattr__(self, "observations", normalized_observations)

    def is_planned(self) -> bool:
        """Indique si le statut déclaré est planifié."""
        return self.status is MissionOccurrenceAssignmentStatus.PLANNED

    def is_confirmed(self) -> bool:
        """Indique si le statut déclaré est confirmé."""
        return self.status is MissionOccurrenceAssignmentStatus.CONFIRMED

    def is_cancelled(self) -> bool:
        """Indique si le statut déclaré est annulé."""
        return self.status is MissionOccurrenceAssignmentStatus.CANCELLED

    def is_completed(self) -> bool:
        """Indique si le statut déclaré est terminé."""
        return self.status is MissionOccurrenceAssignmentStatus.COMPLETED

    def is_absent(self) -> bool:
        """Indique si le statut déclaré est absent."""
        return self.status is MissionOccurrenceAssignmentStatus.ABSENT

    def is_active(self) -> bool:
        """Retourne la valeur active déclarée."""
        return self.active
