<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Connection;

/** Inserts made-up rooms and events for tests. */
final class Schedule
{
    public static function room(Connection $db, string $name = 'Sala 1'): int
    {
        return (int) $db->table('rooms')->insertGetId(['name' => $name]);
    }

    public static function activity(Connection $db, string $name = 'Almuerzo'): int
    {
        return (int) $db->table('activities')->insertGetId(['name' => $name, 'duration' => 60]);
    }

    /** @param array<string, mixed> $overrides */
    public static function event(Connection $db, int $roomId, ?int $workshopId, string $start = '10:00:00', string $end = '12:00:00', string $day = 'Saturday', array $overrides = []): int
    {
        return (int) $db->table('events')->insertGetId($overrides + [
            'id_room' => $roomId,
            'day' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'id_workshop' => $workshopId,
            'id_activity' => null,
        ]);
    }
}
