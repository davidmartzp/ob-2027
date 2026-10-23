<?php

declare(strict_types=1);

namespace App\Application\Schedule;

use App\Application\Partner\PartnerView;
use App\Application\Port\ImageStorage;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Partner\PartnerRepository;
use App\Domain\Schedule\Event;
use App\Domain\Schedule\Room;
use App\Domain\Schedule\ScheduleRepository;
use App\Domain\Workshop\Workshop;
use App\Domain\Workshop\WorkshopRepository;
use App\Domain\Workshop\WorkshopStatus;

/** JSON shapes of the programme, reusing the legacy field names the site already reads. */
final readonly class ScheduleView
{
    public function __construct(
        private ScheduleRepository $schedule,
        private WorkshopRepository $workshops,
        private AttendeeRepository $attendees,
        private PartnerRepository $partners,
        private PartnerView $partnerView,
        private ImageStorage $images,
    ) {
    }

    /** @return array{id: int, id_room: int, id_workshop: int|null, id_activity: int|null, day: string, start_time: string, end_time: string} */
    public static function event(Event $e): array
    {
        return [
            'id' => $e->id,
            'id_room' => $e->roomId,
            'id_workshop' => $e->workshopId,
            'id_activity' => $e->activityId,
            'day' => $e->day->value,
            'start_time' => $e->startTime,
            'end_time' => $e->endTime,
        ];
    }

    /**
     * Each workshop event as `{event, workshop, room}` (GET /events/active).
     *
     * @param list<Event> $events
     * @return list<array<string, mixed>>
     */
    public function activeEvents(array $events): array
    {
        $context = $this->context($events);

        $rows = [];
        foreach ($events as $event) {
            $workshop = $context['workshops'][$event->workshopId] ?? null;
            if ($workshop === null) {
                continue;
            }
            $rows[] = [
                'event' => ['id' => $event->id, 'day' => $event->day->value, 'start_time' => $event->startTime, 'end_time' => $event->endTime],
                'workshop' => [
                    'id' => $workshop->id,
                    'name' => $workshop->name,
                    'instructor' => $context['instructors'][$workshop->id] ?? null,
                    'duration' => $workshop->duration,
                    'level' => $workshop->level,
                    'public' => $workshop->audience,
                    'image' => $this->images->url($workshop->image),
                    'tickets' => $workshop->tickets,
                    'partners' => $context['partners'][$workshop->id] ?? [],
                ],
                'room' => self::room($context['rooms'][$event->roomId] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * Each workshop event as a workshop with its `event` inside (GET /events/schedule).
     *
     * @param list<Event> $events
     * @return list<array<string, mixed>>
     */
    public function schedule(array $events): array
    {
        $context = $this->context($events);

        $rows = [];
        foreach ($events as $event) {
            $workshop = $context['workshops'][$event->workshopId] ?? null;
            if ($workshop === null) {
                continue;
            }
            $rows[] = [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'image' => $this->images->url($workshop->image),
                'level' => $workshop->level,
                'observations' => $workshop->observations,
                'public' => $workshop->audience,
                'duration' => $workshop->duration,
                'instructor' => $context['instructors'][$workshop->id] ?? null,
                'event' => [
                    'id' => $event->id,
                    'day' => $event->day->value,
                    'start_time' => $event->startTime,
                    'end_time' => $event->endTime,
                    'room' => self::room($context['rooms'][$event->roomId] ?? null),
                ],
                'partners' => $context['partners'][$workshop->id] ?? [],
            ];
        }

        return $rows;
    }

    /** @param list<Event> $events @return list<array{id_event: int, workshop_name: string, instructor: array{id: int, name: string}|null}> */
    public function workshopEvents(array $events): array
    {
        $context = $this->context($events, withPartners: false);

        return array_values(array_filter(array_map(static fn (Event $e): ?array => isset($context['workshops'][$e->workshopId]) ? [
            'id_event' => $e->id,
            'workshop_name' => $context['workshops'][$e->workshopId]->name,
            'instructor' => $context['instructors'][$e->workshopId] ?? null,
        ] : null, $events)));
    }

    /** @return array{id: int, name: string|null}|null */
    private static function room(?Room $room): ?array
    {
        return $room === null ? null : ['id' => $room->id, 'name' => $room->name];
    }

    /**
     * Active workshops, their instructors (first name + first surname), rooms and partners.
     *
     * @param list<Event> $events
     * @return array{workshops: array<int, Workshop>, instructors: array<int, array{id: int, name: string}>, rooms: array<int, Room>, partners: array<int, list<array<string, mixed>>>}
     */
    private function context(array $events, bool $withPartners = true): array
    {
        $workshops = [];
        foreach (array_unique(array_filter(array_map(static fn (Event $e): ?int => $e->workshopId, $events))) as $id) {
            $workshop = $this->workshops->find($id);
            if ($workshop !== null && $workshop->status === WorkshopStatus::Active) {
                $workshops[$id] = $workshop;
            }
        }
        $people = $this->attendees->findByIds(array_values(array_unique(array_filter(array_map(static fn (Workshop $w): ?int => $w->attendeeId, $workshops)))));
        $instructors = [];
        foreach ($workshops as $id => $workshop) {
            $person = $people[$workshop->attendeeId] ?? null;
            if ($person !== null) {
                $instructors[$id] = ['id' => $person->id, 'name' => $person->shortName()];
            }
        }
        $rooms = [];
        foreach ($this->schedule->rooms() as $room) {
            $rooms[$room->id] = $room;
        }
        $partners = $withPartners
            ? array_map(fn (array $list): array => array_map($this->partnerView->toArray(...), $list), $this->partners->activeByWorkshop(array_keys($workshops)))
            : [];

        return ['workshops' => $workshops, 'instructors' => $instructors, 'rooms' => $rooms, 'partners' => $partners];
    }
}
