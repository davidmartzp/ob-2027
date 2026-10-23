<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

/** Rooms, activities, events and the attendee ↔ event enrolments (`events_attendees`). */
interface ScheduleRepository
{
    /** @return list<Room> */
    public function rooms(): array;

    public function roomExists(int $id): bool;

    /** @return list<Activity> */
    public function activities(): array;

    public function activityExists(int $id): bool;

    public function findEvent(int $id): ?Event;

    /** @return list<Event> Ordered by day, start time and room. */
    public function events(): array;

    /** @param list<int> $ids @return list<Event> */
    public function findEvents(array $ids): array;

    /** @param list<int> $workshopIds @return array<int, Event> First event of each workshop, keyed by workshop id. */
    public function eventsOfWorkshops(array $workshopIds): array;

    public function overlappingInRoom(int $roomId, Day $day, string $start, string $end): ?Event;

    public function createEvent(int $roomId, Day $day, string $start, string $end, ?int $workshopId, ?int $activityId): Event;

    /** Deletes the event and its enrolments. @return int How many enrolments were removed. */
    public function deleteEvent(int $id): int;

    /** @return list<int> */
    public function eventIdsOf(int $attendeeId): array;

    /** @return list<int> Attendee ids enrolled in the event. */
    public function attendeeIdsOf(int $eventId): array;

    public function isEnrolled(int $attendeeId, int $eventId): bool;

    public function enrol(int $attendeeId, int $eventId): void;

    /** @return bool Whether there was an enrolment to remove. */
    public function unenrol(int $attendeeId, int $eventId): bool;
}
