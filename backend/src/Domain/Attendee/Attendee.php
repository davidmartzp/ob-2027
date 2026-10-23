<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/**
 * Congress attendee (formerly `Assistant`). The remaining registration fields (cards,
 * certificate, chiva…) are added with the attendees use cases (task group 5).
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
        public ?string $phone = null,
        public ?string $country = null,
        public ?string $city = null,
        public ?string $paymethod = null,
        public ?string $paydate = null,
        public ?string $receipt = null,
        public ?string $companion = null,
        public ?string $days = null,
    ) {
    }

    public function fullName(): string
    {
        return trim($this->name . ' ' . $this->lastname);
    }

    /** The number shown to the attendee: the assigned code or, failing that, the special code. */
    public function displayCode(): ?string
    {
        return $this->code !== null ? (string) $this->code : $this->specialCode;
    }
}
