<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/** Test clock: starts at the real time and only moves when told to. */
final class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $moment = 'now')
    {
        $this->set($moment);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function set(string $moment): void
    {
        $this->now = new DateTimeImmutable($moment, new DateTimeZone('America/Bogota'));
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}
