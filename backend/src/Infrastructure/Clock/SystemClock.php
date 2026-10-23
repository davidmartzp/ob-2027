<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/** Current time in Bogotá, the zone of the congress and of the legacy data. */
final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));
    }
}
