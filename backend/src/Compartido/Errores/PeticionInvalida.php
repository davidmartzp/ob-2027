<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

final class PeticionInvalida extends ErrorDominio
{
    public function __construct(string $mensaje = 'La petición no tiene un formato válido.', array $extra = [])
    {
        parent::__construct($mensaje, 400, 'peticion_invalida', $extra);
    }
}
