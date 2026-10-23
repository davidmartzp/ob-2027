<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

/** Congress days and the workshop-selection turns (formerly hard-coded for 2024). */
final readonly class TurnSettings
{
    public function __construct(
        /** YYYY-MM-DD of the congress Saturday and Sunday. */
        public string $saturday,
        public string $sunday,
        /** HH:MM, Bogotá time. */
        public string $startTime = '08:00',
        public string $endTime = '19:00',
        public int $groupSize = 25,
        public int $minutesPerGroup = 10,
        /** Rehearsal mode: turns always open. Never true in production (built in container.php). */
        public bool $bypass = false,
    ) {
    }
}
