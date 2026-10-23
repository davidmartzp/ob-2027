<?php

declare(strict_types=1);

namespace App\Domain\Attendee;

use DateTimeImmutable;

/**
 * When an attendee may pick workshops (inherited from angular-ob's verifyAttendeeCode):
 * only on the congress days matching `days` (S Saturday, D Sunday, T both), between the
 * start and end time, by groups — group = ceil(number / size), entering
 * (group − 1) × minutes after the start. Messages are user-facing (Spanish).
 */
final readonly class RegistrationTurns
{
    public function __construct(private TurnSettings $settings)
    {
    }

    public function decide(Attendee $attendee, DateTimeImmutable $now): TurnDecision
    {
        if ($this->settings->bypass) {
            return TurnDecision::open('Modo ensayo: turnos desactivados.');
        }

        $offset = (max(1, (int) ceil($attendee->turnNumber() / $this->settings->groupSize)) - 1) * $this->settings->minutesPerGroup;
        $today = $now->format('Y-m-d');
        $days = $this->allowedDays($attendee->days);

        if (!in_array($today, $days, true)) {
            $next = $this->firstAfter($days, $today);

            return TurnDecision::closed($next === null
                ? 'Las inscripciones a talleres del congreso ya terminaron.'
                : "No puedes inscribirte hoy. Tu inscripción inicia a las {$this->groupStart($next, $offset)->format('H:i')} el {$this->dayName($next)}.");
        }

        $dayStart = $this->at($today, $this->settings->startTime);
        $dayEnd = $this->at($today, $this->settings->endTime);
        $groupStart = $this->groupStart($today, $offset);

        if ($now < $dayStart) {
            return TurnDecision::closed("Las inscripciones inician a las {$this->settings->startTime}. Tu grupo puede inscribirse a partir de las {$groupStart->format('H:i')}.");
        }
        if ($now > $dayEnd) {
            $next = $this->firstAfter($days, $today);

            return TurnDecision::closed($next === null
                ? "Las inscripciones ya han finalizado hoy a las {$this->settings->endTime} y no hay más días del congreso."
                : "Las inscripciones ya han finalizado hoy a las {$this->settings->endTime}. Tu próxima inscripción inicia a las {$this->groupStart($next, $offset)->format('H:i')} el {$this->dayName($next)}.");
        }
        if ($groupStart > $dayEnd) {
            return TurnDecision::closed('El horario asignado para tu grupo ya ha concluido las inscripciones.');
        }
        if ($now < $groupStart) {
            return TurnDecision::closed("Aún no puedes inscribirte. Tu grupo inicia a partir de las {$groupStart->format('H:i')}.");
        }

        return TurnDecision::open("Código y correo válidos. Tu inscripción inició a partir de las {$groupStart->format('H:i')}.");
    }

    /** @return list<string> Congress dates (YYYY-MM-DD) the attendee may use. */
    private function allowedDays(?string $days): array
    {
        return match ($days) {
            'S' => [$this->settings->saturday],
            'D' => [$this->settings->sunday],
            default => [$this->settings->saturday, $this->settings->sunday],
        };
    }

    /** @param list<string> $days */
    private function firstAfter(array $days, string $today): ?string
    {
        foreach ($days as $day) {
            if ($day > $today) {
                return $day;
            }
        }

        return null;
    }

    private function groupStart(string $date, int $offsetMinutes): DateTimeImmutable
    {
        return $this->at($date, $this->settings->startTime)->modify("+{$offsetMinutes} minutes");
    }

    private function at(string $date, string $time): DateTimeImmutable
    {
        return new DateTimeImmutable("{$date} {$time}", new \DateTimeZone('America/Bogota'));
    }

    private function dayName(string $date): string
    {
        return $date === $this->settings->sunday ? 'domingo' : 'sábado';
    }
}
