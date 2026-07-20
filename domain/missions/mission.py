"""Définition réutilisable d'une mission Teamworks."""

from __future__ import annotations

from dataclasses import dataclass, field
from uuid import UUID, uuid4


@dataclass(frozen=True, slots=True)
class Mission:
    """Représente une définition métier de mission."""

    name: str
    id: UUID = field(default_factory=uuid4)

    def __post_init__(self) -> None:
        if not isinstance(self.id, UUID):
            raise TypeError("id must be a UUID")
        if not isinstance(self.name, str):
            raise TypeError("name must be a str")
        normalized_name = self.name.strip()
        if not normalized_name:
            raise ValueError("name must not be empty")
        object.__setattr__(self, "name", normalized_name)
