<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Attendee\AttendeeStatus;
use Illuminate\Database\Connection;

/** Inserts made-up attendees for tests. */
final class Attendees
{
    private static int $sequence = 0;

    /** @param array<string, mixed> $overrides @return int The new id. */
    public static function create(Connection $db, array $overrides = []): int
    {
        $n = ++self::$sequence;

        return (int) $db->table('attendees')->insertGetId($overrides + [
            'name' => "Persona{$n}",
            'lastname' => 'Prueba',
            'age' => 30,
            'email' => "persona{$n}@ejemplo.com",
            'phone' => '3001234567',
            'country' => 'Colombia (+57)',
            'city' => 'Bogotá',
            'paymethod' => 'Nequi',
            'paydate' => '2027-03-01 10:00:00',
            'receipt' => "R-{$n}",
            'days' => 'T',
            'status' => AttendeeStatus::Confirmed->value,
        ]);
    }
}
