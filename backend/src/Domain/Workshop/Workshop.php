<?php

declare(strict_types=1);

namespace App\Domain\Workshop;

/**
 * A workshop proposed by an attendee (the instructor). `tickets` is the remaining
 * capacity; `part`/`relation` link a two-part workshop (part 2 points to its part 1).
 */
final readonly class Workshop
{
    public function __construct(
        public int $id,
        public ?int $attendeeId,
        public string $name,
        public ?string $audience,
        public ?string $level,
        public ?int $duration,
        public ?string $observations,
        public ?string $image,
        public WorkshopStatus $status,
        public int $tickets = 20,
        public int $part = 1,
        public ?int $relation = null,
    ) {
    }

    public function isWithdrawn(): bool
    {
        return $this->status === WorkshopStatus::Withdrawn;
    }
}
