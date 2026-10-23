<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

interface AttendeeRepository
{
    /** A non-deleted attendee. */
    public function findActive(int $id): ?Attendee;

    /**
     * Non-deleted attendees registered with that email (case-insensitive). Several people
     * can share an email (families), so this returns a list ordered by id.
     *
     * @return list<Attendee>
     */
    public function findActiveByEmail(string $email): array;
}
