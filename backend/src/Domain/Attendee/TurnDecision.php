<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

final readonly class TurnDecision
{
    private function __construct(public bool $open, public string $message)
    {
    }

    public static function open(string $message): self
    {
        return new self(true, $message);
    }

    public static function closed(string $message): self
    {
        return new self(false, $message);
    }
}
