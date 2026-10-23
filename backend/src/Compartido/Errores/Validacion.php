<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

final class Validacion extends ErrorDominio
{
    /** @param array<string, string> $campos Mensaje por cada campo inválido. */
    public function __construct(array $campos, string $mensaje = 'Hay datos por corregir.')
    {
        parent::__construct($mensaje, 422, 'validacion', ['campos' => $campos]);
    }

    public static function campo(string $campo, string $mensaje): self
    {
        return new self([$campo => $mensaje]);
    }
}
