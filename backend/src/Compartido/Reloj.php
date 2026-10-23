<?php

declare(strict_types=1);

namespace App\Compartido;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * La hora actual en Bogotá. Todo lo que dependa de la hora (caducidad de tokens,
 * turnos, ventanas) la pide aquí, para que las pruebas puedan fijarla con `fijar()`.
 */
final class Reloj implements ClockInterface
{
    private ?DateTimeImmutable $fijo = null;

    public function now(): DateTimeImmutable
    {
        return $this->fijo ?? new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));
    }

    /** Solo pruebas: congela el reloj en esa hora (zona de Bogotá). */
    public function fijar(string $momento): void
    {
        $this->fijo = new DateTimeImmutable($momento, new DateTimeZone('America/Bogota'));
    }

    public function avanzar(string $intervalo): void
    {
        $this->fijo = $this->now()->modify($intervalo);
    }

    /** Formato de las columnas DATETIME. */
    public function ahoraSql(): string
    {
        return $this->now()->format('Y-m-d H:i:s');
    }
}
