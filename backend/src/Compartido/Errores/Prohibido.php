<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

final class Prohibido extends ErrorDominio
{
    public function __construct(string $mensaje = 'No tiene permiso para esta operación.', array $extra = [])
    {
        parent::__construct($mensaje, 403, 'prohibido', $extra);
    }
}
