<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

/** Single-use sign-in links sent by email to attendees. Only the hash of the key is stored. */
interface AccessLinkRepository
{
    public function create(string $hash, int $attendeeId, DateTimeImmutable $expiresAt, DateTimeImmutable $now): void;

    /**
     * Marks the link as used and returns its attendee id, or null if it does not exist,
     * expired or was already used. Atomic: two concurrent calls cannot both succeed.
     */
    public function consume(string $hash, DateTimeImmutable $now): ?int;
}
