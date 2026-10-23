<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/** Congress attendee (formerly `Assistant`), with every field of the legacy `assistants` table. */
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
        public ?string $idname = null,
        public ?string $origamigroup = null,
        public ?string $info = null,
        public bool $cards = false,
        public ?int $cardsgroup = null,
        public bool $staff = false,
        public bool $goToChiva = false,
        public bool $printCertificate = false,
        public int $chivaCompanions = 0,
        public string $payAmount = '0',
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    public function fullName(): string
    {
        return trim($this->name . ' ' . $this->lastname);
    }

    /** First name and first surname, as shown in public lists. */
    public function shortName(): string
    {
        return trim($this->name . ' ' . strtok($this->lastname, ' '));
    }

    /** The number shown to the attendee: the assigned code or, failing that, the special code. */
    public function displayCode(): ?string
    {
        return $this->code !== null ? (string) $this->code : $this->specialCode;
    }

    /** The number that decides the turn group: the code, or the digits of the special code (`A12` → 12). */
    public function turnNumber(): int
    {
        return $this->code ?? (int) preg_replace('/\D/', '', (string) $this->specialCode);
    }
}
