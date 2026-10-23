<?php

declare(strict_types=1);

use App\Compartido\BaseDatos\Migracion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return new class implements Migracion {
    public function subir(Builder $esquema): void
    {
        $esquema->create('dos', static fn (Blueprint $t) => $t->id());
    }

    public function bajar(Builder $esquema): void
    {
        $esquema->drop('dos');
    }
};
