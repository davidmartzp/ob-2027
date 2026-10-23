<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

final readonly class Room
{
    public function __construct(public int $id, public ?string $name)
    {
    }
}
