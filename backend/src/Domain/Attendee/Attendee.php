<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/**
 * Congress attendee (formerly `Assistant`). Only the fields used so far; the full
 * registration data is added with the attendees use cases (task group 5).
 */
final readonly class Attendee
{
    public function __construct(
        public int $id,
        public string $name,
        public string $lastname,
        public string $email,
        public int $age,
        public AttendeeStatus $status,
        public ?int $code = null,
        public ?string $specialCode = null,
    ) {
    }

    public function fullName(): string
    {
        return trim($this->name . ' ' . $this->lastname);
    }
}
