<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

final class Conflicto extends ErrorDominio
{
    public function __construct(string $mensaje = 'La operación entra en conflicto con el estado actual.', array $extra = [])
    {
        parent::__construct($mensaje, 409, 'conflicto', $extra);
    }
}
