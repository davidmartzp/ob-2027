<?php

declare(strict_types=1);

use Illuminate\Database\Connection;

/* Non-workshop slots of the programme (as in production, 2026-10-23). Idempotent by id. */
return static function (Connection $db): void {
    $activities = [
        1 => ['Inscripciones y Selección de talleres', 'Inscripciones y Selección de talleres del día por orden de inscripción', 60],
        2 => ['Foto Grupal y con el invitado', 'Foto Grupal y con el invitado', 60],
        3 => ['Receso 15min', 'Receso', 15],
        6 => ['Sesión de intercambio libre', 'Sesión de intercambio libre', 60],
        7 => ['Chocogami / Intercambio de tarjetas / Intercambio libre', 'Chocogami / Intercambio de tarjetas / Intercambio libre', 90],
        8 => ['Chocolatada', 'Clausura / Chocolatada', 90],
        9 => ['Clausura', 'Clausura', 90],
        10 => ['Conferencias Invitados Nacional e Internacional (Auditorio)', 'Conferencias Invitados Nacional e Internacional', 60],
        11 => ['Almuerzo', 'Almuerzo', 120],
        12 => ['Receso 30min', null, 30],
        13 => ['¡La Chiva Rumbera!', null, 120],
    ];
    foreach ($activities as $id => [$name, $description, $duration]) {
        $db->table('activities')->updateOrInsert(['id' => $id], ['name' => $name, 'description' => $description, 'duration' => $duration]);
    }
};
