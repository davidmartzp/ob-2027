<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Schedule\Activity;
use App\Domain\Schedule\Day;
use App\Domain\Schedule\Event;
use App\Domain\Schedule\Room;
use App\Domain\Schedule\ScheduleRepository;
use Illuminate\Database\Connection;

/** Legacy tables `rooms`, `activities`, `events` and `events_attendees`. */
final readonly class DbScheduleRepository implements ScheduleRepository
{
    public function __construct(private Connection $db)
    {
    }

    public function rooms(): array
    {
        return $this->db->table('rooms')->orderBy('id')->get()
            ->map(static fn (object $r): Room => new Room((int) $r->id, $r->name))->all();
    }

    public function roomExists(int $id): bool
    {
        return $this->db->table('rooms')->where('id', $id)->exists();
    }

    public function activities(): array
    {
        return $this->db->table('activities')->orderBy('id')->get()
            ->map(static fn (object $a): Activity => new Activity((int) $a->id, trim($a->name), $a->description, (int) $a->duration))->all();
    }

    public function activityExists(int $id): bool
    {
        return $this->db->table('activities')->where('id', $id)->exists();
    }

    public function findEvent(int $id): ?Event
    {
        $row = $this->db->table('events')->where('id', $id)->first();

        return $row === null ? null : self::event($row);
    }

    public function events(): array
    {
        return $this->db->table('events')->orderBy('day')->orderBy('start_time')->orderBy('id_room')->get()
            ->map(self::event(...))->all();
    }

    public function findEvents(array $ids): array
    {
        return $ids === [] ? [] : $this->db->table('events')->whereIn('id', $ids)->get()->map(self::event(...))->all();
    }

    public function eventsOfWorkshops(array $workshopIds): array
    {
        if ($workshopIds === []) {
            return [];
        }
        $byWorkshop = [];
        foreach ($this->db->table('events')->whereIn('id_workshop', $workshopIds)->orderBy('id')->get() as $row) {
            $byWorkshop[(int) $row->id_workshop] ??= self::event($row);
        }

        return $byWorkshop;
    }

    public function overlappingInRoom(int $roomId, Day $day, string $start, string $end): ?Event
    {
        $row = $this->db->table('events')
            ->where('id_room', $roomId)
            ->where('day', $day->value)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->first();

        return $row === null ? null : self::event($row);
    }

    public function createEvent(int $roomId, Day $day, string $start, string $end, ?int $workshopId, ?int $activityId): Event
    {
        $now = date('Y-m-d H:i:s');
        $id = $this->db->table('events')->insertGetId([
            'id_room' => $roomId,
            'day' => $day->value,
            'start_time' => $start,
            'end_time' => $end,
            'id_workshop' => $workshopId,
            'id_activity' => $activityId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findEvent((int) $id);
    }

    public function deleteEvent(int $id): int
    {
        $removed = $this->db->table('events_attendees')->where('id_event', $id)->delete();
        $this->db->table('events')->where('id', $id)->delete();

        return $removed;
    }

    public function eventIdsOf(int $attendeeId): array
    {
        return $this->db->table('events_attendees')->where('id_attendee', $attendeeId)->orderBy('id_event')->pluck('id_event')
            ->map(static fn (mixed $id): int => (int) $id)->all();
    }

    public function attendeeIdsOf(int $eventId): array
    {
        return $this->db->table('events_attendees')->where('id_event', $eventId)->pluck('id_attendee')
            ->map(static fn (mixed $id): int => (int) $id)->all();
    }

    public function isEnrolled(int $attendeeId, int $eventId): bool
    {
        return $this->db->table('events_attendees')->where(['id_attendee' => $attendeeId, 'id_event' => $eventId])->exists();
    }

    public function enrol(int $attendeeId, int $eventId): void
    {
        $this->db->table('events_attendees')->insert(['id_attendee' => $attendeeId, 'id_event' => $eventId]);
    }

    public function unenrol(int $attendeeId, int $eventId): bool
    {
        return $this->db->table('events_attendees')->where(['id_attendee' => $attendeeId, 'id_event' => $eventId])->delete() > 0;
    }

    private static function event(object $row): Event
    {
        return new Event(
            id: (int) $row->id,
            roomId: (int) $row->id_room,
            day: Day::parse((string) $row->day) ?? Day::Saturday,
            startTime: substr((string) $row->start_time, 0, 8),
            endTime: substr((string) $row->end_time, 0, 8),
            workshopId: $row->id_workshop === null ? null : (int) $row->id_workshop,
            activityId: $row->id_activity === null ? null : (int) $row->id_activity,
        );
    }
}
