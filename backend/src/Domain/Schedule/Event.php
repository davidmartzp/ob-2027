<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

/** A workshop or an activity placed in a room, on a day, in a time slot (HH:MM:SS). */
final readonly class Event
{
    public function __construct(
        public int $id,
        public int $roomId,
        public Day $day,
        public string $startTime,
        public string $endTime,
        public ?int $workshopId,
        public ?int $activityId,
    ) {
    }

    public function overlaps(self $other): bool
    {
        return $this->day === $other->day
            && $this->startTime < $other->endTime
            && $this->endTime > $other->startTime;
    }
}
