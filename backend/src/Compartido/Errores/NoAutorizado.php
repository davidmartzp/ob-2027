<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

final class NoAutorizado extends ErrorDominio
{
    public function __construct(string $mensaje = 'Hace falta iniciar sesión.', array $extra = [])
    {
        parent::__construct($mensaje, 401, 'no_autorizado', $extra);
    }
}
