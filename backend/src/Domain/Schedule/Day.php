<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

/** Congress day, stored as in the legacy `events.day` column. */
enum Day: string
{
    case Saturday = 'Saturday';
    case Sunday = 'Sunday';

    /** Accepts any letter case ("saturday", "SATURDAY"). */
    public static function parse(string $value): ?self
    {
        return self::tryFrom(ucfirst(strtolower(trim($value))));
    }
}
