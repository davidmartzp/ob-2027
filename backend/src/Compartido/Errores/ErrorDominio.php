<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

use RuntimeException;

/**
 * Error esperado de la lógica de negocio. Los servicios lo lanzan y el manejador de
 * errores lo convierte en `{"error": {"codigo", "mensaje", ...extra}}` con su estado HTTP.
 */
abstract class ErrorDominio extends RuntimeException
{
    /** @param array<string, mixed> $extra Campos adicionales dentro de `error` (p. ej. `campos`). */
    public function __construct(
        string $mensaje,
        private readonly int $estado,
        private readonly string $codigo,
        private readonly array $extra = [],
    ) {
        parent::__construct($mensaje);
    }

    public function estado(): int
    {
        return $this->estado;
    }

    public function codigo(): string
    {
        return $this->codigo;
    }

    /** @return array<string, mixed> */
    public function extra(): array
    {
        return $this->extra;
    }
}
