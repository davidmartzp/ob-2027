<?php

declare(strict_types=1);

namespace App\Application\Schedule;

use App\Application\Port\TransactionManager;
use App\Application\Shared\Validator;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Schedule\Activity;
use App\Domain\Schedule\Day;
use App\Domain\Schedule\Event;
use App\Domain\Schedule\Room;
use App\Domain\Schedule\ScheduleRepository;
use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\NotFoundException;
use App\Domain\Shared\Exception\ValidationException;
use App\Domain\Workshop\WorkshopRepository;

/** The CMS builds the programme: rooms and activities (read only), events, attendance lists. */
final readonly class ManageSchedule
{
    public function __construct(
        private ScheduleRepository $schedule,
        private WorkshopRepository $workshops,
        private AttendeeRepository $attendees,
        private TransactionManager $transactions,
        private ScheduleView $view,
    ) {
    }

    /** @return list<array{id: int, name: string|null}> */
    public function rooms(): array
    {
        return array_map(static fn (Room $r): array => ['id' => $r->id, 'name' => $r->name], $this->schedule->rooms());
    }

    /** @return list<array{id: int, name: string, description: string|null, duration: int}> */
    public function activities(): array
    {
        return array_map(static fn (Activity $a): array => ['id' => $a->id, 'name' => $a->name, 'description' => $a->description, 'duration' => $a->duration], $this->schedule->activities());
    }

    /** @return list<array<string, mixed>> */
    public function events(): array
    {
        return array_map(ScheduleView::event(...), $this->schedule->events());
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createEvent(array $input): array
    {
        $data = Validator::validate($input, [
            'id_room' => 'required|int:1,100000000',
            'day' => 'required|string',
            'start_time' => 'required|time',
            'end_time' => 'required|time',
            'id_workshop' => 'int:1,100000000',
            'id_activity' => 'int:1,100000000',
        ]);
        $day = Day::parse($data['day']) ?? throw ValidationException::field('day', 'Debe ser Saturday o Sunday.');
        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::field('end_time', 'Debe ser posterior a la hora de inicio.');
        }
        if (($data['id_workshop'] === null) === ($data['id_activity'] === null)) {
            throw ValidationException::field('id_workshop', 'Indica un taller o una actividad, no ambos.');
        }
        if (!$this->schedule->roomExists($data['id_room'])) {
            throw ValidationException::field('id_room', 'No existe ese salón.');
        }
        if ($data['id_workshop'] !== null) {
            $workshop = $this->workshops->find($data['id_workshop']);
            if ($workshop === null || $workshop->isWithdrawn()) {
                throw ValidationException::field('id_workshop', 'El taller no existe o fue retirado.');
            }
        }
        if ($data['id_activity'] !== null && !$this->schedule->activityExists($data['id_activity'])) {
            throw ValidationException::field('id_activity', 'No existe esa actividad.');
        }

        $clash = $this->schedule->overlappingInRoom($data['id_room'], $day, $data['start_time'], $data['end_time']);
        if ($clash !== null) {
            throw new ConflictException("El salón ya está ocupado de {$clash->startTime} a {$clash->endTime} ese día.");
        }

        return ScheduleView::event($this->schedule->createEvent($data['id_room'], $day, $data['start_time'], $data['end_time'], $data['id_workshop'], $data['id_activity']));
    }

    /** Deletes the event and its enrolments, giving the workshop back one place per enrolment. */
    public function deleteEvent(int $id): void
    {
        $this->transactions->run(function () use ($id): void {
            $event = $this->schedule->findEvent($id) ?? throw new NotFoundException('No existe ese evento.');
            $removed = $this->schedule->deleteEvent($id);
            if ($event->workshopId !== null && $removed > 0) {
                $this->workshops->adjustTickets($event->workshopId, $removed);
            }
        });
    }

    /** @return list<array<string, mixed>> GET /events/workshops: workshop events for the attendance lists. */
    public function workshopEvents(): array
    {
        return $this->view->workshopEvents(array_values(array_filter($this->schedule->events(), static fn (Event $e): bool => $e->workshopId !== null)));
    }

    /** @return list<array{id: int, code: int|null, name: string}> GET /events/{id}/attendees, by participant number. */
    public function eventAttendees(int $eventId): array
    {
        if ($this->schedule->findEvent($eventId) === null) {
            throw new NotFoundException('No existe ese evento.');
        }
        $people = array_values($this->attendees->findByIds($this->schedule->attendeeIdsOf($eventId)));
        usort($people, static fn ($a, $b): int => [$a->code ?? PHP_INT_MAX, $a->id] <=> [$b->code ?? PHP_INT_MAX, $b->id]);

        return array_map(static fn ($a): array => ['id' => $a->id, 'code' => $a->code, 'name' => $a->shortName()], $people);
    }
}
