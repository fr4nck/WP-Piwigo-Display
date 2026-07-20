from dataclasses import FrozenInstanceError
from datetime import datetime, timedelta
from uuid import UUID, uuid4

import pytest

from domain.employees import Employee
from domain.missions import (
    Mission,
    MissionOccurrence,
    MissionOccurrenceAssignment,
    MissionOccurrenceAssignmentStatus,
)


def make_employee() -> Employee:
    return Employee(name="Ada")


def make_occurrence(starts_at: datetime | None = None) -> MissionOccurrence:
    start = starts_at or datetime(2026, 7, 20, 14, 0)
    return MissionOccurrence(
        mission=Mission(name="Séance multisports"),
        starts_at=start,
        ends_at=start + timedelta(hours=2),
    )


def make_assignment(**overrides) -> MissionOccurrenceAssignment:
    values = {
        "employee": make_employee(),
        "occurrence": make_occurrence(),
        "status": MissionOccurrenceAssignmentStatus.PLANNED,
    }
    values.update(overrides)
    return MissionOccurrenceAssignment(**values)


def test_create_minimal_valid_assignment() -> None:
    assignment = make_assignment()

    assert assignment.employee.name == "Ada"
    assert assignment.occurrence.mission.name == "Séance multisports"
    assert assignment.status is MissionOccurrenceAssignmentStatus.PLANNED
    assert assignment.observations is None
    assert assignment.active is True


def test_create_valid_assignment_with_all_data() -> None:
    identifier = uuid4()
    employee = make_employee()
    occurrence = make_occurrence()

    assignment = MissionOccurrenceAssignment(
        id=identifier,
        employee=employee,
        occurrence=occurrence,
        status=MissionOccurrenceAssignmentStatus.CONFIRMED,
        observations=" Confirmée par téléphone ",
        active=False,
    )

    assert assignment.id == identifier
    assert assignment.employee is employee
    assert assignment.occurrence is occurrence
    assert assignment.status is MissionOccurrenceAssignmentStatus.CONFIRMED
    assert assignment.observations == "Confirmée par téléphone"
    assert assignment.active is False


def test_id_is_generated_automatically() -> None:
    assert isinstance(make_assignment().id, UUID)


def test_explicit_uuid_is_accepted() -> None:
    identifier = uuid4()

    assert make_assignment(id=identifier).id == identifier


def test_invalid_uuid_is_rejected() -> None:
    with pytest.raises(TypeError, match="id must be a UUID"):
        make_assignment(id="not-a-uuid")


@pytest.mark.parametrize("employee", [None, "Ada"])
def test_employee_is_required_and_must_be_employee(employee) -> None:
    with pytest.raises(TypeError, match="employee must be an Employee"):
        make_assignment(employee=employee)


@pytest.mark.parametrize("occurrence", [None, "occurrence"])
def test_occurrence_is_required_and_must_be_mission_occurrence(occurrence) -> None:
    with pytest.raises(TypeError, match="occurrence must be a MissionOccurrence"):
        make_assignment(occurrence=occurrence)


@pytest.mark.parametrize("status", [None, "planned"])
def test_status_is_required_and_must_be_status(status) -> None:
    with pytest.raises(TypeError, match="status must be a MissionOccurrenceAssignmentStatus"):
        make_assignment(status=status)


@pytest.mark.parametrize("active", [None, 1, "true"])
def test_active_must_be_strict_bool(active) -> None:
    with pytest.raises(TypeError, match="active must be a bool"):
        make_assignment(active=active)


def test_observations_are_normalized() -> None:
    assert make_assignment(observations="  note utile  ").observations == "note utile"


@pytest.mark.parametrize("observations", ["", "   "])
def test_empty_observations_are_rejected(observations) -> None:
    with pytest.raises(ValueError, match="observations must not be empty"):
        make_assignment(observations=observations)


def test_assignment_is_immutable() -> None:
    assignment = make_assignment()

    with pytest.raises(FrozenInstanceError):
        assignment.active = False


@pytest.mark.parametrize(
    ("status", "method_name"),
    [
        (MissionOccurrenceAssignmentStatus.PLANNED, "is_planned"),
        (MissionOccurrenceAssignmentStatus.CONFIRMED, "is_confirmed"),
        (MissionOccurrenceAssignmentStatus.CANCELLED, "is_cancelled"),
        (MissionOccurrenceAssignmentStatus.COMPLETED, "is_completed"),
        (MissionOccurrenceAssignmentStatus.ABSENT, "is_absent"),
    ],
)
def test_status_predicates_read_declared_status(status, method_name) -> None:
    assignment = make_assignment(status=status)

    assert getattr(assignment, method_name)() is True


def test_only_one_status_predicate_is_true_for_each_status() -> None:
    predicate_names = [
        "is_planned",
        "is_confirmed",
        "is_cancelled",
        "is_completed",
        "is_absent",
    ]

    for status in MissionOccurrenceAssignmentStatus:
        assignment = make_assignment(status=status)
        results = [getattr(assignment, name)() for name in predicate_names]
        assert results.count(True) == 1


@pytest.mark.parametrize("active", [True, False])
def test_is_active_reads_declared_active_value(active) -> None:
    assert make_assignment(active=active).is_active() is active


def test_status_is_not_automatically_computed_from_occurrence_dates() -> None:
    past_start = datetime(2020, 1, 1, 8, 0)
    assignment = make_assignment(
        occurrence=make_occurrence(starts_at=past_start),
        status=MissionOccurrenceAssignmentStatus.PLANNED,
    )

    assert assignment.is_planned() is True
    assert assignment.is_completed() is False
