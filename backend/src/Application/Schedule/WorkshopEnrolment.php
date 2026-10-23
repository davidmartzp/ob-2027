<?php

declare(strict_types=1);

namespace App\Application\Schedule;

use App\Application\Port\TransactionManager;
use App\Application\Shared\Validator;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Auth\Session;
use App\Domain\Schedule\Event;
use App\Domain\Schedule\ScheduleRepository;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\ValidationException;
use App\Domain\Workshop\Workshop;
use App\Domain\Workshop\WorkshopRepository;

/**
 * An attendee picks or drops workshops (`/attendees/{id}/events`). A two-part workshop
 * is one unit: enrolling in either part enrols in all of them, or in none.
 * Everything happens in one transaction with the workshop rows locked, so the last
 * place cannot be taken twice.
 */
final readonly class WorkshopEnrolment
{
    /** Under this age nobody enters adult workshops (inherited rule). */
    private const int ADULT_AGE = 13;
    private const string ADULT_AUDIENCE = 'A';

    public function __construct(
        private ScheduleRepository $schedule,
        private WorkshopRepository $workshops,
        private AttendeeRepository $attendees,
        private TransactionManager $transactions,
    ) {
    }

    /** @return list<int> */
    public function mine(Session $session, int $attendeeId): array
    {
        $session->requireAttendee($attendeeId);

        return $this->schedule->eventIdsOf($attendeeId);
    }

    /**
     * @param array<string, mixed> $input `{"id_event": 12}`
     * @return array{enrolled: list<int>}
     */
    public function enrol(Session $session, int $attendeeId, array $input): array
    {
        $session->requireAttendee($attendeeId);
        $eventId = Validator::validate($input, ['id_event' => 'required|int:1,100000000'])['id_event'];
        $attendee = $this->attendees->findActive($attendeeId) ?? throw new NotFoundException('No existe ese asistente.');

        return $this->transactions->run(function () use ($attendee, $eventId): array {
            [$events, $workshops] = $this->unit($eventId);

            foreach ($events as $event) {
                $workshop = $workshops[$event->workshopId];
                if ($workshop->isWithdrawn()) {
                    throw ValidationException::field('id_event', 'Este taller no está disponible.');
                }
                if ($workshop->audience === self::ADULT_AUDIENCE && $attendee->age < self::ADULT_AGE) {
                    throw ValidationException::field('id_event', 'Este taller es solo para mayores de ' . self::ADULT_AGE . ' años.');
                }
                if ($this->schedule->isEnrolled($attendee->id, $event->id)) {
                    throw new ConflictException('Ya estás inscrito en este taller.');
                }
                if ($workshop->tickets <= 0) {
                    throw new ConflictException("No quedan cupos en «{$workshop->name}».");
                }
            }

            $unitIds = array_map(static fn (Event $e): int => $e->id, $events);
            $mine = $this->schedule->findEvents(array_values(array_diff($this->schedule->eventIdsOf($attendee->id), $unitIds)));
            foreach ($events as $event) {
                foreach ($mine as $other) {
                    if ($other->overlaps($event)) {
                        $name = $other->workshopId !== null ? ($this->workshops->find($other->workshopId)?->name ?? 'otro taller') : 'otra actividad';
                        throw new ConflictException("Ya tienes «{$name}» a esa hora.");
                    }
                }
            }

            foreach ($events as $event) {
                $this->schedule->enrol($attendee->id, $event->id);
                $this->workshops->adjustTickets((int) $event->workshopId, -1);
            }

            return ['enrolled' => $unitIds];
        });
    }

    /** Drops the workshop (every part of it) and gives the places back. */
    public function cancel(Session $session, int $attendeeId, int $eventId): void
    {
        $session->requireAttendee($attendeeId);

        $this->transactions->run(function () use ($attendeeId, $eventId): void {
            [$events] = $this->unit($eventId);
            foreach ($events as $event) {
                if ($this->schedule->unenrol($attendeeId, $event->id)) {
                    $this->workshops->adjustTickets((int) $event->workshopId, 1);
                }
            }
        });
    }

    /**
     * The event plus the events of the other parts of its workshop, with those workshops
     * locked. Root = the part 1 (the workshop itself, or what a part 2 continues).
     *
     * @return array{0: list<Event>, 1: array<int, Workshop>}
     */
    private function unit(int $eventId): array
    {
        $event = $this->schedule->findEvent($eventId) ?? throw new NotFoundException('No existe ese evento.');
        if ($event->workshopId === null) {
            throw ValidationException::field('id_event', 'Solo puedes inscribirte en talleres.');
        }
        $workshop = $this->workshops->find($event->workshopId) ?? throw new NotFoundException('No existe ese taller.');
        $rootId = $workshop->part === 2 && $workshop->relation !== null ? $workshop->relation : $workshop->id;
        $workshopIds = array_values(array_unique([$rootId, $workshop->id, ...array_map(static fn (Workshop $w): int => $w->id, $this->workshops->continuationsOf($rootId))]));

        $locked = $this->workshops->lockForUpdate($workshopIds);
        $events = [$event->workshopId => $event];
        foreach ($this->schedule->eventsOfWorkshops($workshopIds) as $id => $partEvent) {
            $events[$id] ??= $partEvent;
        }
        ksort($events);

        return [array_values($events), $locked];
    }
}
