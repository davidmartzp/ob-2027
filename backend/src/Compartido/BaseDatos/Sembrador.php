<?php

declare(strict_types=1);

namespace App\Compartido\BaseDatos;

use Illuminate\Database\Connection;
use RuntimeException;

/**
 * Ejecuta en orden las semillas de `database/semillas/NN_nombre.php`. Cada archivo
 * devuelve `function (Connection $bd): void` idempotente (buscar por clave natural y
 * crear o actualizar), así que se puede sembrar varias veces.
 */
final readonly class Sembrador
{
    public function __construct(private Connection $bd, private string $directorio)
    {
    }

    /** @return list<string> Semillas ejecutadas. */
    public function sembrar(): array
    {
        $archivos = glob($this->directorio . '/*.php') ?: [];
        sort($archivos);

        foreach ($archivos as $archivo) {
            $semilla = require $archivo;
            if (!is_callable($semilla)) {
                throw new RuntimeException('La semilla ' . basename($archivo) . ' debe devolver una función.');
            }
            $semilla($this->bd);
        }

        return array_map(static fn (string $ruta): string => basename($ruta, '.php'), $archivos);
    }
}
