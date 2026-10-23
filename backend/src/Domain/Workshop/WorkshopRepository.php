<?php

declare(strict_types=1);

namespace App\Domain\Workshop;

/** Writes take `workshops` column values (snake_case, legacy schema: `public` is the audience). */
interface WorkshopRepository
{
    /** Any status, including withdrawn. */
    public function find(int $id): ?Workshop;

    /** @param array<string, mixed> $fields */
    public function create(array $fields): Workshop;

    /** @param array<string, mixed> $fields */
    public function update(int $id, array $fields): Workshop;

    /** @return list<Workshop> The attendee's active and hidden workshops. */
    public function listByAttendee(int $attendeeId): array;

    /** @return list<Workshop> Active workshops in random order (public listing). */
    public function listActiveShuffled(): array;

    /** @return list<Workshop> Every non-withdrawn workshop, by name. */
    public function listNotWithdrawn(): array;

    /** @return list<Workshop> Part-2 workshops that continue `$id`. */
    public function continuationsOf(int $id): array;

    /**
     * Locks the rows until the end of the current transaction (ascending id, so two
     * enrolments never wait on each other in opposite orders) and returns them fresh.
     *
     * @param list<int> $ids
     * @return array<int, Workshop> keyed by id
     */
    public function lockForUpdate(array $ids): array;

    /** Adds `$delta` (negative to take) to the remaining capacity. */
    public function adjustTickets(int $id, int $delta): void;
}
