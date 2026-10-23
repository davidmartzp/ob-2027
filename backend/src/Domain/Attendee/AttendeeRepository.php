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

    /**
     * Attendees in the group, ordered by id; with `$ids`, only those (still within the group).
     *
     * @param list<int>|null $ids
     * @return list<Attendee>
     */
    public function findRecipients(RecipientGroup $group, ?array $ids = null): array;
}
