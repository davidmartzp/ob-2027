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
}
