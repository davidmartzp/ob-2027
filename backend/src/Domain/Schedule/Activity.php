<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

/** A non-workshop slot of the programme (lunch, group photo, closing…). */
final readonly class Activity
{
    public function __construct(public int $id, public string $name, public ?string $description, public int $duration)
    {
    }
}
