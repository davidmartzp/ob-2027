<?php

declare(strict_types=1);

use Illuminate\Database\Connection;

/* Rooms of the congress venue (as in production, 2026-10-23). Idempotent by id. */
return static function (Connection $db): void {
    foreach ([1 => 'Sala 1', 2 => 'Sala 2', 3 => 'Sala 3', 4 => 'Sala 4', 5 => 'Sala 5', 6 => 'KIDS', 8 => 'KIDS 2', 9 => 'Sala Especial'] as $id => $name) {
        $db->table('rooms')->updateOrInsert(['id' => $id], ['name' => $name]);
    }
};
