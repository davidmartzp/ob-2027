<?php

declare(strict_types=1);

namespace App\Compartido\Errores;

final class NoEncontrado extends ErrorDominio
{
    public function __construct(string $mensaje = 'No encontramos lo que busca.', array $extra = [])
    {
        parent::__construct($mensaje, 404, 'no_encontrado', $extra);
    }
}
