"""Occurrence datée et horaire d'une mission Teamworks."""

from __future__ import annotations

from dataclasses import dataclass, field
from datetime import datetime
from uuid import UUID, uuid4

from .mission import Mission


@dataclass(frozen=True, slots=True)
class MissionOccurrence:
    """Représente un créneau daté et horaire d'une mission."""

    mission: Mission
    starts_at: datetime
    ends_at: datetime
    id: UUID = field(default_factory=uuid4)

    def __post_init__(self) -> None:
        if not isinstance(self.id, UUID):
            raise TypeError("id must be a UUID")
        if not isinstance(self.mission, Mission):
            raise TypeError("mission must be a Mission")
        if not isinstance(self.starts_at, datetime):
            raise TypeError("starts_at must be a datetime")
        if not isinstance(self.ends_at, datetime):
            raise TypeError("ends_at must be a datetime")
        if self.ends_at <= self.starts_at:
            raise ValueError("ends_at must be after starts_at")
