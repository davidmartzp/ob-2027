<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

final class DemasiadosIntentos extends ErrorDominio
{
    public function __construct(string $mensaje = 'Demasiados intentos. Espere un momento y vuelva a intentarlo.', array $extra = [])
    {
        parent::__construct($mensaje, 429, 'demasiados_intentos', $extra);
    }
}
