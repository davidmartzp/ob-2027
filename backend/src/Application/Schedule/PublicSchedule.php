<?php

declare(strict_types=1);

namespace App\Application\Schedule;

use App\Domain\Schedule\EnrolmentWindow;
use App\Domain\Schedule\Event;
use App\Domain\Schedule\ScheduleRepository;
use Psr\Clock\ClockInterface;

/** What the public site shows of the programme. */
final readonly class PublicSchedule
{
    public function __construct(
        private ScheduleRepository $schedule,
        private EnrolmentWindow $window,
        private ScheduleView $view,
        private ClockInterface $clock,
    ) {
    }

    /** @return list<array<string, mixed>> GET /events/active: today's workshops, only while the window is open. */
    public function activeEvents(): array
    {
        $days = $this->window->openDays($this->clock->now());
        $events = array_filter(
            $this->schedule->events(),
            static fn (Event $e): bool => $e->workshopId !== null && in_array($e->day, $days, true),
        );
        usort($events, static fn (Event $a, Event $b): int => [$a->day->value === 'Sunday', $a->startTime, $a->roomId] <=> [$b->day->value === 'Sunday', $b->startTime, $b->roomId]);

        return $this->view->activeEvents($events);
    }

    /** @return list<array<string, mixed>> GET /events/schedule: every scheduled active workshop, any time. */
    public function schedule(): array
    {
        return $this->view->schedule(array_values(array_filter($this->schedule->events(), static fn (Event $e): bool => $e->workshopId !== null)));
    }
}
