<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Domain\Attendee\TurnSettings;
use DateTimeImmutable;

/**
 * When the list of workshops to pick from is open (legacy activeEvents): on the
 * congress Saturday between the start and end time, and on the Sunday from the start
 * time on; only that day's events are shown. Rehearsal mode opens every day.
 */
final readonly class EnrolmentWindow
{
    public function __construct(private TurnSettings $settings)
    {
    }

    /**
     * @return list<Day> The days whose events can be picked now.
     * @throws EnrolmentClosedException
     */
    public function openDays(DateTimeImmutable $now): array
    {
        if ($this->settings->bypass) {
            return [Day::Saturday, Day::Sunday];
        }

        $today = $now->format('Y-m-d');
        $time = $now->format('H:i');
        $start = $this->settings->startTime;
        $end = $this->settings->endTime;

        if ($today === $this->settings->saturday) {
            if ($time < $start || $time >= $end) {
                throw new EnrolmentClosedException("Los talleres solo están disponibles el sábado entre las {$start} y las {$end}. Actualiza la página a esa hora para ver la lista.");
            }

            return [Day::Saturday];
        }
        if ($today === $this->settings->sunday) {
            if ($time < $start) {
                throw new EnrolmentClosedException("Los talleres del domingo están disponibles a partir de las {$start}. Actualiza la página a esa hora para ver la lista.");
            }

            return [Day::Sunday];
        }

        throw new EnrolmentClosedException(
            $today < $this->settings->saturday
                ? "La inscripción a talleres abre el sábado del congreso a las {$start}."
                : 'La inscripción a talleres del congreso ya terminó.',
        );
    }
}
