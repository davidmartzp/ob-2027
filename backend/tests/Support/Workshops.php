<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Connection;

/** Inserts made-up workshops and partners for tests. */
final class Workshops
{
    /** @param array<string, mixed> $overrides @return int The new id. */
    public static function create(Connection $db, int $instructorId, array $overrides = []): int
    {
        return (int) $db->table('workshops')->insertGetId($overrides + [
            'name' => 'Grulla clásica',
            'public' => 'A',
            'level' => 'P',
            'duration' => 60,
            'observations' => 'Traer papel de 15 cm',
            'status' => 1,
            'tickets' => 20,
            'part' => 1,
            'id_attendee' => $instructorId,
        ]);
    }

    /** @param list<int> $workshopIds @return int The partner id. */
    public static function partner(Connection $db, array $workshopIds, array $overrides = []): int
    {
        $id = (int) $db->table('partners')->insertGetId($overrides + [
            'name' => 'Papelería El Pliegue',
            'whatsapp' => '573001234567',
            'message' => 'Hola, vengo del congreso',
            'status' => 1,
        ]);
        foreach ($workshopIds as $workshopId) {
            $db->table('workshop_partner')->insert(['id_workshop' => $workshopId, 'id_partner' => $id]);
        }

        return $id;
    }

    /** Smallest valid PNG (1×1 px). */
    public static function png(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }
}
