<?php

declare(strict_types=1);

namespace App\Compartido\BaseDatos;

use Illuminate\Database\Schema\Builder;

/**
 * Un archivo de `database/migraciones/AAAA_MM_DD_HHMMSS_nombre.php` devuelve
 * `new class implements Migracion { … }`. Solo se usa lo que soportan MariaDB y SQLite.
 */
interface Migracion
{
    public function subir(Builder $esquema): void;

    public function bajar(Builder $esquema): void;
}
